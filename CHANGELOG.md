# Changelog

## 1.1.0 — Unreleased

- Preserve all `iperstudio.seo` options and support the registered `iperstudio.seo-plugin` prefix with per-option precedence.
- Add canonical URLs, reciprocal language alternates, x-default, noindex controls and a Panel noindex field.
- Exclude drafts, error pages, missing translations and preview requests from indexing.
- Add configurable description/image fallbacks, page filtering and Open Graph type.
- Preserve existing title affixes, description limits, image fallback order and article type defaults.
- Normalize HTML entities and skip non-image share files.
- Declare Kirby 5/6 compatibility, including provisional Kirby 6 beta support.
- Add isolated regression tests, CI and upgrade documentation.

Review existing head templates for duplicate canonical/alternate links before updating. No content migration is required.
