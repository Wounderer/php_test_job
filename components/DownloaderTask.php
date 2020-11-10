<?php

namespace app\components;

use app\models\Tasks;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\queue\JobInterface;

/**
 * Class DownloaderTask
 * @package app\components
 */
class DownloaderTask extends BaseObject implements JobInterface
{
    public $url;
    public $path;
    public $videoId;
    public $filename;
    public $codec;
    public $quality;
    public $storage_name;
    public $id;

    public function setId($newid) {
        $this->id = $newid;
    }

    /**
     * @param \yii\queue\Queue $queue
     * @return mixed|void
     */
    public function execute($queue)
    {
        if ( $oModel = Tasks::findOne( ['id'=>$this->id ] ) ) {

            $oModel->status = Tasks::STATUS_STARTED;
            if ( !$oModel->save() ) {
               return 1;
            }
            try {
                if ($this->storage_name === YoutubeWorkerComponent::STORAGE_LOCAL) {
                    $this->saveToStorage(new LocalFsStorage( $this ));

                } else if ($this->storage_name === YoutubeWorkerComponent::STORAGE_GRIVE) {
                    $this->saveToStorage( new GdriveStorage( $this ) );
                } else {
                    echo "Unknown storage";
                }
                echo "Finished \n";
                $oModel->status = Tasks::STATUS_FINISHED;
            } catch ( Exception $oException ) {
                $oModel->status = Tasks::STATUS_ERROR;
            }

            if ( !$oModel->save() ) {
                var_dump( $oModel->getErrors() );
            }
        } else {
            echo "not found \n";
        }
    }

    /**
     * @param StorageInterface $oStorage
     */
    public function saveToStorage( StorageInterface $oStorage ) {

        $oStorage->apply();
    }

    /**
     * @return string
     */
    public function getPreviewUrl( Tasks $oModel ) {
        return "https://img.youtube.com/vi/".$oModel->video_id."/default.jpg";

    }

    /**
     *
     */
    public function generateSnippet() {
        $sType = explode( $this->filename, "." )[1];
        $sHtml = '<video width="320" height="240" controls><source src="'.$this->path.$this->filename.'" type="video/'.$sType.'">Your browser does not support the video tag.</video>';
        echo $sHtml."\n";
    }

}

