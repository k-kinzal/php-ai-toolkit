# Publishing the Composer packages

Development takes place on `main` in `k-kinzal/php-ai-toolkit`. Each directory in
`packages/` has a public mirror at `https://github.com/k-kinzal/<directory>`.
Submit these six mirror URLs to [Packagist](https://packagist.org/packages/submit),
not the monorepo URL. Submit `docgen-php`, `doctest-php`, `guard-php`, and
`phpunit-ai-reporter` before the PHPStan packages, which depend on them.

The [Split Monorepo workflow](../.github/workflows/split.yml) validates the
distribution archives and installs all six packages in a fresh consumer project
on PHP 8.0 and 8.5 before publishing. It runs on pushes to `main`, stable `v*`
tags, and manual dispatches. Pull requests only run validation.

## What is published

Each mirror contains the committed package directory, with these changes:

- Remove monorepo path repositories and every development lock file.
- Set internal package dependencies to `dev-main` on main, or `^MAJOR.MINOR.PATCH`
  for a release. All six packages share the same release version.
- Add documentation, source, and issue tracker URLs to Composer metadata.
- Add a mirror notice to the README and omit tests and development tool
  configuration from GitHub distribution archives using `.gitattributes`.

The original manifests and lock files stay in the monorepo for development.
Do not edit the mirrors: the next synchronization replaces their files. Existing
commits remain in their history. The existing `doctest-php` repository is reused
with its earlier history intact.

## Authentication

Each mirror has a write-enabled SSH deploy key named `php-ai-toolkit split`.
The corresponding private key is a repository Actions secret in the monorepo:

| Mirror | Actions secret |
| --- | --- |
| `docgen-php` | `SPLIT_DOCGEN_PHP_SSH_KEY` |
| `doctest-php` | `SPLIT_DOCTEST_PHP_SSH_KEY` |
| `guard-php` | `SPLIT_GUARD_PHP_SSH_KEY` |
| `phpstan-ai-formatter` | `SPLIT_PHPSTAN_AI_FORMATTER_SSH_KEY` |
| `phpstan-guard-rules` | `SPLIT_PHPSTAN_GUARD_RULES_SSH_KEY` |
| `phpunit-ai-reporter` | `SPLIT_PHPUNIT_AI_REPORTER_SSH_KEY` |

To rotate a key, generate an Ed25519 key pair, add its public key as a writable
deploy key on that mirror, replace the matching Actions secret with the private
key, verify a manual split run, and then remove the old deploy key. Each key can
write only to its own mirror; the workflow's `GITHUB_TOKEN` only reads the monorepo.

## Releasing

The planned first release is `0.1.0`. Initial setup publishes only `main`; it does
not create a release tag. Packagist can index that branch as `dev-main`.

Wait for CI to pass on the intended `main` commit, then create and push an
annotated stable tag on the monorepo, for example:

```sh
git tag -a v0.1.0 -m 'Release v0.1.0'
git push origin v0.1.0
```

The split workflow creates the same tag in all six mirrors. Tag snapshots do not
move mirror `main` branches, so publishing an older release cannot roll back
development. Re-running a tag succeeds if its files already match and fails if
the tag would change. Publish a new version instead of moving existing tags.
Wait for each release run to finish before pushing the next release tag.

After the first successful split, submit each mirror on Packagist. Connect the
[Packagist GitHub hook](https://packagist.org/about#how-to-update-packages) so later
pushes update the package automatically. Until a stable tag exists, consumers
must explicitly allow `dev-main` for every toolkit dependency they use.

## Local verification

Run `bash .github/scripts/test-split.sh` with PHP, Composer, Git, jq, and rsync
installed. It builds archives from `HEAD`, checks Composer publication metadata,
installs both development and stable distributions, exercises the CLI entry
points, and tests synchronization against a temporary local Git remote.
