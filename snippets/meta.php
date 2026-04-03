<?php

use Kirby\Toolkit\Str;

$page = $page ?? page();
$site = site();

$normalizeRaw = function (?string $value): string {
  $raw = (string)$value;

  // Guard against malformed values accidentally saved as `Uuid: ...`.
  if (preg_match('/^\s*Uuid:\s*[A-Za-z0-9_-]+\s*$/i', $raw)) {
    return '';
  }

  return $raw;
};

$clean = function (?string $value, int $max = 0) use ($normalizeRaw): string {
  $text = trim(preg_replace('/\s+/u', ' ', strip_tags($normalizeRaw($value))));
  if ($max > 0) {
    $text = Str::short($text, $max);
  }
  return $text;
};

$applyAffixes = function (
  string $value,
  ?string $prefix,
  ?string $suffix,
  string $separator = ' | '
): string {
  $value = trim($value);
  $prefix = trim((string)$prefix);
  $suffix = trim((string)$suffix);
  if ($value === '') {
    return '';
  }

  $parts = [];
  if ($prefix !== '' && strcasecmp($prefix, $value) !== 0) {
    $parts[] = $prefix;
  }
  $parts[] = $value;
  if ($suffix !== '' && strcasecmp($suffix, $value) !== 0) {
    $parts[] = $suffix;
  }

  return implode($separator, $parts);
};

$normalizeFieldList = function ($fields, array $fallback): array {
  if (is_string($fields)) {
    $fields = array_map('trim', explode(',', $fields));
  }
  if (!is_array($fields)) {
    return $fallback;
  }

  $normalized = array_values(array_filter(array_map(static fn ($field) => trim((string)$field), $fields)));
  return $normalized !== [] ? $normalized : $fallback;
};

$firstNonEmptyField = function ($model, array $fields) use ($normalizeRaw): string {
  foreach ($fields as $fieldName) {
    $value = $normalizeRaw($model->content()->get($fieldName)->value());
    if (trim(strip_tags((string)$value)) !== '') {
      return (string)$value;
    }
  }

  return '';
};

$titlePrefix = option('iperstudio.seo.title.prefix', '');
$titleSuffix = option('iperstudio.seo.title.suffix', '');
$titleSeparator = option('iperstudio.seo.title.separator', ' | ');
$titleMaxLength = (int)option('iperstudio.seo.title.maxLength', 0);

$ogTitlePrefix = option('iperstudio.seo.ogTitle.prefix', $titlePrefix);
$ogTitleSuffix = option('iperstudio.seo.ogTitle.suffix', $titleSuffix);
$ogTitleSeparator = option('iperstudio.seo.ogTitle.separator', $titleSeparator);
$ogTitleMaxLength = (int)option('iperstudio.seo.ogTitle.maxLength', 0);

$descriptionMaxLength = (int)option('iperstudio.seo.description.maxLength', 160);
$ogDescriptionMaxLength = (int)option('iperstudio.seo.ogDescription.maxLength', 200);

$baseTitle = $clean($page->seoTitle()->or($page->title())->or($site->title())->value(), $titleMaxLength);
$title = $applyAffixes($baseTitle, $titlePrefix, $titleSuffix, $titleSeparator);

$defaultDescriptionFields = $normalizeFieldList(
  option('iperstudio.seo.defaultDescriptionContent', ['seoDescription']),
  ['seoDescription']
);

// Explicit SEO description always wins over defaults.
$descriptionRaw = $normalizeRaw($page->seoDescription()->value());
if (trim(strip_tags((string)$descriptionRaw)) === '') {
  $descriptionRaw = $firstNonEmptyField($page, $defaultDescriptionFields);
}

$description = $clean($descriptionRaw, $descriptionMaxLength);

$baseOgTitle = $clean($page->ogTitle()->or($page->seoTitle())->or($page->title())->or($site->title())->value(), $ogTitleMaxLength);
$ogTitle = $applyAffixes($baseOgTitle, $ogTitlePrefix, $ogTitleSuffix, $ogTitleSeparator);

$ogDescriptionRaw = $normalizeRaw($page->ogDescription()->value());
if (trim(strip_tags((string)$ogDescriptionRaw)) === '') {
  $ogDescriptionRaw = $description;
}
$ogDescription = $clean($ogDescriptionRaw, $ogDescriptionMaxLength);

$ogImage = $page->ogImage()->toFile()
  ?? $page->hero()->toFile()
  ?? $page->mainImage()->toFile()
  ?? $page->image()
  ?? $site->ogImage()->toFile()
  ?? $site->image();

$ogImageUrl = $ogImage?->url();
$ogType = in_array($page->intendedTemplate()->name(), ['articles-item', 'articles-more'], true)
  ? 'article'
  : 'website';
?>

<?php if ($title !== ''): ?>
  <title><?= esc($title) ?></title>
<?php endif ?>

<?php if ($description !== ''): ?>
  <meta name="description" content="<?= esc($description, 'attr') ?>">
<?php endif ?>

<meta property="og:type" content="<?= $ogType ?>">
<meta property="og:url" content="<?= esc($page->url(), 'attr') ?>">
<meta property="og:title" content="<?= esc($ogTitle !== '' ? $ogTitle : $title, 'attr') ?>">
<?php if ($ogDescription !== ''): ?>
  <meta property="og:description" content="<?= esc($ogDescription, 'attr') ?>">
<?php endif ?>
<?php if ($ogImageUrl): ?>
  <meta property="og:image" content="<?= esc($ogImageUrl, 'attr') ?>">
<?php endif ?>

<meta name="twitter:card" content="<?= $ogImageUrl ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= esc($ogTitle !== '' ? $ogTitle : $title, 'attr') ?>">
<?php if ($ogDescription !== ''): ?>
  <meta name="twitter:description" content="<?= esc($ogDescription, 'attr') ?>">
<?php endif ?>
<?php if ($ogImageUrl): ?>
  <meta name="twitter:image" content="<?= esc($ogImageUrl, 'attr') ?>">
<?php endif ?>
