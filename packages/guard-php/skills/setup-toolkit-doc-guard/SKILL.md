---
name: setup-toolkit-doc-guard
description: Configure Guard Markdown document structure policies for a PHP project, including migration from legacy doc configuration.
---

# Set up Guard Markdown document structure policies

Use `k-kinzal/guard-php` and `vendor/bin/guard`. The Markdown document structure checks are policies in the unified Collect → Policy → Report pipeline. Read [the Guard configuration](../../docs/configuration.md) and [the policy reference](../../docs/documents.md) for configuration semantics.

Inspect the project's existing `guard.yaml`, Composer scripts, and source roots before changing setup. If no policy exists, run `vendor/bin/guard init`; it detects matching presets and migrates existing legacy files. If a policy already exists, update its `documentation` section or imports within the requested scope instead of overwriting it.

Declare the current heading order under `documentation.files`, using quoted ATX notation. Set `documentation.scan` to the intended document scope and `documentation.exclude` for generated discovery paths. `guard init` captures README headings; additional documents can be read with `Guard\Collect\Markdown\Parsing\HeadingParser`. Preserve document content during adoption. Treat existing heading declarations as constraints; do not regenerate them to hide a violation. Keep `max_level: 6` unless the requested document semantics require a shallower boundary.

Add or retain a Composer `guard` script running `guard check`, and include `@guard` in the existing lint sequence without removing other gates. Use `guard check --format=json` for machine-readable output and `guard apply --dry-run` to inspect configuration repairs. Source metrics, directory contents and document headings require manual changes.

Validate with `vendor/bin/guard check`. Success and recommendations exit 0, required violations exit 1, invalid configuration or operational errors exit 2. The standalone `loc-guard`, `tree-guard` and `doc-guard` executables no longer exist. Legacy YAML templates in this skill, where present, are migration references; use the shipped Guard preset for new setup.
