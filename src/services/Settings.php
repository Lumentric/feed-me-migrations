<?php

namespace boldminded\craftfeedmemigrations\services;

use Craft;

class Settings
{
    public static function getFromFile(): array
    {
        // Don't need a Settings model. KISS.
        return include Craft::getAlias('@config') . '/feed-me-migrations.php';
    }

    public static function get(string $name): mixed
    {
        $settings = self::getFromFile();

        if (array_key_exists($name, $settings)) {
            return $settings[$name];
        }

        return null;
    }
}
