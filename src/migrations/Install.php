<?php

namespace boldminded\craftfeedmemigrations\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

class Install extends Migration
{
    public function safeUp(): bool
    {
        // Make the uid column unique so upserts work correctly.
        Craft::$app->db->createCommand()->createIndex(
            'uid_unique',
            '{{%feedme_feeds}}',
            'uid',
            true
        )->execute();

        // Add new column to store a hash of the updates so new migrations are not created if no updates were made.
        if (!$this->db->columnExists('{{%feedme_feeds}}', 'hash')) {
            Craft::$app->db->createCommand()->addColumn(
                '{{%feedme_feeds}}',
                'hash',
                'varchar(255)'
            )->execute();
        }

        return true;
    }

    public function safeDown(): bool
    {
        // Because dropIndex() does not work. https://github.com/yiisoft/yii2/issues/13196
        Craft::$app->db->createCommand('ALTER TABLE {{%feedme_feeds}} DROP CONSTRAINT uid_unique')->execute();

        if ($this->db->columnExists('{{%feedme_feeds}}', 'hash')) {
            Craft::$app->db->createCommand()->dropColumn(
                '{{%feedme_feeds}}',
                'hash',
            )->execute();
        }

        return true;
    }
}
