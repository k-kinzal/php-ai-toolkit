# Use cases

Run examples from the project root and adapt paths to its manifests. When reusing
an existing Composer script, inspect its options first: adding `--packages` to a
script that already sets it widens the selection rather than replacing it.

## Select packages and dependencies

```bash
vendor/bin/docgen --packages=packages/billing --output=build/billing-docs
vendor/bin/docgen --packages='.,packages/*' --exclude='*/tests/Fixture/*'
vendor/bin/docgen --vendor='acme/*' --vendor-dev='phpunit/*'
```

Single-package projects need no `--packages` option. In a monorepo, explicitly
select the intended package if sibling packages should be absent. Keep normal and
diff runs in the same working directory with the same scope and input paths.
After narrowing the scope, check the package list and that old pages disappear
from the cached output.

Use dependency flags only when dependency documentation is wanted. Unmatched
package-name globs and packages without autoloadable PHP sources emit warnings.

## Generate a public API site

```bash
vendor/bin/docgen --public-api --output=build/public-docs
```

Only top-level declarations carrying `@visibility public` get API pages. Their
public and protected members remain unless a member narrows its own visibility;
private members, test references, call sites, and relation indexes are omitted.
Untagged declarations are absent even when PHP makes them accessible.

Source pages still show entire files containing selected declarations. README and
Markdown pages also remain. Review those inputs before using this mode for a
public site. Diff mode applies the selection to both revisions.

## Include coverage, architecture, and examples

```bash
vendor/bin/phpunit --coverage-xml build/coverage-xml
vendor/bin/docgen --coverage=build/coverage-xml --deptrac=deptrac.yaml
```

Prefer the project's existing coverage command when it selects a PHPUnit version
or configuration. Coverage requires PCOV or Xdebug. DocGen consumes the report;
it does not run tests. Root `deptrac.yaml` is discovered automatically, so specify
`--deptrac` only for another path.

PHPDoc `@example` blocks and PHP fences using doctest assertions (`// =>`,
`// Output:`, `// throws`) appear as examples without an extra CLI option. Their
run buttons provide PHPUnit commands; generating the site does not execute them.

## Compare revisions

```bash
vendor/bin/docgen --diff=main --output=build/docs-diff
vendor/bin/docgen --diff=v1.0.0..HEAD --output=build/docs-diff
vendor/bin/docgen --base=main --head=feature --serve
```

Replace example revisions with existing refs. A single base compares against the
working tree; a range compares committed revisions. Git must be available and the
refs must exist locally. DocGen uses temporary worktrees and analyzes both sides.
In CI, fetch the required history before generation.

Keep packages, exclusions, public API selection, and other content options the
same as the ordinary site. Coverage applies only to the head side and must match
that revision. Inspect the generated Off, Diff, and Changes views, including a
removed declaration when present.

## Reuse caches and control resources

```bash
vendor/bin/docgen --cache-dir=build/docgen-cache
vendor/bin/docgen --no-cache --output=build/docs-fresh
vendor/bin/docgen --clear-cache --jobs=4 --memory-limit=1G
```

Runs are incremental by default and report reused sources and pages. Use
`--no-cache` when checking a full rebuild, or `--clear-cache` to discard saved
state. Choose workers and memory for the machine; many vendor packages may need
more than the automatic `512M` floor. Keep cache and output as ignored build
artifacts.

## Reuse a command with Composer

When a reusable project command is requested, keep shared options in one script
and pass run-specific values through Composer:

```json
{
  "scripts": {
    "docgen": "docgen --exclude='tests/Fixture/*'",
    "docgen:serve": "@docgen --serve"
  }
}
```

```bash
composer docgen -- --base-url=https://example.github.io/project
composer docgen -- --diff=main --output=build/docs-diff
```

Reuse existing scripts and their scope. Documentation generation is separate from
`lint`; coverage is an input to generate first when the command requires it.
