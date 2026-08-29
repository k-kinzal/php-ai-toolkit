---
name: setup-toolkit-github-actions
description: >-
  Set up GitHub Actions CI for php-ai-toolkit PHP projects. Use when asked to
  create or update .github/workflows/ci.yml, run toolkit checks in CI, pin
  GitHub Actions to full commit SHAs, align CI with supported PHP versions, or
  harden workflow permissions and concurrency for Composer, PHPUnit, ParaTest,
  PHPStan, PHP-CS-Fixer, PHPCompatibility, LocGuard, TreeGuard, and Deptrac.
---

# Setup GitHub Actions CI

This skill configures GitHub Actions so every php-ai-toolkit gate is visible in
CI and the workflow follows Actions security and maintainability practices.

## Discover Project Constraints

Read these files before editing CI:

- `composer.json`: `require.php`, `config.platform.php`, and Composer scripts.
- Existing `.github/workflows/*.yml` or `.yaml`.
- Toolkit configs that imply CI gates: `.php-cs-fixer.dist.php`,
  `phpstan.neon`, `phpcs.xml.dist`, `loc.yaml`, `tree.yaml`, `deptrac.yaml`,
  `phpunit.xml.dist`.
- Project docs that declare supported PHP versions.
- Composer lock policy: one normal `composer.lock`, no committed lock, or
  PHP-versioned locks such as `composer.lock.php-<minor>`.
- Composer platform requirements and each command's runtime needs. Derive the
  `setup-php` extension list from these; do not rely on what `ubuntu-latest`
  happens to preinstall. ParaTest needs `pcntl`, coverage needs `pcov` or Xdebug,
  and DocGen social images need GD with FreeType.

If the declared PHP floor, Composer constraint, docs, and CI matrix disagree,
surface the conflict and make CI match the declared support policy.

## Template

Read the template from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-github-actions/ci.yml`
and apply it to the project root as `.github/workflows/ci.yml`.

The template contains `REPLACE_WITH_*` sentinels instead of this repository's PHP
matrix and lock policy. Replace every sentinel from target-project evidence before
installing the workflow; a remaining sentinel is a failed setup, not a default.

If a workflow already exists, merge rather than blindly replacing it.

## Required CI Gates

The workflow must make the toolkit checks observable as separate named steps.
Do not hide all lint gates behind a single unnamed `composer lint` step.

Required gates when the corresponding script/config exists:

- `composer format:check` for PHP-CS-Fixer.
- `composer phpstan` for PHPStan and toolkit PHPStan rules.
- `composer compat` for PHPCompatibility.
- `composer loc-guard` for LocGuard.
- `composer tree-guard` for TreeGuard.
- `composer deptrac` for Deptrac.
- `composer test` for the PHPUnit suite when ParaTest is installed, otherwise
  `composer test:unit`.

Property-based tests configured by `/setup-toolkit-pbt` run through
`composer test:pbt` in their separate `pbt.yml` workflow. Verify that workflow is
present and that ordinary PHPUnit and ParaTest commands exclude the `pbt` group;
do not add the slower group to the normal test matrix as well.

Every gate the project has configured belongs in CI. A tool that is installed,
configured, and wired into `composer lint` but never runs on the default branch
is a gate the project believes it has: it passes locally for whoever last ran it
and drifts from then on. When a config file exists and its Composer script does
not run anywhere in CI, that is the finding to report, not a detail to leave.

Keep `compat` inside the `lint` job alongside formatting, PHPStan, LocGuard,
TreeGuard, and Deptrac. It may be a separate step for visibility, but it should
not be a separate CI job unless the project has an explicit reason. Namespace
visibility is enforced by the toolkit's PHPStan rules and needs no separate CI
step.

If a script is missing but the config exists, add the Composer script using the
corresponding setup skill before wiring CI. If neither script nor config exists,
do not invent the gate in CI; set up that tool first.

### The Parallel Test Runner

When ParaTest is installed, make `composer test` select it and run that command in
the normal PHP matrix. Do not add a second sequential PHPUnit job: ParaTest already
executes the PHPUnit suite, and the matrix simultaneously verifies runtime support
and isolation across worker processes.

A real multi-major matrix must use the version-selecting `tests/run.php` runner from
`/setup-toolkit-phpunit`, so ParaTest receives the configuration matching the
installed PHPUnit major. Do not rely on the newest `phpunit.xml.dist` under older
dependency graphs. Keep `composer test:unit` available for local debugging and for
tools that specifically require PHPUnit, but CI does not need to repeat it.

## Separate Decision: Documentation Publishing

Do not add documentation generation or GitHub Pages publishing to `ci.yml`.
Those live in their own workflows, and their templates and the questions to ask
before installing them belong to the `/setup-toolkit-docgen` skill.

Do not silently finish when the project has a `docgen` script but no DocGen
workflow. Ask the user to choose one of the three supported outcomes: local only,
publish the default branch, or publish the default branch plus pull-request diff
previews. Then use `/setup-toolkit-docgen` for the chosen workflows. The workflows
remain separate from `ci.yml` so publishing permissions do not leak into CI.

## Out of Scope: Mutation Testing

Do not write a mutation testing job from scratch. `/setup-toolkit-infection` ships
the job together with the configuration it depends on, because the job needs a
coverage driver, `fetch-depth: 0`, and a different Composer script per event. Point
the user there when they ask for an Infection job.

## Out of Scope: Performance Benchmarks

Do not add PHPBench to the normal test or lint matrix. `/setup-toolkit-phpbench`
owns the benchmark contract, stable runner settings, paired merge-target and
candidate measurements, relative gate, job summary, and evidence Artifact. A
benchmark command run once on one matrix leg is neither a comparison nor a useful
performance gate.

## Out of Scope: Fuzzing

Do not write a generic fuzz job in `ci.yml`. `/setup-toolkit-fuzzing` derives the
input generator and oracle from a specific contract and ships a separate scheduled
workflow with corpus caching and crash artifacts. A random-input loop without that
contract design is not a CI gate.

## PHP Version Coverage

CI must match the project's declared support range. Do not compensate for a
bad Composer constraint by narrowing the workflow matrix.

1. Determine the supported PHP range from `composer.json require.php` and the
   project docs.
2. List every supported minor version in the `tests` matrix.
3. Prefer the project's existing lock policy. A normal single `composer.lock`
   is fine for most applications. Use PHP-versioned lock files only when a
   supported older PHP minor needs a different dependency graph and the project
   has a supply-chain requirement to pin those graphs, which is common for
   libraries and developer tools.
4. If the project uses PHP-versioned lock files, make sure every matrix minor
   has a matching `composer.lock.php-<minor>` file.
5. Check whether dev dependencies can install on each minor:
   ```bash
   cp "composer.lock.php-<minor>" composer.lock
   composer validate --strict --no-check-publish
   ```
6. If dependencies do not install on a supported PHP minor, fix the Composer
   constraints first. Select the newest compatible tool line for that leg and add
   an older line only when Composer proves the target matrix needs it.
7. Run the lint gates as named steps in one `lint` job on the highest supported
   runtime compatible with the tooling. PHPCompatibility checks the declared PHP
   range; repeating formatting, PHPStan, LocGuard, TreeGuard, and Deptrac on every
   runtime adds no distinct gate.
8. Use the highest matrix minor for one-off mutation, documentation, and benchmark
   jobs unless the tool cannot run there. Record that limitation rather than
   copying the template's PHP literal.

Never use `--ignore-platform-reqs` to make a lower PHP job pass. That hides a
real compatibility problem.

The workflow template is deliberately incomplete until its PHP values, test command,
extensions, branches, and lock steps are derived from the target. Never retain a
literal merely because it matches php-ai-toolkit's own CI. In particular, do not
copy this repository's full matrix or versioned-lock topology into a single-runtime
application.

When generating PHP-versioned lock files, do not write `config.platform.php`
into the root `composer.json`. Repeat this process for each minor in the target's
actual support matrix, using a temporary directory and Composer home:

```bash
target_php='<supported-minor>'
lock_tmp_dir="$(mktemp -d)"
composer_home_dir="$lock_tmp_dir/composer-home"
mkdir -p "$composer_home_dir"
cp composer.json "$lock_tmp_dir/composer.json"
COMPOSER_HOME="$composer_home_dir" composer --working-dir="$lock_tmp_dir" config -g platform.php "$target_php.0"
COMPOSER_HOME="$composer_home_dir" composer --working-dir="$lock_tmp_dir" update --no-install --no-audit --no-interaction --no-progress
cp "$lock_tmp_dir/composer.lock" "composer.lock.php-$target_php"
```

## Actions Best Practices

Apply these rules to every workflow created by this skill:

- Pin every external action with the full 40-character commit SHA.
- Keep workflow YAML free of explanatory comments, including release-tag comments
  after action SHAs. Put rationale in the setup skill or project developer
  documentation and use clear job and step names in the workflow itself.
- Verify each SHA from the action's original repository:
  ```bash
  gh release view --repo actions/checkout --json tagName,publishedAt,url
  git ls-remote --tags https://github.com/actions/checkout.git 'refs/tags/<tag>'
  ```
- Use top-level `permissions: contents: read` and only grant additional
  permissions for a step that truly needs them.
- Use `pull_request`, not `pull_request_target`, for untrusted PR code.
- Use `concurrency` with PR number or ref and `cancel-in-progress: true`.
- Set `fail-fast: false` for version matrices so all supported minors report.
- Add `timeout-minutes` to jobs.
- Give every job and every step a clear `name`.
- Do not use `continue-on-error` for required lint or test gates.
- When versioned lock files are used, copy the matching lock to `composer.lock`
  before validation and installation, then install with locked dependencies and
  `require-lock-file: true`. Run `composer check-platform-reqs` after installation.
- When no lock file is committed, do not claim `dependency-versions: locked` and
  do not set `require-lock-file: true`; deliberately install the dependency mode
  the project chose.
- Let `ramsey/composer-install` handle Composer caching; do not add a second
  Composer cache step unless the project has a measured reason.

Job timeouts, PHP memory limits, and worker counts are operational starting
points. Measure sustained runtime, peak memory, and runner capacity, then adjust
them with headroom. Never use those settings to narrow a gate, skip targets, or
turn a quality failure into a pass. The template's values are not target-project
facts.

## Updating Action Pins

When refreshing the template, resolve current releases and SHAs before editing:

```bash
gh release view --repo actions/checkout --json tagName,publishedAt,url
git ls-remote --tags https://github.com/actions/checkout.git 'refs/tags/<tag>'

gh release view --repo shivammathur/setup-php --json tagName,publishedAt,url
git ls-remote --tags https://github.com/shivammathur/setup-php.git 'refs/tags/<tag>'

gh release view --repo ramsey/composer-install --json tagName,publishedAt,url
git ls-remote --tags https://github.com/ramsey/composer-install.git 'refs/tags/<tag>'
```

Use the SHA returned for the exact tag. Do not use a moving branch, a major tag,
or an abbreviated SHA. Do not append the tag as a YAML comment.

## Verification

After editing the workflow:

```bash
git diff --check
go run github.com/rhysd/actionlint/cmd/actionlint@latest .github/workflows/ci.yml
```

Run local Composer checks that are reasonably available:

```bash
composer validate --strict --no-check-publish
# Repeat validation with each target-project lock when versioned locks exist.
composer format:check
composer phpstan
composer compat
composer loc-guard
composer tree-guard
composer deptrac
composer test:unit
composer test
```

Then check that nothing configured was left out of the workflow. Every Composer
script the project treats as a gate should appear in `ci.yml`:

```bash
composer run-script --list
grep -o 'composer [a-z:-]*' .github/workflows/ci.yml | sort -u
```

If a local check cannot run because of the local PHP version or missing tools,
state that clearly and rely on the CI job that covers it.

## References

- [GitHub Actions security hardening](https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions)
- [GitHub Actions workflow syntax](https://docs.github.com/en/actions/using-workflows/workflow-syntax-for-github-actions)
- [Composer Install action](https://github.com/ramsey/composer-install)
