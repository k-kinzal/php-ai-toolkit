---
name: setup-toolkit-php-compatibility
description: >-
  Set up PHPCompatibility cross-version checks for a PHP project. Use when asked
  to verify code matches the supported PHP version, detect syntax or functions
  newer than the minimum PHP, enforce a PHP version floor, stop 8.1+ syntax
  (readonly, enums, first-class callables, new-in-initializer) in an 8.0 project,
  configure PHPCompatibility or PHP_CodeSniffer for compatibility, create
  phpcs.xml.dist, or add a compatibility gate to Composer scripts or CI.
---

# Setup PHPCompatibility (PHP Version Floor Gate)

This skill configures PHPCompatibility so the project fails when code uses any
syntax or standard-library function newer than the **lowest** PHP version the
project claims to support. It answers "is our code actually runnable on our
declared minimum PHP?", which neither PHP-CS-Fixer nor a single-version CI run
verifies.

PHPCompatibility is a PHP_CodeSniffer standard, so it runs on the `phpcs` binary.
It is a static analyzer only — it never rewrites code.

## Determine the Floor First

The whole gate hinges on one number: the project's minimum supported PHP version.
Discover it before anything else — do not assume 8.0.

- Read `composer.json` `require.php` and take the **lowest** version the constraint
  admits (`^8.0` → `8.0`, `>=8.1 <8.5` → `8.1`, `~8.2.0` → `8.2`).
- Cross-check against `config.platform.php`, the CI matrix (lowest leg), and any
  documented support policy. If they disagree, surface the conflict — the declared
  floor and the tested floor should match.

The floor becomes `testVersion` as `<floor>-` (the trailing `-` means "and up").

## Prerequisites

Inspect `composer.json` before installing:

- Autoload roots (`src/`, `app/`, `packages/*/src`) — the production paths to gate.
- Whether PHP-CS-Fixer, PHP_CodeSniffer, or a `phpcs.xml*` already exist.
- Existing `lint`/`check` Composer scripts and CI jobs.

## Installation

Version choice matters more than it looks. PHPCompatibility ships two lines:

- **9.x** (`dev-master` → `9.x-dev`; latest tag `9.3.5`, Dec 2024) tops out at PHP
  7.4 feature detection. It does **not** flag `readonly`, enums, `never`,
  first-class callables, pure intersection types, or `new` in initializers, so it
  is useless as a gate for an 8.0+ floor — a project full of 8.1 syntax scans
  clean. Do **not** use the 9.x line (`^9`, `dev-master`) for this.
- **10.x** (`dev-develop` → `10.x-dev`; tagged pre-releases `10.0.0-alpha1/alpha2`,
  2025) is the line that carries the PHP 8.0–8.5 sniffs and requires
  PHP_CodeSniffer 4. Only this line detects the 8.1+ syntax you want to reject.

10.x has no stable tag yet, so you must take a pre-release. Prefer pinning the
tagged alpha over the moving branch for reproducibility, and set `minimum-stability`
so Composer will accept it:

```bash
composer require --dev --with-dependencies \
  "phpcompatibility/php-compatibility:^10.0@alpha" "squizlabs/php_codesniffer:^4.0"
```

Use the branch (`phpcompatibility/php-compatibility:dev-develop`) instead only if
you need a fix that has landed on `develop` but is not yet in a tagged alpha —
accept that it moves under you. Either way you are on the 10.x line; `dev-master`
and `^9` are the trap to avoid.

The install pulls in `dealerdirect/phpcodesniffer-composer-installer`, which
registers the standard automatically but is a Composer plugin. Allow it once:

```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
```

Confirm the standard is registered:

```bash
vendor/bin/phpcs -i   # expect "PHPCompatibility" in the list
```

If the project is pinned to PHP_CodeSniffer 3.x and cannot move to 4.x, register
the standard path manually instead of using the plugin:

```bash
vendor/bin/phpcs --config-set installed_paths vendor/phpcompatibility/php-compatibility
```

Do not force the install with `--ignore-platform-reqs`.

## Template

Read the template from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-php-compatibility/phpcs.xml.dist`
and apply it to the project root as `phpcs.xml.dist`, then adapt:

- Set `<config name="testVersion" value="..."/>` to the discovered floor (e.g. `8.1-`).
- List the real production autoload roots as `<file>` entries.
- Exclude fixture/snapshot directories that intentionally hold sample syntax
  (e.g. `tests/Fixture/*`). Only gate code that actually ships or runs.

Keep this ruleset compatibility-only. Never add style sniffs (PSR12, Squiz) — code
style belongs to PHP-CS-Fixer, and mixing the two makes the tools fight.

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

Only wire `@compat` into `lint`/`check` **after** the codebase already passes the
gate — otherwise you break the pipeline on install. If violations exist, fix them
(or downgrade the declared floor) first, then add the step. Do not remove existing
lint steps.

## Relationship to PHPStan

PHPStan's `phpVersion` (a range in PHPStan 2.x: `phpVersion: {min: ..., max: ...}`)
can also flag version drift and needs no extra dependency. It is a reasonable
alternative when the project wants to avoid a second toolchain, but it reports
newer syntax as a parse error that halts analysis and its messages are coarser.
PHPCompatibility gives per-feature messages and stable sniff identifiers, so prefer
it when diagnostic quality matters. Do not run both as the compatibility gate.

## Verification

```bash
vendor/bin/phpcs                         # whole project, uses phpcs.xml.dist
vendor/bin/phpcs -s                      # add sniff identifiers, for ignore config
vendor/bin/phpcs --report=source         # violation-source summary (which features)
```

Exit codes:

- `0`: no violations — all code fits the declared floor.
- `1`/`2`: violations found (phpcs uses a non-zero exit for reported errors).

A violation is genuine evidence that either the code must be downgraded to the
floor or the declared floor must be raised to match reality. Do not silence it —
the project's own toolkit rules forbid suppression comments.

Note: PHPCompatibility flags version-specific tokens **lexically**. A bareword
constant such as `T_ENUM` is reported wherever it textually appears, even inside a
`defined('T_ENUM')` guard. Reference such constants via `constant('T_ENUM')` (a
string argument) so the code stays runnable on the floor without a bareword.

## References

- [PHPCompatibility](https://github.com/PHPCompatibility/PHPCompatibility)
- [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)
