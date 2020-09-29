<?php
namespace app\components;

class DownloaderTaskLocalFs extends DownloaderTask
{
    /**
     * Very simple task execution example.
     * @param \yii\queue\Queue $queue
     * @return mixed|void
     */
    public function execute($queue)
    {
        echo "Downloading video: ". $this->videoId. " ...";
        if ( @file_put_contents( $this->path.$this->filename , @file_get_contents($this->url)) ) {
            echo $this->generateSnippet();
            echo "Done! \n";
        } else {
            echo "Error! \n";
        }
    }
}

