<?php

namespace tests\unit\models;

use app\components\YoutubeWorkerComponent;

class YoutubedownloaderTest extends \Codeception\Test\Unit
{
    public function testDownloaderLoadUrl()
    {
        $oDownloader = new YoutubeWorkerComponent([]);
        $oDownloader->loadUrl("https://pikabu.ru/story/kai_len__tseni_poka_tsel_official_video_7691452");

        expect_that( $oDownloader->isValidTask() === true );
    }

}
