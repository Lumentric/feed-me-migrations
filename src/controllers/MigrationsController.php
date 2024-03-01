<?php

namespace boldminded\craftfeedmemigrations\controllers;

use boldminded\craftfeedmemigrations\services\Migration;
use Craft;
use craft\web\Controller;
use craft\feedme\Plugin;
use yii\web\Response;

class MigrationsController extends Controller
{
    public function actionCreate(): Response
    {
        $request = Craft::$app->getRequest();

        $feedId = $request->getParam('feedId');
        $feed = Plugin::$plugin->feeds->getFeedById($feedId);

        (new Migration)->create($feed->uid);

        return $this->redirect('feed-me/feeds');
    }
}
