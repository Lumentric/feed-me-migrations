<?php

namespace boldminded\craftfeedmemigrations\services;

class LastMigration
{
    public function getFromFile(): ?string
    {
        $migrationsPath = \Craft::getAlias('@contentMigrations');
        $iterator = new \DirectoryIterator($migrationsPath);
        $newestTime = 0;
        $newestFile = null;

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $modifiedTime = $file->getMTime();

                if ($modifiedTime > $newestTime) {
                    $newestFile = $file->getPathName();
                    $newestTime = $modifiedTime;
                }
            }
        }

        return $newestFile;
    }
}
