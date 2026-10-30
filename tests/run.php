<?php

require __DIR__ . '/bootstrap.php';

use Iperstudio\Seo\Seo;

$kirby = fixture();
$kirby->setCurrentLanguage('it');
$page = $kirby->site()->find('article');
$meta = Seo::metadata($page);
check($meta['title'] === 'Article' && $meta['description'] === '', '1.0 title and description defaults are preserved');
check($meta['ogType'] === 'article' && $meta['image']->filename() === 'hero.png', '1.0 article type and image fallback order are preserved');
check($meta['canonical'] === $page->url('it') && !$meta['noindex'], 'Public pages have a canonical URL and remain indexable');
check(array_keys($meta['alternates']) === ['en', 'it', 'x-default'] || array_keys($meta['alternates']) === ['it', 'en', 'x-default'], 'Translated pages have reciprocal alternates and x-default');
check(array_keys(Seo::alternates($kirby->site()->find('italian-only'))) === ['it', 'x-default'], 'Missing translations are excluded from alternates');
$kirby->setCurrentLanguage('en');
$meta = Seo::metadata($kirby->site()->find('italian-only'));
check($meta['noindex'] && $meta['canonical'] === $kirby->site()->find('italian-only')->url('it'), 'Missing translation is noindex and canonical points to the default language');
$split = $kirby->site()->find('split');
check(!Seo::indexable($split, 'it') && Seo::indexable($split, 'en'), 'Noindex applies to its translation only');
check(Seo::alternates($split) === ['en' => $split->url('en')], 'Noindex translations and their x-default link are omitted');
check(!Seo::indexable($kirby->site()->find('noindex')) && !Seo::indexable($kirby->site()->errorPage()), 'Editorial noindex and error pages are excluded');
$draft = $kirby->site()->draft('draft');
$child = new Kirby\Cms\Page(['slug' => 'child', 'parent' => $draft, 'num' => 1]);
check(!Seo::indexable($draft) && !Seo::indexable($child), 'Drafts and descendants are excluded');

$kirby = fixture(['iperstudio.seo' => [
    'title' => ['prefix' => 'Studio', 'suffix' => 'Legacy', 'separator' => ' / '],
    'ogTitle' => ['suffix' => 'Social', 'separator' => ' - '],
    'defaultDescriptionContent' => ['intro'],
]]);
$kirby->setCurrentLanguage('it');
$meta = Seo::metadata($kirby->site()->find('article'));
check($meta['title'] === 'Studio / Article / Legacy' && $meta['ogTitle'] === 'Studio - Article - Social', 'Legacy nested title and separate OG options work without migration');
check($meta['description'] === 'Fallback introduction', 'Legacy description field fallback works');

$kirby = fixture([
    'iperstudio.seo.title.suffix' => 'Legacy',
    'iperstudio.seo-plugin.title.suffix' => 'New',
    'iperstudio.seo-plugin.descriptionFallback' => static fn ($page, $language) => "Callback $language",
    'iperstudio.seo-plugin.imageFallback' => static fn ($page) => $page->file('share.png'),
    'iperstudio.seo-plugin.ogType' => static fn () => 'profile',
]);
$kirby->setCurrentLanguage('it');
$page = $kirby->site()->find('article');
$meta = Seo::metadata($page);
check($meta['title'] === 'Article | New', 'Canonical option prefix takes precedence over the legacy prefix');
check($meta['description'] === 'Callback it' && $meta['image']->filename() === 'share.png' && $meta['ogType'] === 'profile', 'Project policies are configurable through callbacks');
$custom = $page->clone(['content' => array_replace($page->content()->toArray(), [
    'seotitle' => '<b>Custom & "Title"</b>', 'seodescription' => '<p>Custom &amp; description</p>', 'ogimage' => 'main.png',
])]);
$meta = Seo::metadata($custom);
check($meta['title'] === 'Custom & "Title" | New' && $meta['description'] === 'Custom & description', 'Editorial values override callbacks and markup is normalized');
check($meta['image']->filename() === 'main.png', 'Editorial share image overrides the project callback');
$html = snippet('seo/meta', ['page' => $custom], true);
$dom = new DOMDocument;
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);
check($xpath->query('//title')->length === 1 && $xpath->query('//link[@rel="canonical"]')->length === 1, 'Snippet renders a single title and canonical URL');
check(!str_contains($html, '<b>') && str_contains($html, '&quot;') && str_contains($html, '&amp;'), 'Metadata and attributes are escaped');
$long = str_repeat('description ', 30);
check(Seo::metadata($page->clone(['content' => ['title' => 'Long', 'seodescription' => $long]]))['description'] === Kirby\Toolkit\Str::short(trim($long), 160), 'Default 160-character truncation is preserved');
check(Seo::clean('Uuid: malformed') === '', 'Malformed UUID metadata is ignored');

$kirby = fixture(['iperstudio.seo-plugin' => ['indexing' => false, 'imageFallback' => false, 'description.maxLength' => 0]], false);
$page = $kirby->site()->find('article');
$meta = Seo::metadata($page);
check($meta['noindex'] && $meta['alternates'] === [] && $meta['image'] === null, 'Single-language sites and explicit indexing/image controls work');
check(Seo::metadata($page->clone(['content' => ['title' => 'Long', 'seodescription' => $long]]))['description'] === trim($long), 'Truncation can be disabled');
$html = snippet('seo/meta', ['page' => $page], true);
check(str_contains($html, 'name="robots" content="noindex, follow"'), 'Disabled indexing renders a robots noindex tag');

$kirby = fixture(['iperstudio.seo-plugin' => ['canonical' => false, 'alternates' => false]]);
$page = $kirby->site()->find('article');
$html = snippet('seo/meta', ['page' => $page], true);
check(!str_contains($html, 'rel="canonical"') && !str_contains($html, 'rel="alternate"'), 'Existing head templates can opt out of added canonical and alternate links');

$_GET['_token'] = 'preview';
$kirby = fixture();
check(Seo::metadata($kirby->site()->homePage())['noindex'], 'Preview requests are noindex');
unset($_GET['_token']);

ob_end_flush();
