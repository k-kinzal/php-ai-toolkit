#!/usr/bin/env bash
set -euo pipefail

package="${1:?Usage: prepare-package.sh PACKAGE OUTPUT [vMAJOR.MINOR.PATCH]}"
output="${2:?Pass an empty output directory.}"
tag="${3:-}"
root="$(git rev-parse --show-toplevel)"
cd "$root"

if [[ ! "$package" =~ ^[a-z][a-z0-9-]+$ ]] || [ ! -f "packages/$package/composer.json" ]; then
  echo "Unknown package '$package'; choose a directory in packages/." >&2
  exit 1
fi
if [ -n "$tag" ] && [[ ! "$tag" =~ ^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]]; then
  echo "Invalid release tag '$tag'; use vMAJOR.MINOR.PATCH, for example v0.1.0." >&2
  exit 1
fi
mkdir -p "$output"
if [ -n "$(ls -A "$output")" ]; then
  echo "Output directory '$output' is not empty; prepare into a new directory." >&2
  exit 1
fi

# Export committed files only: never copy local vendor/, caches or build output.
git archive "HEAD:packages/$package" | tar -x -C "$output"
names="$(jq -s 'map(.name)' packages/*/composer.json)"
constraint=dev-main
if [ -n "$tag" ]; then
  constraint="^${tag#v}"
fi
jq --argjson names "$names" --arg constraint "$constraint" --arg package "$package" '
  del(.repositories, .version) |
  .homepage = "https://k-kinzal.github.io/php-ai-toolkit/" |
  .support = ((.support // {}) + {
    source: ("https://github.com/k-kinzal/php-ai-toolkit/tree/main/packages/" + $package),
    issues: "https://github.com/k-kinzal/php-ai-toolkit/issues",
    docs: "https://k-kinzal.github.io/php-ai-toolkit/"
  }) |
  (.require, ."require-dev") |= with_entries(
    if (.key as $name | $names | index($name)) != null
    then .value = $constraint else . end
  )
' "$output/composer.json" > "$output/composer.json.tmp"
mv "$output/composer.json.tmp" "$output/composer.json"
rm -f "$output"/composer.lock "$output"/composer.lock.php-*

# Composer's GitHub dist archives should contain the runtime and documentation.
cat >> "$output/.gitattributes" <<'EOF'
/.gitattributes export-ignore
/.gitignore export-ignore
/.php-cs-fixer.dist.php export-ignore
/deptrac.yaml export-ignore
/guard.yaml export-ignore
/infection.json* export-ignore
/phpcs.xml* export-ignore
/phpstan.neon* export-ignore
/phpunit*.xml* export-ignore
/tests/ export-ignore
EOF
cat > "$output/.gitignore" <<'EOF'
/vendor/
/build/
/composer.lock
/.phpunit.cache/
/.phpunit.result.cache
/.php-cs-fixer.cache
/.deptrac.cache
EOF
awk 'NR == 2 {
  print ""
  print "> Read-only mirror of [php-ai-toolkit](https://github.com/k-kinzal/php-ai-toolkit)."
  print "> Submit changes and issues to the monorepo."
} { print }' "$output/README.md" > "$output/README.md.tmp"
mv "$output/README.md.tmp" "$output/README.md"

composer validate --working-dir="$output" --strict --no-check-lock
