---
name: setup-toolkit-loc-guard
description: Configure Guard source metrics policies for a PHP project, including migration from legacy loc configuration.
---

# Set up Guard source metrics policies

Use `k-kinzal/guard-php` and `vendor/bin/guard`. The source metrics checks are policies in the unified Collect → Policy → Report pipeline. Read [the Guard guide](../../docs/guard.md) and [the policy reference](../../docs/loc-guard.md) for configuration semantics.

Inspect the project's existing `guard.yaml`, Composer scripts, and source roots before changing setup. If no policy exists, run `vendor/bin/guard init`; it detects matching presets and migrates existing legacy files. If a policy already exists, update its `metrics` section or imports within the requested scope instead of overwriting it.

Import `vendor/k-kinzal/guard-php/rules/metrics.yaml` when adopting the standard constraints. Keep existing custom constraints and limits. The preset is the maintained source of the recommended defaults.

Set `metrics.source` from production Composer autoload roots. Exclude generated or vendored paths; keep code that needs a different limit in scope and assign it a named profile. Root profile omissions disable a metric; inherited omissions retain it, and explicit `null` disables it. Assignments use `match.paths`, must be disjoint, and must match scanned PHP files. Do not raise thresholds or remove constraints merely to pass a check.

Add or retain a Composer `guard` script running `guard check`, and include `@guard` in the existing lint sequence without removing other gates. Use `guard check --format=json` for machine-readable output and `guard apply --dry-run` to inspect configuration repairs. Source metrics, directory contents and document headings require manual changes.

Validate with `vendor/bin/guard check`. Success and recommendations exit 0, required violations exit 1, invalid configuration or operational errors exit 2. The standalone `loc-guard`, `tree-guard` and `doc-guard` executables no longer exist. Legacy YAML templates in this skill, where present, are migration references; use the shipped Guard preset for new setup.
