---
name: setup-toolkit-doc-gen
description: >-
  Set up DocGen static documentation generation for a PHP project. Use when
  asked to configure doc-gen, doc.yaml, API documentation generation, a
  docs.rs-style documentation site, GitHub Pages documentation hosting,
  multi-package or monorepo documentation, documenting vendor packages,
  architecture layer visualization from deptrac, per-method test coverage
  references in documentation, executable doctest examples in docs, or
  Composer scripts and CI jobs that publish generated PHP documentation.
---

# Setup DocGen (Static Documentation Site)

This skill configures `doc-gen`, the php-ai-toolkit CLI that generates a dense, fully cross-linked static HTML
documentation site — complete types, interface implementations, call sites, architecture layers, coverage-backed
test references, and runnable doctest examples — for the composer packages of a project.

## Prerequisites

Inspect `composer.json` before configuring:

- Confirm the target project requires `k-kinzal/php-ai-toolkit`.
- Read the Composer PSR-4 autoload maps; they define exactly what gets documented.
- Check whether the repository is a monorepo (`packages/*/composer.json`) or a single package.
- Check for existing documentation config such as `doc.yaml` or a docs build step.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-doc-gen/doc.yaml` and apply it to the
project root as `doc.yaml`.

| Setting | Default | Meaning |
|---------|---------|---------|
| `packages` | `['.', 'packages/*']` | Directory globs probed for a `composer.json`; each hit becomes a documented package. |
| `vendor` | `[]` | Composer package-name globs (`acme/*`, not directory names) of installed **runtime** dependencies to document alongside the project. |
| `vendor_dev` | `[]` | The same for installed **dev** dependencies; keep it empty unless the test tooling itself needs documenting. |
| `exclude` | `[]` | Path globs, relative to the project root, pruned from source scanning. |
| `output` | `build/docs` | Site output directory. |

The optional keys `title`, `deptrac`, and `coverage` override the site title, the deptrac config used for the
architecture layer graph (auto-detected at `deptrac.yaml`), and the PHPUnit `--coverage-xml` report directory used
for per-method test references.

## Scope Semantics

The documented unit is the composer package: production autoload sources become API pages while `autoload-dev`
sources become linkable source pages only, so test references stay navigable without widening the API surface.
Page content and design are fixed by the generator on purpose — only the scope is configurable.

## Adapting to the Project

- Single-package repositories need no `packages` change; the `packages/*` glob simply matches nothing.
- Add `exclude` globs for fixture directories that contain intentionally invalid PHP (for example `tests/Fixture/*`).
- To document key dependencies too, set `vendor` to specific composer package name globs such as `['acme/*']` rather
  than `['*']`; a glob that documents no installed package of that kind is reported as a warning, so typos surface at
  once. Dev tooling is excluded unless a `vendor_dev` glob asks for it.
- Documenting a dependency is what makes its classes link targets in signatures and type expressions, so add the
  packages whose types appear in the public API. Packages that ship only a phar cannot be documented and say so in a
  warning.
- Documenting many vendor packages needs memory: the run raises a limit below 512M automatically, and
  `--memory-limit=1G` handles very large dependency trees.
- When the project runs deptrac, keep `deptrac.yaml` at the root so the architecture graph appears automatically.
- Generate coverage with `--coverage-xml build/coverage-xml` and set `coverage: build/coverage-xml` so every method
  links the test cases that cover it.

## Recommended Composer Scripts

Add scripts that match the project:

```json
{
    "scripts": {
        "doc-gen": "doc-gen --config=doc.yaml",
        "doc-gen:serve": "doc-gen --config=doc.yaml --serve"
    }
}
```

Do not add `doc-gen` to `lint`; it is a generator, not a check. For GitHub Pages, publish the `output` directory
(the site is fully static and already contains `.nojekyll`).

## Verification

After applying:

```bash
vendor/bin/doc-gen --config=doc.yaml
```

Exit codes:

- `0`: documentation generated
- `2`: configuration or runtime error

Then preview locally with `vendor/bin/doc-gen --serve` and spot-check the index page, one class page, and search.

## References

- [DocGen Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doc-gen.md) — Settings, scope semantics, and CLI behavior.
