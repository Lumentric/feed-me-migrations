<?php

namespace boldminded\craftfeedmemigrations;

use boldminded\craftfeedmemigrations\services\Migration;
use boldminded\craftfeedmemigrations\services\Settings;
use Craft;
use craft\base\Plugin;
use craft\feedme\events\FeedEvent;
use craft\feedme\services\Feeds;
use yii\base\Event;

/**
 * Feed Me Migrations plugin
 *
 * @method static FeedMeMigrations getInstance()
 * @author BoldMinded, LLC <support@boldminded.com>
 * @copyright BoldMinded, LLC
 * @license https://craftcms.github.io/license/ Craft License
 */
class FeedMeMigrations extends Plugin
{
    public bool $hasCpSettings = false;
    public bool $hasCpSection = false;
    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => [
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
    }

    private function attachEventHandlers(): void
    {
        Event::on(Feeds::class, Feeds::EVENT_AFTER_SAVE_FEED, function(FeedEvent $event) {
            $feed = $event->feed;
            $request = Craft::$app->getRequest();
            $action = $request->getBodyParam('action');

            // Create migrations only on the last save action
            if ($action === 'feed-me/feeds/save-and-review-feed' && Settings::get('enable-auto') !== false) {
                (new Migration)->create($feed->uid);
            }
        });

        Craft::$app->view->hook('cp.layouts.base', function(&$context, &$handled) {
            // Only add JS to the Feed Me settings page to enable manual migrations
            if (isset($context['feeds'])
                && str_contains($context['docTitle'], 'Feed Me')
                && Settings::get('enable-manual') !== false
            ) {
                /** @var craft\web\View $view */
                $view = $context['view'];
                $path = Craft::getAlias('@boldminded/craftfeedmemigrations') . '/scripts/feed-me-migrations.js';
                $view->js[] = [file_get_contents($path)];

                $handled = true;
            }
        });
    }
}
