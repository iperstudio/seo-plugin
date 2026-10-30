<?php $meta = Iperstudio\Seo\Seo::metadata($page ?? page()) ?>
<?php if ($meta['title'] !== ''): ?>
<title><?= esc($meta['title']) ?></title>
<?php endif ?>
<?php if ($meta['description'] !== ''): ?>
<meta name="description" content="<?= esc($meta['description'], 'attr') ?>">
<?php endif ?>
<?php if ($meta['renderCanonical']): ?>
<link rel="canonical" href="<?= esc($meta['canonical'], 'attr') ?>">
<?php endif ?>
<?php foreach ($meta['alternates'] as $language => $href): ?>
<link rel="alternate" hreflang="<?= esc($language, 'attr') ?>" href="<?= esc($href, 'attr') ?>">
<?php endforeach ?>
<?php if ($meta['noindex']): ?>
<meta name="robots" content="noindex, follow">
<?php endif ?>
<meta property="og:type" content="<?= esc($meta['ogType'], 'attr') ?>">
<meta property="og:site_name" content="<?= esc($meta['siteName'], 'attr') ?>">
<meta property="og:url" content="<?= esc($meta['canonical'], 'attr') ?>">
<meta property="og:title" content="<?= esc($meta['ogTitle'], 'attr') ?>">
<?php if ($meta['ogDescription'] !== ''): ?>
<meta property="og:description" content="<?= esc($meta['ogDescription'], 'attr') ?>">
<?php endif ?>
<?php if ($image = $meta['image']): ?>
<meta property="og:image" content="<?= esc($image->url(), 'attr') ?>">
<meta property="og:image:width" content="<?= $image->width() ?>">
<meta property="og:image:height" content="<?= $image->height() ?>">
<?php if ($image->alt()->isNotEmpty()): ?><meta property="og:image:alt" content="<?= esc($image->alt(), 'attr') ?>"><?php endif ?>
<?php endif ?>
<meta name="twitter:card" content="<?= $image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= esc($meta['ogTitle'], 'attr') ?>">
<?php if ($meta['ogDescription'] !== ''): ?>
<meta name="twitter:description" content="<?= esc($meta['ogDescription'], 'attr') ?>">
<?php endif ?>
<?php if ($image): ?><meta name="twitter:image" content="<?= esc($image->url(), 'attr') ?>"><?php endif ?>
