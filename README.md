# Iperstudio SEO Plugin

Provides:

- `seo/page` tab blueprint
- `sections/seo-fields` reusable blueprint section
- `seo/meta` snippet for frontend meta tags
- base `title` tag + meta description + Open Graph/Twitter tags

****

## Installation

### Composer

Add to your project `composer.json` then run `composer update`.

```
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:iperstudio/seo-plugin.git"
    }
  ],
  "require": {
    "iperstudio/seo-plugin": "^1.0"
  },
```

## Setup

In page blueprint:

```yaml
tabs:
  content: ...
  seo: seo/page
```

Or inside an existing tab section:

```yaml
sections:
  seo:
    extends: sections/seo-fields
```

In `<head>`:

```php
<?php snippet('seo/meta') ?>
```

## Options

Add optional prefix/suffix for `title` and `og:title`:

```php
return [
  'iperstudio.seo.title.suffix' => 'Oltreoceano',
  'iperstudio.seo.title.separator' => ' | ',
  // 0 = no truncation
  // 'iperstudio.seo.title.maxLength' => 0,
  // 'iperstudio.seo.ogTitle.maxLength' => 0,
  // 'iperstudio.seo.description.maxLength' => 160,
  // 'iperstudio.seo.ogDescription.maxLength' => 200,
  // Description fallback fields (first non-empty wins)
  // Note: `seoDescription` (if filled) always wins over these defaults.
  // 'iperstudio.seo.defaultDescriptionContent' => ['seoDescription'],
  // Optional separate OG overrides (fallback to title options if omitted)
  // 'iperstudio.seo.ogTitle.prefix' => '',
  // 'iperstudio.seo.ogTitle.suffix' => 'Oltreoceano',
  // 'iperstudio.seo.ogTitle.separator' => ' | ',
];
```

## License

MIT

## Credits

- [Iperstudio](https://github.com/iperstudio)
