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

Choose PHPStan from the target project, not from this toolkit repository's
development constraint. Read the target's PHP range, existing PHPStan/extensions,
Composer locks, and the runtimes where analysis is installed and executed. Then
inspect current Composer metadata and release notes and select the newest PHPStan
release compatible with that complete graph.

A single-runtime application will normally need one current release line. For a
multi-version library or tool, prefer the newest line that installs throughout the
matrix. Use a minimal union only when different supported runtimes genuinely need
different lines, and verify every maintained lock or CI leg. Check the intended
candidate with a dry run and explain an unexpected older resolution with
`composer why-not`:

```bash
composer require --dev "phpstan/phpstan:<target-derived-constraint>" k-kinzal/php-ai-toolkit --dry-run
composer why-not phpstan/phpstan <newest-compatible-version>
```

Run the confirmed `composer require` without `--dry-run`. Do not copy the toolkit's
root constraint or lock resolution. If PHPStan already has an intentional direct
constraint, preserve it and update to the newest release it admits unless changing
that policy is in scope.

The toolkit requirement is unversioned only for a new install so Composer can select
its newest stable release compatible with the target graph. Preserve an intentional
existing toolkit pin and update its lock within that constraint.

Read `composer config vendor-dir` as well. Every include below is rooted at that
project-derived directory (`vendor` is only Composer's default).

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
# NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you.

includes:
    - REPLACE_WITH_VENDOR_DIR/phpstan/phpstan-strict-rules/rules.neon
    - REPLACE_WITH_VENDOR_DIR/k-kinzal/php-ai-toolkit/extension.neon
    - REPLACE_WITH_VENDOR_DIR/k-kinzal/php-ai-toolkit/rules.neon

parameters:
    level: max
    paths:
        - REPLACE_WITH_ANALYSIS_PATH
```

Replace `REPLACE_WITH_VENDOR_DIR` with `composer config vendor-dir` before use. A
remaining sentinel is a configuration error, not a literal directory name. Replace
`REPLACE_WITH_ANALYSIS_PATH` with every production and test autoload root derived
from `composer.json`; repeat the list item for multiple roots.

Analysis scope belongs in PHPStan configuration so direct invocations, editor
integrations, Composer, and CI all analyse the same paths. Do not put the paths only
on the Composer command. Keep that command independent of project layout:

```json
{
    "scripts": {
        "phpstan": "phpstan analyse --memory-limit=512M",
        "lint": [
            "@format:check",
            "@phpstan"
        ]
    }
}
```

## Existing Configuration

Reduce an existing configuration to the three baseline includes, `level: max`, the
project's complete `paths`, and settings that are both project-specific and
necessary. Remove copied rule definitions, the old error-formatter include, and
copied exception defaults.

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
        visibilityExemptNamespacePrefixes:
            - 'App\\Tests'
```

The toolkit's visibility rule enforces `@visibility` declarations during the
same analysis. PHPStan must analyse every production root that declares or
references those symbols. Add only namespace prefixes that genuinely sit
outside the ownership contract, normally test namespaces. Declarations marked
`@visibility public` are also treated as public API by PHPStan's unused-symbol
extensions, so do not add separate `ignoreErrors` entries for them.

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

- [PHPStan AI Formatter](vendor/k-kinzal/php-ai-toolkit/docs/phpstan-ai-formatter.md)
- [PHPStan Rules](vendor/k-kinzal/php-ai-toolkit/docs/phpstan-rules.md)
