---
name: setup-toolkit-github-actions
description: >-
  Set up GitHub Actions CI for php-ai-toolkit PHP projects. Use when asked to
  create or update .github/workflows/ci.yml, run toolkit checks in CI, pin
  GitHub Actions to full commit SHAs, align CI with supported PHP versions, or
  harden workflow permissions and concurrency for Composer, PHPUnit, PHPStan,
  PHP-CS-Fixer, PHPCompatibility, LocGuard, and Deptrac.
---

# Setup GitHub Actions CI

This skill configures GitHub Actions so every php-ai-toolkit gate is visible in
CI and the workflow follows Actions security and maintainability practices.

## Discover Project Constraints

Read these files before editing CI:

- `composer.json`: `require.php`, `config.platform.php`, and Composer scripts.
- Existing `.github/workflows/*.yml` or `.yaml`.
- Toolkit configs that imply CI gates: `.php-cs-fixer.dist.php`,
  `phpstan.neon`, `phpcs.xml.dist`, `loc.yaml`, `deptrac.yaml`,
  `phpunit.xml.dist`.
- Project docs that declare supported PHP versions.
- Composer lock policy: one normal `composer.lock`, no committed lock, or
  PHP-versioned locks such as `composer.lock.php-8.0`.

If the declared PHP floor, Composer constraint, docs, and CI matrix disagree,
surface the conflict and make CI match the declared support policy.

## Template

Read the template from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-github-actions/ci.yml`
and apply it to the project root as `.github/workflows/ci.yml`.

If a workflow already exists, merge rather than blindly replacing it.

## Required CI Gates

The workflow must make the toolkit checks observable as separate named steps.
Do not hide all lint gates behind a single unnamed `composer lint` step.

Required gates when the corresponding script/config exists:

- `composer format:check` for PHP-CS-Fixer.
- `composer phpstan` for PHPStan and toolkit PHPStan rules.
- `composer compat` for PHPCompatibility.
- `composer loc-guard` for LocGuard.
- `composer deptrac` for Deptrac.
- `composer test:unit`, `composer test:unit:legacy`, or `composer test` for PHPUnit.

Keep `compat` inside the `lint` job alongside formatting, PHPStan, LocGuard, and
Deptrac. It may be a separate step for visibility, but it should not be a
separate CI job unless the project has an explicit reason.

If a script is missing but the config exists, add the Composer script using the
corresponding setup skill before wiring CI. If neither script nor config exists,
do not invent the gate in CI; set up that tool first.

## PHP Version Coverage

CI must match the project's declared support range. Do not compensate for a
bad Composer constraint by narrowing the workflow matrix.

1. Determine the supported PHP range from `composer.json require.php` and the
   project docs.
2. List every supported minor version in the `tests` and `lint` matrices.
3. Prefer the project's existing lock policy. A normal single `composer.lock`
   is fine for most applications. Use PHP-versioned lock files only when a
   supported older PHP minor needs a different dependency graph and the project
   has a supply-chain requirement to pin those graphs, which is common for
   libraries and developer tools.
4. If the project uses PHP-versioned lock files, make sure every matrix minor
   has a matching file such as `composer.lock.php-8.0`.
5. Check whether dev dependencies can install on each minor:
   ```bash
   cp composer.lock.php-8.0 composer.lock
   composer validate --strict --no-check-publish
   ```
6. If dependencies do not install on a supported PHP minor, fix the Composer
   constraints first. For example, a PHP 8.0 project that tests with PHPUnit
   must allow a PHPUnit 9.6 / ParaTest 6 line in addition to newer PHPUnit lines.
7. Run the lint gates as named steps inside the `lint` job on the supported
   PHP matrix.

Never use `--ignore-platform-reqs` to make a lower PHP job pass. That hides a
real compatibility problem.

For this package, `composer.json` supports PHP `^8.0`, so both the `tests` and
`lint` jobs include PHP 8.0 through 8.5. Because the developer-tool dependency
graph differs substantially between PHP 8.0 and newer PHP versions, and the
project wants supply-chain pinning, this repository keeps one lock file per PHP
minor (`composer.lock.php-8.0`, `composer.lock.php-8.1`, etc.). Each matrix job
must copy the matching versioned lock to `composer.lock` before running
Composer install.

When generating PHP-versioned lock files, do not write `config.platform.php`
into the root `composer.json`. Generate each lock in a temporary directory with
a temporary Composer home:

```bash
for php in 8.0 8.1 8.2 8.3 8.4 8.5; do
  tmpdir="$(mktemp -d)"
  home="$tmpdir/composer-home"
  mkdir -p "$home"
  cp composer.json "$tmpdir/composer.json"
  COMPOSER_HOME="$home" composer --working-dir="$tmpdir" config -g platform.php "$php.0"
  COMPOSER_HOME="$home" composer --working-dir="$tmpdir" update --no-install --no-audit --no-interaction --no-progress
  cp "$tmpdir/composer.lock" "composer.lock.php-$php"
done
```

## Actions Best Practices

Apply these rules to every workflow created by this skill:

- Pin every external action with the full 40-character commit SHA.
- Keep the release tag as an inline comment next to the SHA for reviewability.
- Verify each SHA from the action's original repository:
  ```bash
  gh release view --repo actions/checkout --json tagName,publishedAt,url
  git ls-remote --tags https://github.com/actions/checkout.git 'refs/tags/v7.0.0'
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
  before validation and installation, then install with locked dependencies.
- Let `ramsey/composer-install` handle Composer caching; do not add a second
  Composer cache step unless the project has a measured reason.

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
or an abbreviated SHA.

## Verification

After editing the workflow:

```bash
git diff --check
go run github.com/rhysd/actionlint/cmd/actionlint@latest .github/workflows/ci.yml
```

Run local Composer checks that are reasonably available:

```bash
composer validate --strict --no-check-publish
for php in 8.0 8.1 8.2 8.3 8.4 8.5; do
  cp "composer.lock.php-${php}" composer.lock
  composer validate --strict --no-check-publish
done
composer format:check
composer phpstan
composer compat
composer loc-guard
composer deptrac
composer test:unit
composer test:unit:legacy # when the project keeps a PHPUnit 9 config for old PHP support
```

If a local check cannot run because of the local PHP version or missing tools,
state that clearly and rely on the CI job that covers it.

## References

- [GitHub Actions security hardening](https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions)
- [GitHub Actions workflow syntax](https://docs.github.com/en/actions/using-workflows/workflow-syntax-for-github-actions)
- [Composer Install action](https://github.com/ramsey/composer-install)
