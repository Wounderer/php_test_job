<?php

namespace app\components;

use yii\base\BaseObject;
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

    public function execute($queue)
    {

    }

    /**
     * @return string
     */
    public function getPreviewUrl() {
        return "https://img.youtube.com/vi/".$this->videoId."/default.jpg";

    }
    public function generateSnippet() {
        $sType = explode( $this->filename, "." )[1];
        $sHtml = '<video width="320" height="240" controls><source src="'.$this->path.$this->filename.'" type="video/'.$sType.'">Your browser does not support the video tag.</video>';
        echo $sHtml."\n";
    }
}

