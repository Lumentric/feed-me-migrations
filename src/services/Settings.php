<?php

namespace boldminded\craftfeedmemigrations\services;

class Settings
{
    public static function getFromFile(): array
    {
        return [
            'debug' => false, // Adds a small snapshot of SectionTypes to the migration file
            'enable' => true,
            'enable-auto' => false,
            'enable-manual' => true,
        ];
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
