<?php

namespace boldminded\craftfeedmemigrations\services;

use boldminded\craftfeedmemigrations\FeedMeMigrations;
use Craft;
use craft\feedme\Plugin;
use craft\feedme\records\FeedRecord;
use craft\helpers\StringHelper;

class Migration
{
    const REGEXES = [
        '/{"section":"(\d+)","entryType":"(\d+)"}/' => '{"section":"[sectionUID]","entryType":"[sectionTypeUID]"}',
        '/{"sectionId":"(\d+)","typeId":"(\d+)"}/' => '{"sectionId":"[sectionUID]","typeId":"[sectionTypeUID]"}',
    ];

    private string $migrationHash = '';

    public function create(string $uid): bool
    {
        $feeds = $this->getFeeds();

        if (!isset($feeds[$uid])) {
            $this->handleError('Unable to find Feed Me configuration');

            return false;
        }

        $feed = $feeds[$uid]->toArray();

        $updatedMappings = $this->updateMappings($feed);
        $migrationContent = $this->buildMigrationContent($feed, $updatedMappings);
        $this->setMigrationHash($migrationContent);

        if ($this->shouldNotCreateMigration($feed['uid'])) {
            $this->handleNotice(sprintf('Configuration has not changed for %s. No migration created.', $feed['name']));
            return true;
        }

        if (!$this->createMigrationStub($feed['name'])) {
            $this->handleError(sprintf('Unable to generate a migration file for %s.', $feed['name']));
            return false;
        }

        if (!$this->updateMigrationFile($feed, $migrationContent)) {
            $this->handleError(sprintf('Unable to update Feed Me migration file for %s.', $feed['name']));
            return false;
        }

        $this->updateHash($feed);
        $this->handleSuccess(sprintf('Migration created for %s', $feed['name']));

        return true;
    }

    private function shouldNotCreateMigration(string $feedUID): bool
    {
        $row = FeedRecord::find()
            ->select([
                'id',
                'uid',
                'hash',
            ])
            ->where(['uid' => $feedUID])
            ->one();

        return $row->getAttribute('hash') === $this->migrationHash;
    }

    private function updateHash(array $feed)
    {
        Craft::$app->db->createCommand()->update('{{%feedme_feeds}}', [
            'hash' => $this->migrationHash,
        ], [
            'uid' => $feed['uid'],
        ])->execute();
    }

    private function setMigrationHash(string $content): void
    {
        $this->migrationHash = md5($content);
    }

    private function buildMigrationContent(array $feed, array $updatedMappings): string
    {
        $duplicateHandle = $this->prepareJson($feed['duplicateHandle']);
        $elementGroup = $updatedMappings['elementGroup'] ?? $this->prepareJson($feed['elementGroup']);
        $fieldMapping = $updatedMappings['fieldMapping'] ?? $this->prepareJson($feed['fieldMapping']);
        $fieldUnique = $updatedMappings['fieldUnique'] ?? $this->prepareJson($feed['fieldUnique']);
        $siteId = $feed['siteId'] ?: 'null';
        $singleton = $feed['singleton'] ?: '0';
        $backup = $feed['backup'] ?: '0';
        $uid = $feed['uid'] ?? null;
        $sectionsVarExport = json_encode((new SectionTypes)->getDictionary(), JSON_PRETTY_PRINT);
        $migrationContent = '';

        if (Settings::get('debug') ?? false) {
            $migrationContent = '/**' . PHP_EOL . $sectionsVarExport . PHP_EOL . '*/' . PHP_EOL;
        }

        $migrationContent .= <<<EOF
        \$sections = (new SectionTypes)->getDictionary();
        Craft::\$app->db->createCommand()->upsert('{{%feedme_feeds}}', [
            'name' => '{$feed['name']}',
            'feedUrl' => '{$feed['feedUrl']}',
            'feedType' => '{$feed['feedType']}',
            'primaryElement' => '{$feed['primaryElement']}',
            'elementType' => '{$feed['elementType']}',
            'elementGroup' => '{$elementGroup}',
            'siteId' => '{$siteId}',
            'singleton' => '{$singleton}',
            'duplicateHandle' => '{$duplicateHandle}',
            'updateSearchIndexes' => '{$feed['updateSearchIndexes']}',
            'paginationNode' => '{$feed['paginationNode']}',
            'fieldMapping' => '{$fieldMapping}',
            'fieldUnique' => '{$fieldUnique}',
            'passkey' => '{$feed['passkey']}',
            'backup' => '{$backup}',
            'setEmptyValues' => '{$feed['setEmptyValues']}',
            'uid' => '{$uid}',
        ])->execute();
EOF;

        return $migrationContent;
    }

    private function updateMigrationFile(array $feed, string $migrationContent): bool
    {
        $migrationStub = (new LastMigration)->getFromFile();
        $fileContents = file_get_contents($migrationStub);

        $fileContents = (string) str_replace(
            [
                'use craft\db\Migration;',
                '// Place migration code here...',
                'return false;'
            ],
            [
                'use craft\db\Migration;' . PHP_EOL . 'use boldminded\craftfeedmemigrations\services\SectionTypes;',
                $migrationContent,
                'return true;'
            ],
            $fileContents
        );

        $fileContents = (string) preg_replace(
            '/echo "(.*) cannot be reverted\\.\\\\n";/um',
            'Craft::$app->db->createCommand()->delete(\'{{%feedme_feeds}}\', [\'uid\' => \'' . $feed['uid'] . '\'])->execute();',
            $fileContents
        );

        $fileContents = str_replace('@inheritdoc', '{@inheritdoc}', $fileContents);

        return boolval(file_put_contents($migrationStub, $fileContents));
    }

    private function updateMappings(array $feed): array
    {
        $mappings = [
            'fieldMapping' => json_encode($feed['fieldMapping']),
            'fieldUnique' => json_encode($feed['fieldUnique']),
            'elementGroup' => json_encode($feed['elementGroup']),
        ];

        $updatedMappings = [];
        $sectionTypes = new SectionTypes;

        foreach ($mappings as $fieldName => $mapping) {
            if (! $mapping) {
                continue;
            }

            foreach (self::REGEXES as $regex => $replacement) {
                preg_match_all($regex, $mapping, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $sectionId = intval($match[1] ?? 0);
                    $sectionTypeId = intval($feed['elementGroup'][$feed['elementType']]['entryType'] ?? 0);

                    list($sectionUID, $sectionTypeUID) = $sectionTypes->getSectionInfoById($sectionId, $sectionTypeId);

                    // Replace placeholders with PHP vars to be used in the migration to get the section and sectionType
                    // by ID, since section names can change between environments and throughout the dev process, but
                    // IDs remain the same.
                    $replacement = str_replace([
                        '[sectionUID]',
                        '[sectionTypeUID]',
                    ], [
                        sprintf('\' . $sections[\'%s\'][\'id\'] . \'', $sectionUID),
                        sprintf('\' . $sections[\'%s\'][\'types\'][\'%s\'][\'id\'] . \'', $sectionUID, $sectionTypeUID),
                    ], $replacement);

                    $updatedMappings[$fieldName] = preg_replace($regex, $replacement, $mapping);
                    $updatedMappings[$fieldName] = $this->prepareJson($updatedMappings[$fieldName]);
                }
            }
        }

        return $updatedMappings;
    }

    private function createMigrationStub(string $feedName): bool
    {
        $rootPath = Craft::getAlias('@root');
        $migrationsPath = Craft::getAlias('@contentMigrations');

        if (!file_exists($migrationsPath)) {
            mkdir($migrationsPath, 0777); // @todo, Craft/Yii way of doing this? Const for perms?
        }

        $command = sprintf(
            'php %s/craft migrate/create feedme_%s',
            $rootPath,
            StringHelper::slugify($feedName, '_'),
            //StringHelper::randomString(6)
        );

        $output = [];
        $result = 0;
        exec($command, $output, $result);

        return $result === 0;
    }

    private function getFeeds(): array
    {
        $feeds = Plugin::$plugin->feeds->getFeeds();
        $dictionary = [];

        foreach ($feeds as $feed) {
            $dictionary[$feed->uid] = $feed;
        }

        return $dictionary;
    }

    private function prepareJson(mixed $value): string
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        return str_replace('\\', '\\\\', $value);
    }

    private function handleError(string $message): void
    {
        Craft::$app->getSession()->setError($message);
    }

    private function handleSuccess(string $message): void
    {
        Craft::$app->getSession()->setSuccess($message);
    }

    private function handleNotice(string $message): void
    {
        Craft::$app->getSession()->setNotice($message);
    }
}
