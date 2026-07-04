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

This skill configures Deptrac by discovering the project's actual boundaries first, then defining dependency direction between those boundaries. Do not start from a preferred architecture model.

## Prerequisites

Inspect `composer.json` before installing:

- PHP runtime used for tools: `composer config platform.php`, `require.php`, CI matrix, and local `php -v`
- PHPStan version constraints, because current Deptrac releases may constrain PHPStan
- Autoload roots, package type, framework dependencies, `bin` entries, and existing scripts

Prefer Composer when it resolves cleanly:

```bash
composer require --dev deptrac/deptrac
```

Do not use `--ignore-platform-reqs` to force Deptrac into an incompatible project. If Composer cannot install Deptrac because the project runtime is older than Deptrac's runtime requirement, either use a compatible Deptrac major selected by Composer or run Deptrac as a PHAR/separate toolchain on a newer PHP runtime. Deptrac can analyze code that targets an older PHP version as long as the parser can parse the syntax.

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
2. List candidate layers from real directory or namespace groups. Prefer names already present in code: `Rule`, `Support`, `Cli`, `Command`, `Analyzer`, `Reporter`, `Domain`, `UseCase`, `Http`, `Infrastructure`, module names, package names.
3. For each candidate, write why it is a boundary. A directory is not automatically a layer; it needs a responsibility boundary or dependency rule worth enforcing.
4. Drop candidates that are too small, purely incidental, generated, or only exist to hold exceptions/types with no useful dependency policy.
5. Define dependency direction from project docs, naming, entry points, and current dependency evidence. Current dependencies are evidence, not automatic permission.
6. Create `deptrac.yaml` only after the layer list and intended direction are clear.

Before writing the config, form a small table for yourself:

| Layer | Collector basis | Responsibility | May depend on |
|-------|-----------------|----------------|---------------|

If this table cannot be filled without guessing, do not invent a strict architecture. Report that Deptrac is not useful yet or that a human architecture decision is needed.

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

Every production class-like token should belong to at least one intentional layer, unless the project intentionally leaves a thin public surface unlayered. If many tokens are unassigned, fix collectors before accepting violations.

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
- Preserve existing layers that match real architecture.
- Replace vague catch-all layers with precise collectors.
- Do not add broad mutual access just to make analysis pass.
- Do not suppress violations during setup. Fix them or leave a clear report of what needs a project-level architecture decision.

## Recommended Composer Scripts

Add scripts that match the installed Deptrac binary:

```json
{
    "scripts": {
        "deptrac": "deptrac analyse --config-file=deptrac.yaml",
        "lint": [
            "@format:check",
            "phpstan analyse --memory-limit=512M",
            "@deptrac"
        ]
    }
}
```

If the project already has `lint` or `check`, merge `@deptrac` into it after PHPStan unless runtime constraints require Deptrac to run in a separate CI job. Do not remove existing lint steps.

## Verification

After applying:

```bash
vendor/bin/deptrac analyse --config-file=deptrac.yaml
vendor/bin/deptrac debug:unassigned --config-file=deptrac.yaml
vendor/bin/deptrac debug:unused --config-file=deptrac.yaml
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
