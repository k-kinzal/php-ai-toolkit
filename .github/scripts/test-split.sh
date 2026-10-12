#!/usr/bin/env bash
set -euo pipefail

root="$(git rev-parse --show-toplevel)"
test_dir="$(mktemp -d)"
trap 'rm -rf "$test_dir"' EXIT

# Use real Git archives as Composer dist packages, including export-ignore rules.
# Both release and dev dependencies must resolve without the monorepo path repos.
for version in dev-main 0.1.0; do
  stage="$test_dir/$version"
  mkdir -p "$stage/consumer"
  definitions="$stage/packages.jsonl"
  tag=''
  if [ "$version" != dev-main ]; then
    tag="v$version"
  fi
  for manifest in "$root"/packages/*/composer.json; do
    package="$(basename "$(dirname "$manifest")")"
    output="$stage/$package"
    bash "$root/.github/scripts/prepare-package.sh" "$package" "$output" "$tag"
    git -C "$output" init --quiet --initial-branch=main
    git -C "$output" add --all
    git -C "$output" -c user.name=Test -c user.email=test@example.com commit --quiet -m Package
    git -C "$output" archive --format=zip --output="$stage/$package.zip" HEAD
    jq --arg version "$version" --arg url "$stage/$package.zip" '
      . + {version: $version, dist: {type: "zip", url: $url}}
    ' "$output/composer.json" >> "$definitions"
  done
  jq -s --arg version "$version" '{
    name: "test/split-consumer",
    require: (map({key: .name, value: $version}) | from_entries),
    repositories: [{type: "package", package: .}],
    config: {"allow-plugins": false}
  }' "$definitions" > "$stage/consumer/composer.json"
  composer update --working-dir="$stage/consumer" --no-interaction --no-plugins --no-scripts --prefer-dist
  composer check-platform-reqs --working-dir="$stage/consumer"
  "$stage/consumer/vendor/bin/guard" --help >/dev/null
  "$stage/consumer/vendor/bin/docgen" --help >/dev/null
  cat > "$stage/consumer/phpstan.neon" <<'EOF'
includes:
    - vendor/k-kinzal/phpstan-guard-rules/rules.neon
    - vendor/k-kinzal/phpstan-ai-formatter/extension.neon
parameters:
    level: 1
    paths:
        - smoke.php
    toolkit:
        allRules: false
EOF
  printf '<?php\ndeclare(strict_types=1);\necho "Package smoke test";\n' > "$stage/consumer/smoke.php"
  "$stage/consumer/vendor/bin/phpstan" analyse --configuration="$stage/consumer/phpstan.neon" --no-progress
  php -r '
    require $argv[1] . "/vendor/autoload.php";
    foreach (glob($argv[1] . "/vendor/k-kinzal/*/composer.json") as $file) {
        $manifest = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (isset($manifest["repositories"]) || is_file(dirname($file) . "/composer.lock")) {
            throw new RuntimeException("Monorepo development configuration leaked into " . $file);
        }
        if (is_dir(dirname($file) . "/tests") || !is_file(dirname($file) . "/LICENSE")) {
            throw new RuntimeException("Incorrect distribution contents in " . $file);
        }
    }
  ' "$stage/consumer"
done

# Exercise actual pushes locally: updates, unchanged reruns, immutable releases,
# and releasing an older snapshot without rolling main back.
git init --quiet --bare --initial-branch=main "$test_dir/remote.git"
git clone --quiet "$test_dir/remote.git" "$test_dir/mirror"
git -C "$test_dir/mirror" -c user.name=Test -c user.email=test@example.com commit --quiet --allow-empty -m Initialize
git -C "$test_dir/mirror" push --quiet origin main
snapshot="$test_dir/dev-main/docgen-php"
rm -rf "$snapshot/.git"
bash "$root/.github/scripts/push-package.sh" "$snapshot" "$test_dir/mirror" first
first="$(git -C "$test_dir/mirror" rev-parse HEAD)"
bash "$root/.github/scripts/push-package.sh" "$snapshot" "$test_dir/mirror" first
test "$first" = "$(git -C "$test_dir/mirror" rev-parse HEAD)"
echo 'Newer main snapshot' >> "$snapshot/README.md"
bash "$root/.github/scripts/push-package.sh" "$snapshot" "$test_dir/mirror" second
main="$(git -C "$test_dir/mirror" rev-parse HEAD)"
release="$test_dir/0.1.0/docgen-php"
rm -rf "$release/.git"
bash "$root/.github/scripts/push-package.sh" "$release" "$test_dir/mirror" first v0.1.0
bash "$root/.github/scripts/push-package.sh" "$release" "$test_dir/mirror" first v0.1.0
test "$main" = "$(git --git-dir="$test_dir/remote.git" rev-parse main)"
echo 'Conflicting release' >> "$release/README.md"
if bash "$root/.github/scripts/push-package.sh" "$release" "$test_dir/mirror" first v0.1.0; then
  echo 'A published tag was unexpectedly replaced.' >&2
  exit 1
fi
echo 'Package installation and split push checks passed.'
