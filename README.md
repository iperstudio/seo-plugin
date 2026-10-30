# Iperstudio SEO Plugin

SEO fields for the Kirby Panel and a single `seo/meta` snippet for titles, descriptions, canonical URLs, language alternates, Open Graph and Twitter cards.

Requires PHP 8.2+, mbstring and Kirby 5 or 6. Tested locally with Kirby 5.3.1, 5.6.1 and 6.0.0-beta.1/beta.2; Kirby 6 support is provisional while it is in beta.

## Installation

Until the package is listed on Packagist, add the repository to the project's `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/iperstudio/seo-plugin" }
  ]
}
```

Then install a tagged release:

```sh
composer require iperstudio/seo-plugin:^1.1
```

After publication on Packagist, the repository entry is no longer necessary. The Kirby Composer installer places the plugin in `site/plugins/seo-plugin`.

## Setup

Add the SEO tab to a page blueprint:

```yaml
tabs:
  content: ...
  seo: seo/page
```

Or extend `sections/seo-fields` inside an existing tab. Include the snippet once in the head:

```php
<?php snippet('seo/meta') ?>
```

The optional `seoNoindex` field excludes a page from search engines and from the Iperstudio sitemap plugin. It is non-translatable by default; a project can override the blueprint when it needs per-language control. Neither plugin requires the other.

## Configuration

Use the registered prefix `iperstudio.seo-plugin` for new configurations:

```php
return [
    'iperstudio.seo-plugin' => [
        'title' => ['suffix' => 'Studio', 'separator' => ' | '],
        'defaultDescriptionContent' => ['intro', 'text'],
        'description' => ['maxLength' => 160],
    ],
];
```

Flat dotted keys also work. Existing `iperstudio.seo` options remain supported. For each option, the new prefix takes precedence; false, zero and empty strings retain their meaning. Options are read at runtime rather than registered with defaults that would mask the legacy configuration.

| Option | Default | Purpose |
| --- | --- | --- |
| `title.prefix`, `title.suffix` | `''` | Affixes for the HTML title |
| `title.separator` | `' \| '` | Separator between title parts |
| `title.maxLength` | `0` | Base title limit; zero disables truncation |
| `ogTitle.prefix`, `ogTitle.suffix`, `ogTitle.separator` | Title settings | Separate Open Graph formatting |
| `ogTitle.maxLength` | `0` | Base Open Graph title limit |
| `description.maxLength` | `160` | Description limit, followed by Kirby's ellipsis when truncated |
| `ogDescription.maxLength` | `200` | Open Graph description limit |
| `defaultDescriptionContent` | `['seoDescription']` | Ordered fallback fields; also accepts a comma-separated string |
| `descriptionFallback` | `null` | Callback `(Page $page, ?string $language): string` before fallback fields |
| `imageFallback` | `null` | Callback `(Page $page, ?string $language): ?File`, or `false` to disable implicit image choices |
| `ogType` | `null` | String or callback `(Page $page, ?string $language): string` |
| `siteName` | Site title | `og:site_name` |
| `pageFilter` | `null` | Callback `(Page $page, ?string $language): bool`; must return true to allow indexing |
| `indexing` | `true` | Boolean or callback `(App $kirby): bool`; set false on staging |
| `canonical` | `true` | Emit the canonical link |
| `alternates` | `true` | Emit translated alternates and x-default |

Editorial `seoDescription` always wins over callbacks and fallback fields. HTML is normalized and all snippet values are escaped. Title affixes are not automatically changed to the site name. Existing title and truncation defaults are preserved.

The default share image order remains `ogImage`, `hero`, `mainImage`, first page image, site `ogImage`, first site image. Invalid non-image files are skipped. A custom `imageFallback` replaces the implicit page/first-site-image choices, while explicit page and site `ogImage` remain available. This lets projects avoid selecting unrelated avatars or use a video poster without embedding their content model in the plugin.

The default Open Graph type remains `article` for `articles-item` and `articles-more`, otherwise `website`; use `ogType` for other content models.

Drafts, descendants of drafts, error pages, missing translations, preview requests (`_token` or `_version`) and pages marked `seoNoindex` are noindex. Canonical URLs omit query parameters and use the default-language URL when the requested translation is missing. Alternate clusters contain only existing eligible translations and use the default language for x-default when it is eligible.

## Upgrading from 1.0

Version 1.1 is a compatible minor release. Existing snippets, blueprint names, stored content, legacy options and defaults remain supported. No content migration is needed.

Canonical and alternate links are new output. If your head already renders them, disable `canonical` and/or `alternates`, or remove the duplicate tags from your head. Noindex handling and decoded HTML entities also improve the resulting markup; inspect the rendered head when upgrading.

Update only this dependency and review the lock-file diff:

```sh
composer update iperstudio/seo-plugin
```

A `^1.0` constraint accepts 1.1; an exact `1.0.3` or `~1.0.0` constraint must be changed to opt in. Removing legacy options or changing established defaults is reserved for a future major release.

## Development and releases

```sh
composer install
composer test
```

To use an existing Kirby checkout without installing dependencies:

```sh
KIRBY_BOOTSTRAP=/absolute/path/to/kirby/bootstrap.php php tests/run.php
```

Tests use disposable fixture sites and do not modify real content. CI covers PHP 8.2/8.4 with Kirby 5 and PHP 8.4 with Kirby 6 beta. Run `composer validate`, update the changelog and `version` together, then create the matching `v1.1.0` tag when releasing. The explicit version supports Kirby's update checks for ZIP installations; it must match the Git tag. Configure Packagist's repository webhook after submitting the public repository.

## License

MIT. See [LICENSE.md](LICENSE.md).
