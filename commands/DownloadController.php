<?php
namespace app\commands;

use app\components\DownloaderTask;
use app\components\YoutubeWorkerComponent;
use app\models\Tasks;
use Yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\console\ExitCode;

class DownloadController extends Controller
{
    public function actionList() {
        // Show Id, date, VideoId, Status,
        /**
         * @var $aTasks Tasks[]
         */
        $aTasks = Tasks::find()->all();
        if ( !empty( $aTasks ) ) {
            foreach ( $aTasks as $oTask ) {
                echo implode( "\t" , [ $oTask->id, $oTask->video_id, $oTask->ts, $oTask->status, $oTask->preview, $oTask->storage ] )." \n";
            }
        }

    }

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
                /**
                 * @var $aTasks DownloaderTask[]
                 */
                $aTasks = $oDownloader->createTask( $storage );
                $this->logMessage( "Creating ". count( $aTasks ). " tasks" );

                foreach ( $aTasks as $oTask ) {
                    $this->logMessage( "Adding task" );
                    $oTaskModel = Tasks::createFomDownloaderTaskModel(  $oTask,  $url );
                    if (  $oTaskModel->validate() && $oTaskModel->save() ) {

                        $oTaskModel->refresh();
                    } else {
                      $this->logMessage( "Task create error" );
                    }
                    $oTask->setId( $oTaskModel->id );
                    Yii::$app->queue->push( $oTask );
                }
                return Yii::$app->end(0);
            } else {
                $this->logMessage( "Invalid task. Required quality or codec is unavailable" );
            }
        } catch ( Exception $oException ) {
            $this->logMessage( $oException->getMessage() );
            return $oException->getCode();
        }

    }

    private function logMessage( $sMessage ) {
        echo $sMessage . " \n";
    }
}
