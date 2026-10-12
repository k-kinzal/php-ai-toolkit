#!/usr/bin/env bash
set -euo pipefail

package_dir="${1:?Usage: push-package.sh PACKAGE_DIR CHECKOUT SOURCE_SHA [TAG]}"
checkout="${2:?Pass the split repository checkout.}"
source_sha="${3:?Pass the source monorepo commit.}"
tag="${4:-}"

rsync -a --delete --exclude '/.git/' "$package_dir/" "$checkout/"
cd "$checkout"
git config user.name 'github-actions[bot]'
git config user.email '41898282+github-actions[bot]@users.noreply.github.com'
git add --all
tree="$(git write-tree)"
message="Sync from k-kinzal/php-ai-toolkit@$source_sha"

if [ -n "$tag" ]; then
  # A release may refer to an older source commit. Publish its snapshot as a tag
  # without moving main backwards or replacing an already published release.
  if git rev-parse --verify "refs/tags/$tag" >/dev/null 2>&1; then
    if [ "$(git rev-parse "refs/tags/$tag^{tree}")" != "$tree" ]; then
      echo "Tag '$tag' already contains different files; publish a new version instead." >&2
      exit 1
    fi
    echo "Tag '$tag' already contains this package."
    exit 0
  fi
  commit="$(git commit-tree "$tree" -p HEAD -m "$message")"
  git tag -a "$tag" "$commit" -m "$tag from php-ai-toolkit@$source_sha"
  git push origin "refs/tags/$tag"
else
  if git diff --cached --quiet; then
    echo 'The split repository already contains this package.'
    exit 0
  fi
  git commit --quiet -m "$message"
  git push origin HEAD:refs/heads/main
fi
