---
name: setup-toolkit-php-compatibility
description: >-
  Set up PHPCompatibility cross-version checks for a PHP project. Use when asked
  to verify code matches its declared PHP support range, detect syntax, functions,
  constants, or removals outside supported PHP versions, configure
  PHPCompatibility or PHP_CodeSniffer for compatibility, create phpcs.xml.dist,
  or add a compatibility gate to Composer scripts or CI.
---

# Setup PHPCompatibility (Declared PHP Range Gate)

This skill configures PHPCompatibility so the project fails when code uses syntax
or standard-library features incompatible with the PHP versions the target project
claims to support. It answers "does the shipped code fit the declared PHP range?",
which neither PHP-CS-Fixer nor a single-version CI run verifies.

PHPCompatibility is a PHP_CodeSniffer standard, so it runs on the `phpcs` binary.
It is a static analyzer only — it never rewrites code.

## Determine the Supported Range First

Discover the target project's complete supported PHP set before choosing a package
version or writing `testVersion`. Do not infer it from this repository.

- Read `composer.json` `require.php` and resolve the admitted intervals, including
  lower and upper bounds rather than extracting only the first version literal.
- Cross-check `config.platform.php`, every CI matrix leg, deployment/tool runtimes,
  and documented support policy. Surface disagreements instead of choosing the
  range most convenient for the template.
- Express a single supported minor as that minor, a bounded contiguous range as
  `<minimum>-<maximum>`, and an open-ended range as `<minimum>-`. A project
  supporting only one minor must not receive an open-ended value merely because an
  example shows one.
- If Composer declares disjoint intervals, configure and run each interval
  separately or obtain an explicit decision to enforce their conservative
  contiguous envelope. Do not silently discard an interval or its upper bound.

PHPCompatibility's `testVersion` represents the target support policy, not the PHP
version currently running PHPCS.

## Prerequisites

Inspect `composer.json` before installing:

- Production autoload roots — the paths to gate.
- The declared PHP range and the PHP runtime where PHPCS will run.
- Existing PHPCompatibility, PHP_CodeSniffer, PHP-CS-Fixer, `phpcs.xml*`, Composer
  constraints, plugin policy, and locks.
- Existing `lint`/`check` Composer scripts and CI jobs.

## Installation

Version selection has two independent compatibility axes:

- the PHPCompatibility release must contain sniffs for the target project's whole
  declared PHP range; and
- PHPCompatibility plus PHP_CodeSniffer must install on the runtime and dependency
  graph where the gate runs.

Inspect current Composer metadata and the authoritative sniff support/release notes
at application time. Select the newest release at the highest stability level that
satisfies both axes. A stable release that cannot recognize the target's PHP
features is not compatible for this purpose. When required sniff coverage exists
only in a pre-release, use the newest tagged pre-release and record why; do not use a
moving development branch by default.

Derive constraints for both directly used tools and confirm them before writing the
manifest:

```bash
composer require --dev --with-dependencies \
  "phpcompatibility/php-compatibility:<target-derived-constraint>" \
  "squizlabs/php_codesniffer:<target-derived-constraint>" --dry-run
composer why-not phpcompatibility/php-compatibility <newest-capable-version>
```

Run the confirmed requirement without `--dry-run`. Prefer a single current release
line. Add an older line only when a real supported installation graph needs it, and
verify every maintained lock or CI leg. Do not copy constraints from this
repository's `composer.json`: they describe this repository's matrix and the sniff
coverage available when its locks were produced.

The install may pull in `dealerdirect/phpcodesniffer-composer-installer`, which
registers the standard automatically but is a Composer plugin. Allow it only when
the resolved graph uses it:

```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
```

Confirm the standard is registered:

```bash
vendor/bin/phpcs -i
```

If an intentional existing PHP_CodeSniffer pin prevents the newest capable
PHPCompatibility release, report the conflict rather than silently replacing the
pin or downgrading sniff coverage. When automatic registration is unavailable,
register the installed standard path manually:

```bash
REPLACE_WITH_VENDOR_DIR/bin/phpcs --config-set installed_paths \
  REPLACE_WITH_VENDOR_DIR/phpcompatibility/php-compatibility
```

Replace `REPLACE_WITH_VENDOR_DIR` from `composer config vendor-dir`.

Do not force the install with `--ignore-platform-reqs`.

## Template

Read the template from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-php-compatibility/phpcs.xml.dist`
and apply it to the project root as `phpcs.xml.dist`, then adapt it before running:

- Replace `PHP_VERSION_RANGE` with the discovered single minor, bounded range, or
  open-ended range.
- Replace the example `<file>` entries with every real shipped/runtime autoload
  root. Include tests only when their runtime compatibility is part of the target's
  declared policy.
- Keep excludes limited to generated code and fixtures or snapshots that
  intentionally contain incompatible sample syntax.

Keep this ruleset compatibility-only. Never add style sniffs such as PSR12 or Squiz;
code style belongs to PHP-CS-Fixer, and mixing the two makes the tools fight.

## Recommended Composer Scripts

`phpcs` auto-discovers `phpcs.xml.dist`, so the script needs no `--standard` flag:

```json
{
    "scripts": {
        "compat": "phpcs",
        "lint": [
            "@format:check",
            "@phpstan",
            "@compat"
        ]
    }
}
```

Add `@compat` to the existing gate sequence without removing other steps. If the
first run finds violations, fix the code or resolve the declared support-policy
conflict; do not weaken the range or leave the configured gate unwired.

## Relationship to PHPStan

PHPStan can also flag version drift when the installed release supports declaring
the target range, and needs no extra dependency. It is a reasonable alternative
when the project wants to avoid a second toolchain, but syntax outside the parser's
range can halt analysis and its messages are coarser. PHPCompatibility gives
per-feature messages and stable sniff identifiers, so prefer it when diagnostic
quality matters. Do not run both as the compatibility gate.

## Verification

```bash
vendor/bin/phpcs
vendor/bin/phpcs -s
vendor/bin/phpcs --report=source
```

Exit codes:

- `0`: no violations — all scanned code fits the declared supported range.
- `1`/`2`: violations found or a PHPCS runtime/configuration error.

A violation is evidence that either the code or declared support range must change.
Do not silence it; the project's toolkit rules forbid suppression comments.

PHPCompatibility flags version-specific tokens lexically. A bareword token constant
may be reported even inside a `defined()` guard. Reference such a constant through
`constant('TOKEN_NAME')` when that is required to keep the file runnable on the
declared range.

## References

- [PHPCompatibility](https://github.com/PHPCompatibility/PHPCompatibility)
- [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)
