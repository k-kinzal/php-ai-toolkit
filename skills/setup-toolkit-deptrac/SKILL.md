---
name: setup-toolkit-deptrac
description: >-
  Set up Deptrac architecture dependency analysis for a PHP project. Use when
  asked to configure Deptrac, enforce architecture boundaries, validate layer
  dependencies, create deptrac.yaml, add architecture checks to Composer scripts
  or CI, or adapt architecture rules for PHP web apps, CLI apps, workers,
  packages, libraries, modular monoliths, or reusable components.
---

# Setup Deptrac (Discovered Architecture Boundaries)

This skill configures Deptrac around the architecture the project should have.
Current directories and dependencies are evidence, not the model: a configuration
that merely permits the existing graph makes a poor design permanently green.

## Prerequisites

Inspect `composer.json` before installing:

- PHP runtime used for tools: `composer config platform.php`, `require.php`, CI matrix, and local `php -v`
- PHPStan version constraints, because current Deptrac releases may constrain PHPStan
- Autoload roots, package type, framework dependencies, `bin` entries, and existing scripts

Inspect current Composer metadata and Deptrac's release requirements at application
time. Select the newest release compatible with the target's PHP support range,
PHPStan graph, configuration format, and the runtime where Deptrac actually runs.
Do not copy this toolkit repository's Deptrac constraint.

A single-runtime application normally needs one current release line. A library or
tool that installs development dependencies throughout a PHP matrix may need a
minimal union, but add an older line only for a supported leg that cannot install
the newest compatible line. Verify every maintained lock or CI leg and use
`composer why-not` to explain an unexpected resolution:

```bash
composer require --dev "deptrac/deptrac:<target-derived-constraint>" --dry-run
composer why-not deptrac/deptrac <newest-compatible-version>
```

Run the confirmed requirement without `--dry-run`. Do not use
`--ignore-platform-reqs` to force Deptrac into an incompatible project. A normal
single `composer.lock` is fine when it represents the intended dependency graph.
Use PHP-versioned lock files only when supported runtimes need different graphs and
the target requires those graphs to be pinned.

Do not add `config.platform.php` just to make all environments use the oldest PHP dependency set unless the target project intentionally locks one dependency graph. Use platform overrides only for temporary compatibility checks.

If Composer cannot install any compatible Deptrac for the target runtime, run Deptrac as a PHAR/separate toolchain on a newer PHP runtime. Deptrac can analyze code that targets an older PHP version as long as the parser can parse the syntax.

## Discovery Inputs

Read these before designing `deptrac.yaml`:

1. `composer.json` autoload roots and `bin` entries.
2. Top-level directories under `src/`, `app/`, `lib/`, `packages/`, and `modules/`.
3. `README.md`, `AGENTS.md`, `docs/`, and existing architecture notes.
4. Existing config files: `deptrac.yaml`, `deptrac.php`.

Also inspect existing dependency direction before finalizing rules:

```bash
rg --files src app lib packages modules 2>/dev/null
find src app lib packages modules -maxdepth 2 -type d 2>/dev/null
rg -n '^namespace |^use ' src app lib packages modules 2>/dev/null
```

## Layer Discovery Workflow

Work in this order:

1. Identify production analysis paths from Composer autoload roots. Usually this is `src/` or `app/`, not `tests/`.
2. Identify responsibilities and trust boundaries from behavior, entry points,
   public contracts, and external IO. Then compare them with the real directories
   and namespaces.
3. For each candidate, write why it is a boundary. A directory is not automatically a layer; it needs a responsibility boundary or dependency rule worth enforcing.
4. Drop candidates that are too small, purely incidental, generated, or only exist to hold exceptions/types with no useful dependency policy.
5. Define the dependency direction that keeps policy and core behavior independent
   of entry points and infrastructure. Current dependencies are never automatic
   permission.
6. Refactor internal code until the physical structure expresses those boundaries.
   Preserve released public facades and signatures; introducing Deptrac does not
   authorize a public API change.
7. Create `deptrac.yaml` only after the intended layer table and the internal moves
   agree. Use this as its first line:

   ```yaml
   # NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you.
   ```

Before writing the config, form a small table for yourself:

| Layer | Collector basis | Responsibility | May depend on |
|-------|-----------------|----------------|---------------|

If this table cannot be filled without guessing, ask for the missing architecture
decision before writing the config. Do not skip Deptrac and do not create a
zero-value ruleset just to finish adoption.

For a flat library, keep the stable public entry points at their existing namespace
and move implementation behind responsibility-based internal namespaces. A long
`classLike` regular expression that enumerates today's classes is not layering; it
is an allowlist of the current accident. Prefer directory collectors after the
structure has been repaired.

## Examples

Examples live under `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-deptrac/examples/`.

These files are examples only. Do not copy one directly unless its layer names, collectors, and dependency direction match the discovered project architecture.

- `application-layers.deptrac.yaml` - apps with real `Http`, `UseCase`, `Domain`, and `Infrastructure` directories.
- `component-layers.deptrac.yaml` - CLI/tools/libraries whose top-level directories are functional components.
- `library-public-api.deptrac.yaml` - libraries with explicit `Contract`, `Core`, `Internal`, and adapter directories.
- `module-boundaries.deptrac.yaml` - modular codebases where modules must not call each other directly.
- `phpstan-extension-components.deptrac.yaml` - PHPStan extension/toolkit packages with rules, support services, formatters, reporters, and CLI code.

## Collector Strategy

Prefer collectors in this order:

1. `directory` for stable directory ownership such as `src/Domain/` or `src/Module/Billing/`.
2. `classLike` for naming conventions such as `*Command`, `*Controller`, or `*Repository`.
3. `composer` for package dependency boundaries such as framework-only or dev-only packages.
4. `bool` plus `layer` when a broad layer needs exclusions.
5. `private: true` on collectors for implementation classes that must only be used inside the same layer.

Every production class-like token should belong to one intentional layer. A thin
public facade can have its own layer; leaving it unassigned hides dependencies.
Fix the structure or collectors until `debug:unassigned` is empty.

## Ruleset Strategy

Use explicit rules. Deptrac reports a violation for dependencies that are not allowed by the ruleset.

Define only the edges that should exist. Avoid umbrella rules like "everything may depend on Shared" unless `Shared` is genuinely stable and low-level.

Use these heuristics:

- Entry-point layers (`Cli`, `Command`, `Http`, `Controller`, workers) may usually depend inward, but inward layers should not depend back on entry points.
- Support/utility layers should not depend on higher-level product behavior.
- Adapters/infrastructure may depend on contracts or core abstractions, but core code should not depend on adapters unless the project explicitly uses a different pattern.
- Feature modules should not depend on other feature modules unless docs say that coupling is intentional.
- If two layers currently depend on each other, do not permit the cycle by default. Decide whether to merge them into one layer or break the dependency.

## Merging Existing Configuration

If `deptrac.yaml` already exists, merge rather than overwrite:

- Keep existing `paths`, `exclude_files`, `imports`, and custom `services` unless they are wrong.
- Preserve existing layers that match the intended architecture.
- Replace vague catch-all layers with precise collectors.
- Do not add broad mutual access just to make analysis pass.
- Do not suppress violations or add reciprocal dependencies during setup. Fix the
  internal design or stop for a project-level architecture decision.

## Recommended Composer Scripts

Add scripts that match the installed Deptrac entry point.

Use the entry point provided by the resolved Deptrac release. Only when that actual
release does not expose `vendor/bin/deptrac`, add a project-root launcher such as
`deptrac.php` instead of assuming a legacy layout or adding a new file under `bin/`:

```php
<?php

declare(strict_types=1);

$deptracEntrypoints = [
    __DIR__ . '/vendor/bin/deptrac',
    __DIR__ . '/vendor/deptrac/deptrac/deptrac.php',
];

foreach ($deptracEntrypoints as $deptracEntrypoint) {
    if (!is_file($deptracEntrypoint)) {
        continue;
    }

    $command = array_map(
        static fn (string $argument): string => escapeshellarg($argument),
        [
            PHP_BINARY,
            $deptracEntrypoint,
            ...array_slice($argv, 1),
        ],
    );

    passthru(implode(' ', $command), $exitCode);
    exit($exitCode);
}

fwrite(STDERR, 'Could not find Deptrac. Run "composer install" first.' . PHP_EOL);
exit(1);
```

Then call the launcher from Composer:

```json
{
    "scripts": {
        "phpstan": "phpstan analyse --memory-limit=512M",
        "deptrac": "@php deptrac.php analyse --config-file=deptrac.yaml",
        "lint": [
            "@format:check",
            "@phpstan",
            "@deptrac"
        ]
    }
}
```

If the project already has `lint` or `check`, merge `@deptrac` into it after PHPStan unless runtime constraints require Deptrac to run in a separate CI job. Do not remove existing lint steps.

## Verification

After applying:

```bash
composer deptrac
php deptrac.php debug:unassigned --config-file=deptrac.yaml
php deptrac.php debug:unused --config-file=deptrac.yaml
```

`debug:unassigned` can return a non-zero exit code when it successfully finds unassigned tokens. Treat the output as a collector coverage report, not as a command failure by itself.

Fix configuration in this order:

1. Syntax/config errors.
2. Unassigned production tokens.
3. Unused rulesets caused by stale layer assumptions.
4. Real architecture violations.

## References

- [Deptrac Configuration](https://deptrac.github.io/deptrac/configuration/)
- [Deptrac Collectors](https://deptrac.github.io/deptrac/collectors/)
- [Deptrac Debugging](https://deptrac.github.io/deptrac/debugging/)
