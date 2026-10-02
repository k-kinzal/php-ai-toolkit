# Guard

Guard combines source metrics, directory constraints, Markdown structure and configuration-value policies in `guard.yaml`. Install `k-kinzal/guard-php` to publish `vendor/bin/guard`. PHP 8.0 or newer and the DOM extension are required.

## Commands

```sh
vendor/bin/guard init
vendor/bin/guard check
vendor/bin/guard apply --dry-run
vendor/bin/guard apply
vendor/bin/guard check --format=json
```

`--config=path/to/guard.yaml` selects another policy. All target paths are relative to that policy's directory, regardless of the working directory. `init` refuses to overwrite an existing policy and does not change tool settings. It detects Composer source roots and installed PHPStan/PHPUnit versions, includes the toolkit's current metric and naming defaults, and recommends Composer's sorted package settings. Existing `loc.yaml`, `tree.yaml` and `doc-guard.yaml` are imported with their thresholds and constraints intact.

Exit codes are `0` for success (including recommendations), `1` for required violations and `2` for invalid configuration, malformed documents or an operational failure. A successful dry run means the proposed changes would satisfy the configuration rules; it makes no writes.

## Project policy

The schema organizes responsibilities rather than nesting three old command configurations:

- `scope.source` and `scope.exclude` select PHP files for metric checks.
- `quality.profiles`, `quality.default` and `quality.assignments` define metric policies and path-specific assignments. Profile `extends` and all existing limits keep their previous meaning.
- `structure.paths`, `structure.exclude` and `structure.directories` declare directory rules. Every matching rule is enforced.
- `documentation.files` and `documentation.scan` declare Markdown headings and discover undeclared documents. `documentation.exclude` excludes specified paths from discovery, while explicitly declared documents are still checked.
- `configuration` contains named field constraints, shared by `check` and `apply`.

```yaml
version: 1
scope:
  source: [src]
  exclude: []
quality:
  profiles:
    standard:
      limits:
        file: {lines: 500, ncloc: 350}
        class: {lines: 400}
        trait: {lines: 300}
        interface: {lines: 200}
        enum: {lines: 200}
        function: {lines: 50, cyclomatic_complexity: 20}
        method: {lines: 50, cyclomatic_complexity: 20}
  default: standard
  assignments: []
structure:
  paths: [src]
  directories:
    - path: 'src/**'
      allow: ['*.php']
      deny: ['*Helper.php', '*Manager.php', '*Service.php']
      forbid_empty: true
      file_case: pascal
      dir_case: pascal
      max_files: 15
      max_dirs: 20
documentation:
  files:
    README.md:
      headings: ['# Product', '## Usage']
  scan: [README.md]
configuration:
  - id: application.mode
    file: config.json
    select: /mode
    assert: {equals: A}
  - id: application.driver
    file: settings.yaml
    select: /driver
    assert: {one_of: [A, B]}
    repair: A
  - id: application.workers
    file: settings.toml
    select: /server/workers
    assert: {min: 1}
    repair: 1
  - id: phpstan.level
    file: phpstan.neon
    select: /parameters/level
    level: recommended
    assert: {equals: max}
  - id: phpunit.strict-output
    file: phpunit.xml.dist
    format: xml
    select: /phpunit/@beStrictAboutOutputDuringTests
    assert: {equals: 'true'}
```

Sections other than `version` are optional so a policy can check only configuration data. Unknown keys, duplicate rule IDs, invalid types and contradictory bounds are errors. For the complete existing constraints, see [source metrics](loc-guard.md), [directory structure](tree-guard.md) and [Markdown structure](doc-guard.md).

## Required values and recommendations

`level: required` is the default and fails the check. `level: recommended` emits a warning and keeps exit code zero. `apply` can repair both levels.

`equals` uses an exact, typed value. `one_of` permits any listed value. `min` and `max` constrain numbers, including both endpoints. Multiple assertions on a field must all pass. Missing fields violate a rule even when its expected value is null; explicit null remains distinct from absence.

An `equals` assertion supplies its own repair. Other assertions require an explicit `repair` before Guard can apply a change. The repair must satisfy all assertions. A compliant value is retained, so an allowed `B` is not replaced with the fallback `A`. Without a repair, Guard reports the required manual change. This follows Syncer's distinction between a fixed value and retaining any value that satisfies constraints.

Guard plans every configuration file before writing. It rechecks overlapping rules against the proposed final document. An unresolved required configuration violation blocks all configuration writes, including changes in other files. Source size, naming and Markdown violations remain required failures after apply; these require code or document editing because deleting code, renaming arbitrary files or discarding document content is not a deterministic repair.

## Formats and preservation

| Format | Selector | Notes |
| --- | --- | --- |
| JSON | JSON Pointer, such as `/config/sort-packages` | Object/list distinctions and scalar types are retained. |
| YAML | JSON Pointer | Uses Symfony YAML's data model; custom object tags are rejected. |
| NEON | JSON Pointer | Uses Nette NEON; ordinary maps/lists and scalar fields are editable. |
| TOML | JSON Pointer | Uses the PHP 8.0-compatible `yosymfony/toml` parser (TOML 0.4 syntax). Unsupported newer syntax fails without writing. |
| XML | XPath selecting one node | Existing leaf elements and attributes are supported; missing attributes can be created on an existing element. |

Use `format` when the final filename extension is not the format, for example `phpunit.xml.dist`. JSON Pointer escapes `/` as `~1` and `~` as `~0`. Array selectors address existing indices; Guard does not silently extend lists or replace scalar parents. XML values are strings, so quote booleans in YAML. Numeric XML text can be checked with `min`/`max`. DTD/entity declarations and ambiguous XML selectors are rejected. Namespace-aware XML can be selected with XPath `local-name()` and `namespace-uri()` predicates.

Unchanged files remain byte-for-byte identical. Changed JSON/YAML/NEON/TOML documents are serialized again: indentation and comments may change. XML comments and unrelated nodes are retained. Serialization is checked by parsing the result again; a change in unrelated data types prevents the write. TOML cannot represent null. Unsupported values must be corrected in the policy instead of being coerced.

Targets must be existing regular files inside the project; traversal and symlink targets are rejected. A policy cannot edit itself. Guard checks for concurrent file changes and uses same-directory temporary files plus atomic replacement, retaining permissions. Each replacement is atomic; a filesystem failure partway through a multi-file commit can leave earlier replacements applied. Fix that failure and rerun `guard check`.

## Repository migration

The monorepo's `guard.yaml` carries the former source thresholds and directory/document constraints, with paths updated for `packages/*`. Local AI instructions, hooks and `.entire` state are ignored by Git and retained on disk. Root Markdown discovery still uses `*.md`; only the local `AGENTS.md` and `CLAUDE.md` files are excluded. Product skills under `skills/` remain distributable toolkit content.

The legacy analyzer classes and package-local `bin/loc-guard`, `bin/tree-guard` and `bin/doc-guard` remain available for existing callers supplying their old configuration files. New Composer installations expose the unified `guard` command; the root Composer aliases for the three old checks all run `guard check`.
