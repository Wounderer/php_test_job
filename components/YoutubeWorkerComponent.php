<?php
namespace app\components;

use app\components\DownloaderTask;
use app\models\Tasks;
use yii\console\Exception;
use YouTube\YouTubeDownloader;
use linslin\yii2\curl;

/**
 * Class YoutubeWorkerComponent
 * @package app\components
 */
class YoutubeWorkerComponent extends \yii\base\Component
{

    /**
     * Storage types
     */
    const STORAGE_LOCAL = "localfs";
    const STORAGE_GRIVE = "gdrive";
    const STORAGE_S3 = "s3";

    // Constructor config params
    /**
     * @var array
     */
    public $codecs = [];
    public $qualities = [];

    /**
     * Implemented only local storage
     * @var array
     */
    public $storages = [
        self::STORAGE_LOCAL => [
            "path" => "/tmp/",
        ],
        self::STORAGE_GRIVE => false,
        self::STORAGE_S3 => false,
    ];

    /**
     * Create required extensions object after component initiation
     */
    public function init()
    {
        parent::init();
        $this->oDownloader = new YouTubeDownloader();
        $this->oCurl = new curl\Curl();
    }

    /**
     * Load available videos from url and store info about available variations
     * @param $sUrl
     * @throws Exception
     */
    public function loadUrl( $sUrl ) {
        // Bad url
        if (filter_var($sUrl, FILTER_VALIDATE_URL) === FALSE) {
            throw new Exception("Invalid url format");
        }

        $sHtml = $this->oCurl->get( $sUrl );
        if ($this->oCurl->errorCode === null) {
            if (preg_match_all('#(?:https?:\/\/)?(?:m\.|www\.)?(?:youtu\.be\/|youtube\-nocookie\.com\/embed\/|youtube\.com\/(?:embed\/|v\/|e\/|\?v=|shared\?ci=|watch\?v=|watch\?.+&v=))([-_A-Za-z0-9]{10}[AEIMQUYcgkosw048])(.*?)\b#s', $sHtml, $aVideoIds, PREG_SET_ORDER)) {
                if ( !empty( $aVideoIds ) ) {
                    foreach ( $aVideoIds as $aMatch ) {
                        if ( !in_array( $aMatch[1],  $this->aAvailableIds ) ) {
                            $this->aAvailableIds[] = $aMatch[1];
                        }
                    }
                } else {
                    throw new Exception( "No videos found on url" );
                }
            }
        } else {
            // List of curl error codes here https://curl.haxx.se/libcurl/c/libcurl-errors.html
            throw new Exception( "Url response code: " . $this->oCurl->responseCode );
        }

        if ( empty( $this->aAvailableIds ) ) {
            throw new Exception( "No videos found on url" );
        }

        foreach ( $this->aAvailableIds as $sVideoId ) {
            $aUrls = $this->oDownloader->getDownloadLinks($sVideoId);
            if ( !empty( $aUrls ) ) {
                foreach ($aUrls as $oUrl) {
                    $aUrlParts = explode(", ", $oUrl["format"]);
                    // Only videos - ignoring audio
                    if (array_key_exists(1, $aUrlParts) && $aUrlParts[1] === "video") {
                        $sCodecName = strtolower(trim($aUrlParts[0]));
                        $sQuality = str_replace("p", "", strtolower(trim($aUrlParts[2])));
                        $sStoredKey = $sCodecName . $sQuality;
                        // If component config contains codecs or quality limitations
                        if ($this->isValidCodec($sCodecName) && $this->isValidQuality($sQuality)) {
                            if ( $this->sTargetCodec == $sCodecName && $this->sTargetQuality == $sQuality ) {
                                $this->aDownloadUrls[ $sVideoId ] = $oUrl["url"];
                            }
                            $this->aAvailableUrls[$sStoredKey] = $oUrl['url'];
                            $this->aAvailableCodecs[] = $sCodecName;
                            $this->aAvailableQualities[] = $sQuality;
                        }
                    }

                }
            }
        }
        // Remove repeated data. Better than checking in_array on each iteration
        $this->aAvailableQualities = array_unique( $this->aAvailableQualities );
        $this->aAvailableCodecs = array_unique( $this->aAvailableCodecs );
    }

    /**
     * @param $sVideoId
     * @return mixed
     * @throws Exception
     */
    public function getDownloadUrl( $sVideoId ) {
        if ( array_key_exists( $sVideoId,  $this->aDownloadUrls ) ) {
            return $this->aDownloadUrls[ $sVideoId ];
        } else {
            throw new Exception( "Cant find download url for video: ". $sVideoId  );
        }

    }

    /**
     * @param $sCodec
     * @return bool
     */
    public function setTargetCodec( $sCodec ) {
        if ( $this->isValidCodec( $sCodec ) ) {
            $this->sTargetCodec = $sCodec;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $sQuality
     * @return bool
     */
    public function setTargetQuality( $sQuality ) {
        if ( $this->isValidQuality( $sQuality ) ) {
            $this->sTargetQuality = $sQuality;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $sCodecName
     * @return bool
     */
    public function isValidCodec( $sCodecName ) {
        return empty( $this->codecs ) || in_array( $sCodecName, $this->codecs) ;
    }

    /**
     * @param $sCodecName
     * @return bool
     */
    public function isValidQuality( $sCodecName ) {
        return empty( $this->qualities ) || in_array( $sCodecName, $this->qualities );
    }

    /**
     * @return bool
     */
    public function isValidTask()  {
        return !empty( $this->aAvailableIds ) && array_key_exists( $this->sTargetCodec.$this->sTargetQuality, $this->aAvailableUrls );
    }

    /**
     * List of available codecs
     * @return string
     */
    public function getAvailableCodecsList() {
        return implode(", ", $this->aAvailableCodecs );
    }


    /**
     * @return string
     */
    public function getAvailableVideosList() {
        return implode(", ", $this->aAvailableIds );
    }

    /**
     * List of available qualities
     * @return string
     */
    public function getAvailableQualitiesList() {
        return implode(", ", $this->aAvailableQualities );
    }

    /**
     * @param $sStorageType
     * @return array|DownloaderTask[]
     * @throws Exception
     */
    public function createTask( $sStorageType ) {
        $aReturnTasks = [];
        if ( array_key_exists( $sStorageType, $this->storages ) && $this->storages[ $sStorageType ] !== false ) {
            if ( !empty( $this->aAvailableIds ) ) {
                foreach ( $this->aAvailableIds as $sId ) {
                    $aReturnTasks[] = new DownloaderTask(
                        [
                            'storage_name' => $sStorageType,
                            "url" => $this->getDownloadUrl( $sId ),
                            "filename"=>$this->createFilename( $sId ),
                            "path" => $this->storages[ $sStorageType]["path"],
                            "videoId" => $sId,
                            "codec" => $this->sTargetCodec,
                            'quality' => $this->sTargetQuality
                        ]
                    );
                }
            }
        } else {
            throw new Exception( "Storage type is incorrect or disabled" );
        }
        return $aReturnTasks;
    }

    /**
     * @var array
     */
    private $aAvailableIds = [];

    /**
     * @param $sVideoId
     * @return string
     */
    private function createFilename( $sVideoId ) {
        return implode("_", [ $sVideoId, $this->sTargetCodec, $this->sTargetQuality, ".".$this->sTargetCodec ] );
    }

    /**
     * @var string
     */
    private $sTargetCodec = "mp4";
    /**
     * @var string
     */
    private $sTargetQuality = "360";


    /**
     * @var array
     */
    private $aAvailableUrls = [];
    private $aAvailableQualities = [];
    private $aAvailableCodecs = [];
    private $aDownloadUrls = [];


    /**
     * @var YouTubeDownloader
     */
    private $oDownloader;

    /**
     * @var curl\Curl
     */
    private $oCurl;
}