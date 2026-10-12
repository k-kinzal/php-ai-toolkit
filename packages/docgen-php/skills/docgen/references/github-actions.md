# GitHub Actions

Use the same DocGen command and content options locally and in CI. Keep generation
and publishing in documentation workflows separate from the test/lint workflow.

## Generate and retain the site

After the project's normal PHP and dependency steps, run its existing coverage
and documentation commands. For a project without those scripts, the equivalent
generation steps are:

```yaml
- name: Generate coverage
  run: vendor/bin/phpunit --coverage-xml build/coverage-xml
- name: Generate documentation
  run: >-
    vendor/bin/docgen --coverage=build/coverage-xml
    --base-url=https://example.github.io/project
```

Adapt the PHPUnit command and URL to the project. For a site without coverage,
omit both the coverage step and `--coverage`.

- Restore `build/docgen-cache` with `actions/cache`, using the runner OS, selected
  Composer lock hash, and commit for the key; use the OS/lock prefix for fallback.
  Keep the cache outside the uploaded site.
- Upload the directory passed to `--output` (`build/docs` by default). With
  `actions/upload-artifact`, include hidden files so `.nojekyll` is retained and
  fail if the directory is empty.
- Pass the actual published address through `--base-url`, including a project
  or preview path when applicable. For a custom domain or an owner site, adapt
  the URL instead of using the project-site formula in the templates.

## Pages workflow templates

The skill bundles two templates under `assets/`:

| Template | Use |
| --- | --- |
| [docs.yml](../assets/docs.yml) | Build on default-branch pushes, upload the site, and publish it to the root of a Pages branch. |
| [docs-preview.yml](../assets/docs-preview.yml) | Generate pull-request diffs under `pr/<number>/`, comment the URL, and remove previews on close. Requires the root-site workflow. |

Use the templates for requested branch-based Pages publishing. An existing Pages
deployment can instead consume the generated directory through its own workflow.
Local generation or artifact-only CI does not require publishing.

Before using a template as `.github/workflows/docs.yml` or `docs-preview.yml`,
replace every `REPLACE_WITH_*` value with facts from the target project:

| Placeholder | Value to derive |
| --- | --- |
| `REPLACE_WITH_DEFAULT_BRANCH` | Repository's default branch. |
| `REPLACE_WITH_DOCS_BRANCH` | Existing branch served by Pages, commonly `gh-pages`. |
| `REPLACE_WITH_DOCGEN_JOB_PHP` | A supported PHP version compatible with DocGen and the project's dependency graph. |
| `REPLACE_WITH_REQUIRED_EXTENSIONS` | Project requirements; GD with FreeType for social images, `pcntl` for workers. The templates select PCOV for coverage. |
| `REPLACE_WITH_DEPENDENCY_MODE` | Existing CI install policy; `locked` when installing the committed lock. |
| `REPLACE_WITH_LOCK_REQUIREMENT` | `true` when a committed lock is required, otherwise the project's lock policy. |
| `REPLACE_WITH_SELECTED_LOCK_FILE` | Lock file installed by this job and hashed for the cache key. |

For versioned locks, copy the selected lock to `composer.lock` before validation
and installation. Reuse project coverage and DocGen scripts in both templates;
retain `composer check-platform-reqs`. Keep action pins as full commit SHAs.

The root template builds with read permissions and publishes in a separate job
with `contents: write` and `pages: write`. Publishing preserves `pr/` and `CNAME`
while deleting stale root output. It requests a Pages build and waits for the
published commit. The Pages branch and repository Pages source must already
exist; generating HTML alone does not configure hosting.

## Pull-request diffs

The preview template fetches full history and passes the base SHA through an
environment variable to `--diff`. Its working tree is the pull-request checkout;
the URL ends in `/pr/<number>`. Keep normal and preview package selection,
exclusions, coverage, architecture input, and public API mode aligned.

The preview job writes to the docs branch and comments on the pull request. It
uses `pull_request` and skips forks; retain those restrictions. The root workflow
owns root output and the preview workflow owns `pr/`. In monorepos, derive all
paths from the same working directory and include relevant manifests, locks, and
workflow files in any path filters.

## Verify

Run the generation command locally and inspect the package list and output.
After adapting a workflow, run `actionlint` and check that no placeholders remain.
Publication is verified by the workflow run and the served URL, including a
symbol page and a source link; successful local generation verifies only the build.
