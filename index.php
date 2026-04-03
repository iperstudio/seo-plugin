<?php

use Kirby\Cms\App as Kirby;

Kirby::plugin('iperstudio/seo-plugin', [
    'blueprints' => [
        'seo/page' => __DIR__ . '/blueprints/seo-page.yml',
        'sections/seo-fields' => __DIR__ . '/blueprints/sections/seo-fields.yml',
    ],
    'snippets' => [
        'seo/meta' => __DIR__ . '/snippets/meta.php',
    ],
]);
