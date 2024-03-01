# Feed Me Migrations

Migrations for Feed Me configurations

## Requirements

This plugin requires Craft CMS 4.6.0 or later, Feed Me 5.4.0 or later, and PHP 8.0.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Feed Me Migrations”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require boldminded/craft-feed-me-migrations

# tell Craft to install the plugin
./craft plugin/install feed-me-migrations
```

## Config Options

Configuration is managed entirely by an `config/feed-me-migrations.php` file. Options available:

```php
<?php

return [
    'debug' => false, // Adds a small snapshot of SectionTypes to the migration file
    'enable' => true,
    'enable-auto' => true,
    'enable-manual' => true,
];
```
