---
name: setup-toolkit-doc-gen
description: >-
  Set up DocGen static documentation generation for a PHP project. Use when
  asked to configure doc-gen, doc.yaml, API documentation generation, a
  docs.rs-style documentation site, GitHub Pages documentation hosting,
  multi-package or monorepo documentation, documenting vendor packages,
  architecture layer visualization from deptrac, per-method test coverage
  references in documentation, executable doctest examples in docs,
  documentation that compares two git revisions, incremental documentation
  builds with a parse and page cache, per-pull-request documentation previews,
  or Composer scripts and CI jobs that publish generated PHP documentation.
---

# Setup DocGen (Static Documentation Site)

This skill configures `doc-gen`, the php-ai-toolkit CLI that generates a dense, fully cross-linked static HTML
documentation site — complete types, interface implementations, call sites, architecture layers, coverage-backed
test references, runnable doctest examples, and an optional comparison of two git revisions — for the composer
packages of a project.

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
| `cache` | `build/doc-gen-cache` | Directory the parsed sources and written pages are remembered in; `false` turns caching off. Keep it outside `output`. |

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
- Runs are incremental by default: the next run parses only the sources that changed and rewrites only the pages
  that changed, and reports what it reused (`Cache: 861 of 862 sources and 1365 of 1411 pages reused`). The site is
  the same site a full run writes, byte for byte. Use `--no-cache` to prove that on demand, `--clear-cache` to start
  over, and `--cache-dir=DIR` to keep the cache elsewhere. Add the cache directory to `.gitignore`.
- In CI, restore the cache directory between runs (for example with `actions/cache` keyed on `composer.lock` and the
  toolkit version) so a documentation job costs the size of the change rather than the size of the project.
- To review what a branch changed, generate the site in diff mode: `--diff=main` compares the working tree against
  `main`, `--diff=v1.0.0..HEAD` compares two revisions. The site then marks additions and removals down to the single
  argument of a declaration and offers three display modes — the plain documentation, the marked documentation, and
  the changes alone. Diff mode needs a git working tree and analyzes both revisions, so it takes about twice as long.

## Recommended Composer Scripts

Add scripts that match the project:

```json
{
    "scripts": {
        "doc-gen": "doc-gen --config=doc.yaml",
        "doc-gen:serve": "doc-gen --config=doc.yaml --serve",
        "doc-gen:diff": "doc-gen --config=doc.yaml --diff=main --serve",
        "doc-gen:fresh": "doc-gen --config=doc.yaml --no-cache"
    }
}
```

Do not add `doc-gen` to `lint`; it is a generator, not a check.

## GitHub Pages Publishing

The site is fully static and already contains `.nojekyll`, so publishing it means committing the `output` directory
to a branch that GitHub Pages serves. This skill ships two workflow templates for that, and **which of them to
install is the user's decision — ask before writing any workflow file**:

| Answer | What to install |
|--------|-----------------|
| No CI publishing | Nothing; `doc-gen` stays a local command. |
| Publish the default branch | `docs.yml` only. |
| Publish and preview pull requests | `docs.yml` and `docs-preview.yml`. |

Never install `docs-preview.yml` without `docs.yml`: the preview workflow writes only below `pr/<number>/` and
expects the site workflow to own the root of the branch.

Read the templates from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-doc-gen/docs.yml` and
`docs-preview.yml` and apply them as `.github/workflows/docs.yml` and `.github/workflows/docs-preview.yml`.

- `docs.yml` runs on pushes to the default branch: it generates the site and syncs it to the root of the `gh-pages`
  branch, keeping `pr/` and `CNAME`.
- `docs-preview.yml` runs on pull requests: it generates the site in diff mode against the base commit, publishes it
  to `pr/<number>/`, comments the link on the pull request, and removes that directory when the pull request closes.
  Pull requests from forks are skipped, because their token cannot write to the branch.

Adapt both to the project before applying:

- Match the workflow PHP version to a version the project supports, and add the lock-file step the project's CI uses
  (for example `cp composer.lock.php-8.4 composer.lock` when the repository keeps one lock file per PHP minor).
- Match the generation command to the project: `vendor/bin/doc-gen --config=doc.yaml` or the Composer script.
- Match the `on.push.branches` entry to the default branch, and `DOCS_BRANCH` to the branch Pages serves.
- Keep the action pins as full commit SHAs with the release tag in a comment, the per-job `contents: write`
  permission, and `pull_request` (never `pull_request_target`) as the preview trigger.

Tell the user about the one-time repository setup the workflows do not do:

```bash
git switch --orphan gh-pages
git commit --allow-empty -m 'docs: create the documentation branch'
git push -u origin gh-pages
git switch main
```

Then set Settings > Pages > Build and deployment > Source to "Deploy from a branch", branch `gh-pages`, folder
`/ (root)`. The site is served at `https://<owner>.github.io/<repository>/`, and previews at
`https://<owner>.github.io/<repository>/pr/<number>/`.

## Verification

After applying:

```bash
vendor/bin/doc-gen --config=doc.yaml
```

Exit codes:

- `0`: documentation generated
- `2`: configuration or runtime error

Then preview locally with `vendor/bin/doc-gen --serve` and spot-check the index page, one class page, and search.

When a workflow was installed, check it too, and say plainly that the publishing itself is only observable after the
workflow runs on the default branch:

```bash
go run github.com/rhysd/actionlint/cmd/actionlint@latest .github/workflows/docs.yml
```

## References

- [DocGen Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doc-gen.md) — Settings, scope semantics, caching, publishing, and CLI behavior.
- [GitHub Actions Configuration](vendor/k-kinzal/php-ai-toolkit/docs/github-actions.md) — Workflow hardening rules the documentation workflows follow.
