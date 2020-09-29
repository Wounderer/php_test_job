<?php
/**
 * Yii bootstrap file.
 * Used for enhanced IDE code autocompletion.
 */
class Yii extends \yii\BaseYii
{
    /**
     * @var ConsoleApplication the application instance
     */
    public static $app;
}

/**
 * Class ConsoleApplication
 * Include only Console application related components here
 *
 * @property \app\components\YoutubeWorkerComponent $downloader
 */
class ConsoleApplication extends yii\console\Application
{
}