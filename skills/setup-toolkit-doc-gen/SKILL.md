---
name: setup-toolkit-doc-gen
description: >-
  Set up DocGen static documentation generation for a PHP project. Use when
  asked to configure doc-gen, its command line options, API documentation
  generation, a docs.rs-style documentation site, GitHub Pages hosting,
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
- Check for an existing documentation step, such as a `doc-gen` Composer script or a docs workflow.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Configuration

**DocGen has no configuration file.** A run is configured by its options alone, so the command that generated a site
is the whole description of that site — which is what lets a CI job pass the values only it knows, such as the
address the site is published at. Write the project's own settings into a Composer script, and leave the rest to the
job that calls it.

| Option | Default | Meaning |
|--------|---------|---------|
| `--packages=GLOBS` | `.` and `packages/*` | Directory globs probed for a `composer.json`; each hit becomes a documented package. |
| `--vendor[=GLOBS]` | none | Composer package-name globs (`acme/*`, not directory names) of installed **runtime** dependencies to document alongside the project; bare `--vendor` means all of them. |
| `--vendor-dev[=GLOBS]` | none | The same for installed **dev** dependencies; leave it off unless the test tooling itself needs documenting. |
| `--exclude=GLOBS` | none | Path globs, relative to the project root, pruned from source scanning. |
| `--output=DIR` | `build/docs` | Site output directory. |
| `--title=TEXT` | the root package name | Site title. |
| `--deptrac=FILE` | `deptrac.yaml` when present | Deptrac configuration the architecture graph and the layer badges are read from. |
| `--coverage=DIR` | none | PHPUnit `--coverage-xml` report directory; links every method to the test cases covering it. |
| `--base-url=URL` | none | Absolute address the site is published at, without a trailing slash. Pass it where the site is published from; it is what the canonical links and the social preview are written from. |
| `--repository=URL` | what the root `composer.json` declares | Absolute address of the repository the project lives in, which every page links back to. Pass it only to override `support.source` or `homepage`. |
| `--cache-dir=DIR` | `build/doc-gen-cache` | Directory the parsed sources and written pages are remembered in; `--no-cache` turns caching off for one run. Keep it outside the output directory. |

Every option that takes `GLOBS` reads a comma-separated list and may be repeated, which adds to what the earlier
occurrences named. `doc-gen --help` lists these together with the run options (`--jobs`, `--memory-limit`,
`--serve`, `--diff`, `--clear-cache`).

## Scope Semantics

The documented unit is the composer package: production autoload sources become API pages while `autoload-dev`
sources become linkable source pages only, so test references stay navigable without widening the API surface.
Page content and design are fixed by the generator on purpose — only the scope is configurable.

## Adapting to the Project

- Single-package repositories need no `--packages`; the default `packages/*` glob simply matches nothing.
- Add `--exclude` globs for fixture directories that contain intentionally invalid PHP (for example
  `--exclude=tests/Fixture/*`).
- To document key dependencies too, pass specific composer package name globs such as `--vendor=acme/*` rather than
  `--vendor`; a glob that documents no installed package of that kind is reported as a warning, so typos surface at
  once. Dev tooling is excluded unless a `--vendor-dev` glob asks for it.
- Documenting a dependency is what makes its classes link targets in signatures and type expressions, so add the
  packages whose types appear in the public API. Packages that ship only a phar cannot be documented and say so in a
  warning.
- Documenting many vendor packages needs memory: the run raises a limit below 512M automatically, and
  `--memory-limit=1G` handles very large dependency trees.
- When the project runs deptrac, keep `deptrac.yaml` at the root so the architecture graph appears automatically.
- Generate coverage with `--coverage-xml build/coverage-xml` and pass `--coverage=build/coverage-xml` so every method
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

The scripts are where the project's own settings live, because there is no configuration file to keep them in.
Write the settings once in `doc-gen`, and let the other scripts add to it:

```json
{
    "scripts": {
        "doc-gen": "doc-gen --exclude='tests/Fixture/*' --coverage=build/coverage-xml",
        "doc-gen:serve": "@doc-gen --serve",
        "doc-gen:diff": "@doc-gen --diff=main --serve",
        "doc-gen:fresh": "@doc-gen --no-cache"
    }
}
```

Quote a glob that a shell would otherwise expand, as `'tests/Fixture/*'` above. A caller adds to the script's
options with `composer doc-gen -- --base-url=https://example.github.io/project`, which is how a CI job passes what
only it knows.

Do not add `doc-gen` to `lint`; it is a generator, not a check.

## GitHub Pages Publishing

The site is fully static and already contains `.nojekyll`, so publishing it means committing the output directory
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
- Match the generation command to the project: the Composer script, or `vendor/bin/doc-gen` with the project's own
  options spelled out.
- Match the `on.push.branches` entry to the default branch, and `DOCS_BRANCH` to the branch Pages serves.
- Keep the action pins as full commit SHAs with the release tag in a comment, the per-job `contents: write`
  permission, and `pull_request` (never `pull_request_target`) as the preview trigger.

Pass `--base-url` with the published address at the same time, because that is what makes a shared link render as a
card: every page then carries its canonical link, its own description, and the Open Graph tags, and the run draws
`assets/og-image.png` from the site title and the project description (needs `ext-gd`; without it the pages simply
carry no image). Both workflow templates derive that address from `GITHUB_REPOSITORY`, so a fork or a rename needs
no edit. Add a docs badge to the README that links to the site, the way docs.rs does:

```markdown
[![docs](https://img.shields.io/badge/docs-<package>-0969da?logo=php&logoColor=white)](https://<owner>.github.io/<repository>/)
```

Close the loop the other way too: the site links back to the repository from every page, reading the address from
`support.source` (then `homepage`) in the package's `composer.json`. Add those entries where the project declares
neither — `support.docs` and `homepage` can point at the published site — and pass `--repository` only to override
what the manifests declare.

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
composer doc-gen
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
