<?php
namespace app\commands;

use app\components\YoutubeWorkerComponent;
use Yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\console\ExitCode;

class DownloadController extends Controller
{
    public function actionIndex($url, $codec="mp4", $quality="360", $storage="localfs")
    {
        try {
            /**
             * @var $oDownloader YoutubeWorkerComponent
             */
            $oDownloader = Yii::$app->downloader;

            $this->logMessage( "Target codec: ". $codec );
            $oDownloader->setTargetCodec( $codec );

            $this->logMessage( "Target quality: ". $quality );
            $oDownloader->setTargetQuality( $quality );

            $this->logMessage( "Target storage: ". $storage );
            $this->logMessage( "Getting info from url: ". $url );

            $oDownloader->loadUrl( $url );

            $this->logMessage( "Available videos: " . $oDownloader->getAvailableVideosList() );
            $this->logMessage( "Available codecs: " . $oDownloader->getAvailableCodecsList() );
            $this->logMessage( "Available qualities: " . $oDownloader->getAvailableQualitiesList() );
            if ( $oDownloader->isValidTask() ) {
                $aTasks = $oDownloader->createTask( $storage );
                $this->logMessage( "Creating ". count( $aTasks ). " tasks" );
                foreach ( $aTasks as $oTask ) {
                    Yii::$app->queue->push( $oTask );
                }
            } else {
                $this->logMessage( "Invalid task. Required quality or codec is unavailable" );
            }
        } catch ( Exception $oException ) {
            $this->logMessage( $oException->getMessage() );
            return $oException->getCode();
        }
        return ExitCode::OK;
    }

    private function logMessage( $sMessage ) {
        echo $sMessage . " \n";
    }
}
