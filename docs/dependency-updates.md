# Dependency updates

[Renovate](https://github.com/apps/renovate) checks the root Composer dependencies
and GitHub Actions. Its repository configuration is in [`renovate.json`](../renovate.json).
Dependency updates, including major versions, merge automatically after CI passes.
Composer ranges are widened instead of
replacing older supported major versions, and the PHP minimum remains unchanged.
The Dependency Dashboard is disabled because this repository does not use GitHub Issues.

## GitHub Apps

Install these official apps on `k-kinzal/php-ai-toolkit` (select only this repository
when choosing repository access):

1. [Renovate](https://github.com/apps/renovate/installations/new) creates and merges
   dependency PRs.
2. [Renovate Approve](https://github.com/apps/renovate-approve/installations/new)
   approves Renovate PRs whose description says automerge is enabled.

App installation is an account setting; merging the configuration PR does not
install either app. No personal access token or new repository secret is needed.
Keep the repository's Pull requests feature enabled so Renovate can open its PRs.

`platformAutomerge` is disabled deliberately: Renovate waits for all checks on the
current revision and an up-to-date branch before merging. This works with the
repository's direct-to-main workflow without introducing required PRs or enabling
GitHub's native auto-merge. If required reviews are added later, permit the
Renovate Approve app's review to satisfy them. See the
[Renovate automerge documentation](https://docs.renovatebot.com/key-concepts/automerge/).

## Composer lock files

`composer.lock` is the PHP 8.5 lock file, used by Renovate, lint, mutation testing,
and documentation publishing. PHP 8.0–8.4 retain their version-specific
`composer.lock.php-*` files. For local development on an older PHP version, copy
the corresponding lock file to `composer.lock` before installing; restore the
tracked PHP 8.5 lock before committing unrelated changes.

Weekly lock maintenance runs before 06:00 Monday in Asia/Tokyo. When Renovate
changes `composer.json` or `composer.lock`, the `Renovate lock files` workflow
resolves all five compatibility locks against the same manifest using Composer's
global PHP platform setting. It never installs dependencies or executes Composer
scripts/plugins. Resolution failures block automerging.

If the locks change, the workflow commits only those files to the Renovate branch
and explicitly dispatches CI on that branch. This is needed because a push with
`GITHUB_TOKEN` does not automatically start workflows. CI validates each lock and
runs tests on all six PHP versions, lint, and mutation testing on changed lines.
Renovate ignores the workflow's commit author when deciding whether it may rebase
the branch. The workflow checks the PR revision before pushing and never force
pushes.

If a lock-refresh run fails after pushing, rerun CI on the Renovate branch with
the base branch as the `base_ref` input. If dependency resolution or CI fails,
repair the dependency update in its PR before merging; do not bypass the checks.
