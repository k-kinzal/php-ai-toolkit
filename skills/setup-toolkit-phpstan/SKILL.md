---
name: setup-toolkit-phpstan
description: >-
  Set up PHPStan with php-ai-toolkit's opinionated defaults and AI error
  formatter. Use when asked to configure PHPStan, static analysis, toolkit
  PHPStan rules, or the AI error formatter in a PHP project.
---

# Set Up PHPStan

The target configuration assembles the baseline from PHPStan's strict rules and
the toolkit's two public configuration files. Do not copy their contents into
the target or tune the baseline until the current code passes.

## Configuration Responsibilities

- `phpstan/phpstan-strict-rules/rules.neon` registers the official strict rules;
- `php-ai-toolkit/extension.neon` registers the `ai` error formatter and its
  agent detector;
- `php-ai-toolkit/rules.neon` defines checked-exception policy and registers
  every toolkit rule through configurable conditional tags; and
- the target `phpstan.neon` selects PHPStan level `max`.

Together these are one baseline. `toolkit.allRules` defaults to `true`, and every
rule-specific `toolkit.<rule>.enabled` value inherits it. Do not disable or
weaken them during adoption.

## Installation

Derive the PHPStan constraint before installation. A single-runtime application
may select one compatible major. A library or tool with a PHP support matrix must
use a union that resolves on every supported minor and verify every maintained
lock. For this toolkit's PHP 8.0+ matrix that is:

```bash
composer require --dev "phpstan/phpstan:^1.12 || ^2.0" k-kinzal/php-ai-toolkit
```

Do not copy that union into a target with a different support range; derive its
constraint. Read `composer config vendor-dir` as well. Every include below is
rooted at that project-derived directory (`vendor` is only Composer's default).

`phpstan/phpstan-strict-rules` is a runtime dependency of the toolkit; do not add
a separately chosen version constraint to the target project.

The default setup loads the toolkit explicitly and does not need
`phpstan/extension-installer`. If that Composer plugin is present solely for the
toolkit or strict-rules, remove it and its `allow-plugins` entry. If another
PHPStan extension genuinely needs the plugin, keep it but add both
`k-kinzal/php-ai-toolkit` and `phpstan/phpstan-strict-rules` to
`extra.phpstan/extension-installer.ignore`; the target configuration loads both
explicitly and PHPStan rejects a configuration file included twice.

## Target Configuration

Copy the shipped template to the configuration name the project already uses
(`phpstan.neon` or `phpstan.neon.dist`):

```neon
includes:
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/k-kinzal/php-ai-toolkit/extension.neon
    - vendor/k-kinzal/php-ai-toolkit/rules.neon

parameters:
    level: max
```

That is the complete standard configuration. Put analysis paths on the Composer
command so the configuration remains only the toolkit contract:

```json
{
    "scripts": {
        "phpstan": "phpstan analyse src tests --memory-limit=512M",
        "lint": [
            "@format:check",
            "@phpstan"
        ]
    }
}
```

Derive `src tests` from the production and test autoload roots in
`composer.json`; do not assume those literal directory names.

## Existing Configuration

Reduce an existing configuration to the three baseline includes, `level: max`,
and settings that are both project-specific and necessary. Remove copied rule
definitions, the old error-formatter include, and copied exception defaults.

The following additions can be legitimate:

- `bootstrapFiles` for runtime symbols PHPStan cannot otherwise discover;
- narrow `excludePaths` for generated or deliberately invalid fixtures;
- PHP version bounds derived from the declared support range; and
- `parameters.toolkit` when the project uses non-standard namespace or path
  conventions.

Custom rule parameters describe facts; they are not escape hatches. Examples:

```neon
parameters:
    toolkit:
        testNamespacePrefixes:
            - 'App\Tests'
        restrictedTestNamespacePrefixes:
            - 'App\Tests\Unit'
            - 'App\Tests\Integration'
        srcMarker: '/app/'
        unitTestMarker: '/tests/Unit/'
```

Only list a `broadCatchAllowedPaths` entry for a real process or protocol
boundary that must translate every failure. Only list pairing exclusions for
generated declarations or another evidenced non-source artifact.

Do not add `ignoreErrors` to make adoption green. Fix the code. A narrow ignore
for a demonstrated upstream type error needs the exact identifier and path and
human approval.

Introducing the toolkit is not permission to change a released public API,
product documentation, or `AGENTS.md`. Preserve public facades and refactor
their internals when a rule exposes a design problem.

## Verification

Run the exact Composer script that CI will run:

```bash
composer phpstan
```

Confirm the output uses the `ai` formatter and intentionally introduce one
known toolkit violation in a disposable local probe if there is any doubt that
the extension loaded. Remove the probe immediately after verification.

## References

- [PHPStan Configuration](vendor/k-kinzal/php-ai-toolkit/docs/phpstan.md)
- [PHPStan Rules](vendor/k-kinzal/php-ai-toolkit/docs/phpstan-rules.md)
