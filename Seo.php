<?php

namespace Iperstudio\Seo;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;

final class Seo
{
    public static function option(App $kirby, string $key, mixed $fallback = null): mixed
    {
        return $kirby->option('iperstudio.seo-plugin.' . $key)
            ?? $kirby->option('iperstudio.seo.' . $key, $fallback);
    }

    public static function clean(mixed $value, int $max = 0): string
    {
        $text = (string)$value;
        if (preg_match('/^\s*Uuid:\s*[A-Za-z0-9_-]+\s*$/i', $text)) return '';
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        return $max > 0 ? Str::short($text, $max) : $text;
    }

    public static function indexable(Page $page, ?string $language = null): bool
    {
        if ($page->isDraft() || $page->isErrorPage()) return false;
        foreach ($page->parents() as $parent) if ($parent->isDraft()) return false;
        if ($language !== null && $page->kirby()->multilang() && !$page->translation($language)->exists()) return false;
        if ($page->content($language)->get('seoNoindex')->isTrue()) return false;
        $filter = self::option($page->kirby(), 'pageFilter');
        return !is_callable($filter) || $filter($page, $language) === true;
    }

    public static function alternates(Page $page): array
    {
        $kirby = $page->kirby();
        if (!$kirby->multilang()) return [];
        $links = [];
        foreach ($kirby->languages() as $language) {
            $code = $language->code();
            if (self::indexable($page, $code)) $links[$code] = $page->url($code);
        }
        $default = $kirby->defaultLanguage()->code();
        if (isset($links[$default])) $links['x-default'] = $links[$default];
        return $links;
    }

    private static function affix(string $title, string $prefix, string $suffix, string $separator): string
    {
        if ($title === '') return '';
        $parts = [];
        if ($prefix !== '' && strcasecmp($prefix, $title) !== 0) $parts[] = $prefix;
        $parts[] = $title;
        if ($suffix !== '' && strcasecmp($suffix, $title) !== 0) $parts[] = $suffix;
        return implode($separator, $parts);
    }

    private static function image(Page $page, ?string $language): ?File
    {
        $content = $page->content($language);
        $image = $content->get('ogImage')->toFile();
        if ($image?->type() === 'image') return $image;

        $fallback = self::option($page->kirby(), 'imageFallback');
        if (is_callable($fallback)) {
            $image = $fallback($page, $language);
            if ($image instanceof File && $image->type() === 'image') return $image;
        } elseif ($fallback !== false) {
            // Preserve the 1.0 fallback order unless a project supplies its own policy.
            foreach (['hero', 'mainImage'] as $field) {
                $image = $content->get($field)->toFile();
                if ($image?->type() === 'image') return $image;
            }
            if ($image = $page->image()) return $image;
        }

        $site = $page->site();
        $image = $site->content($language)->get('ogImage')->toFile();
        if ($image?->type() === 'image') return $image;
        return $fallback === null ? $site->image() : null;
    }

    public static function metadata(Page $page): array
    {
        $kirby = $page->kirby();
        $language = $kirby->language()?->code();
        $content = $page->content($language);
        $option = static fn (string $key, mixed $fallback = null) => self::option($kirby, $key, $fallback);

        $prefix = self::clean($option('title.prefix', ''));
        $suffix = self::clean($option('title.suffix', ''));
        $separator = (string)$option('title.separator', ' | ');
        $rawTitle = $content->get('seoTitle')->or($content->get('title'))->or($page->site()->title());
        $baseTitle = self::clean($rawTitle, (int)$option('title.maxLength', 0));
        $title = self::affix($baseTitle, $prefix, $suffix, $separator);

        $description = self::clean($content->get('seoDescription'));
        if ($description === '') {
            $fallback = $option('descriptionFallback');
            if (is_callable($fallback)) $description = self::clean($fallback($page, $language));
        }
        if ($description === '') {
            $fields = $option('defaultDescriptionContent', ['seoDescription']);
            if (is_string($fields)) $fields = explode(',', $fields);
            if (!is_array($fields)) $fields = ['seoDescription'];
            foreach ($fields as $field) {
                $description = self::clean($content->get(trim((string)$field)));
                if ($description !== '') break;
            }
        }
        $description = self::clean($description, (int)$option('description.maxLength', 160));

        $ogBase = self::clean($content->get('ogTitle')->or($rawTitle), (int)$option('ogTitle.maxLength', 0));
        $ogTitle = self::affix(
            $ogBase,
            self::clean($option('ogTitle.prefix', $prefix)),
            self::clean($option('ogTitle.suffix', $suffix)),
            (string)$option('ogTitle.separator', $separator)
        );
        $ogDescription = self::clean($content->get('ogDescription')) ?: $description;
        $ogDescription = self::clean($ogDescription, (int)$option('ogDescription.maxLength', 200));
        $image = self::image($page, $language);
        $ogType = $option('ogType');
        if (is_callable($ogType)) $ogType = $ogType($page, $language);
        $ogType ??= in_array($page->intendedTemplate()->name(), ['articles-item', 'articles-more'], true) ? 'article' : 'website';

        $canonical = $page->url($language);
        if ($language !== null && !$page->translation($language)->exists()) {
            $canonical = $page->url($kirby->defaultLanguage()->code());
        }
        $enabled = $option('indexing', true);
        if (is_callable($enabled)) $enabled = $enabled($kirby);
        $preview = $kirby->request()->get('_token') !== null || $kirby->request()->get('_version') !== null;
        $noindex = !$enabled || !self::indexable($page, $language) || $preview;

        return compact('title', 'description', 'ogTitle', 'ogDescription', 'image', 'ogType', 'canonical', 'noindex') + [
            'alternates' => $option('alternates', true) ? self::alternates($page) : [],
            'siteName' => self::clean($option('siteName', $page->site()->title())),
            'renderCanonical' => (bool)$option('canonical', true),
        ];
    }
}
