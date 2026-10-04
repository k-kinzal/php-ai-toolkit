# Guard

Guard combines source metrics, directory constraints, Markdown structure and configuration-value policies in `guard.yaml`. Install `k-kinzal/guard-php` to publish `vendor/bin/guard`. PHP 8.0 or newer and the DOM extension are required.

## Commands

```sh
vendor/bin/guard init
vendor/bin/guard init --import=quality,structure,phpstan
vendor/bin/guard check
vendor/bin/guard apply --dry-run
vendor/bin/guard apply
vendor/bin/guard check --format=json
```

`--config=path/to/guard.yaml` selects another policy. All target paths are relative to that policy's directory, regardless of the working directory. `init` refuses to overwrite an existing policy and does not change tool settings. With no `--import`, it writes imports for the shipped presets that match the project. `--import` names presets explicitly and does not add the ones it omits. Existing `loc.yaml`, `tree.yaml` and `doc-guard.yaml` are copied into the project file. `loc.yaml` and `tree.yaml` suppress the `quality` and `structure` imports so those thresholds stay intact.

Exit codes are `0` for success (including recommendations), `1` for required violations and `2` for invalid configuration, malformed documents or an operational failure. A successful dry run means the proposed changes would satisfy the configuration rules; it makes no writes.

## Project policy

The schema organizes responsibilities rather than nesting three old command configurations:

- `scope.source` and `scope.exclude` select PHP files for metric checks.
- `quality.profiles`, `quality.default` and `quality.assignments` define metric policies and path-specific assignments. Profile `extends` and all existing limits keep their previous meaning.
- `structure.paths`, `structure.exclude` and `structure.directories` declare directory rules. Every matching rule is enforced.
- `documentation.files` and `documentation.scan` declare Markdown headings and discover undeclared documents. `documentation.exclude` excludes specified paths from discovery, while explicitly declared documents are still checked.
- `configuration` contains named field constraints, shared by `check` and `apply`.
- `imports` lists other guard documents to load before the project file. Omit it to define the whole policy in the project file.

## Imports

Paths in `imports` are relative to the file that lists them. An imported file may import more files. Guard loads imports in order, then applies the current file. A later file overrides an earlier one, and the project file overrides all of them. A circular import is an error.

Shipped presets live in `vendor/k-kinzal/guard-php/rules/`:

- `quality.yaml` and `structure.yaml` are the generic metric and directory rules.
- `phpstan.yaml` and `phpstan-guard-rules.yaml` check the PHPStan level, includes, and toolkit rules.
- `phpunit9.yaml` through `phpunit13.yaml` check the PHPUnit major that each configuration file targets. `doctest9.yaml` through `doctest13.yaml` check executable PHPDoc examples.
- `php-cs-fixer.yaml`, `phpcs.yaml`, `deptrac.yaml`, `infection.yaml`, and `composer.yaml` check the core toolkit setup.
- `github-actions.yaml` and `mutation.yaml` check the pull-request workflow and the scheduled mutation workflow.
- `pbt.yaml`, `fuzz.yaml`, `phpbench.yaml`, and `docgen.yaml` apply only when those tools are adopted.

Import only the presets you want. A section that is neither imported nor written in the project file is not checked. Writing the rules in the project file and listing no imports is a complete custom policy.

Overrides keep the imported values for every key you leave out:

- A `configuration` entry with the same `id` replaces only the keys it sets. Point a shipped rule at `phpstan.neon.dist` without copying its assertion.
- `quality.profiles` merges by profile name. A limit replaces only the metrics you set.
- `structure.directories` merges by `path`. A directory rule replaces only the keys you set, and a new path is added.
- `scope` replaces the imported scope when the project file sets it.
- `documentation.files` replaces one document at a time. `scan` and `exclude` replace the imported lists when they are set.

`guard init` writes imports for the presets that match the project. `quality` and `structure` are included when source roots exist. PHPStan, PHP-CS-Fixer, PHPCompatibility, Deptrac, Infection, and Composer are included when their configuration or package is present. Each PHPUnit major gets its own preset: versioned files such as `phpunit10.xml.dist` select that major, and `phpunit.xml.dist` is PHPUnit 13 when those versioned files exist. A project with only `phpunit.xml.dist` uses the lock version, or a constraint that names one major. Doctest presets are added when that PHPUnit file already contains a doctest suite, or when PHPStan requires public-API examples. Property-based tests, fuzzing, PHPBench, and DocGen are added only when those packages are installed. GitHub Actions presets are added when `.github/workflows/ci.yml` or `mutation.yml` exists. When the detected file is not the preset default, init writes an `id` and `file` override. Source roots other than `src` get extra `structure.directories` entries that use the same constraints.

```yaml
version: 1
imports:
  - vendor/k-kinzal/guard-php/rules/quality.yaml
  - vendor/k-kinzal/guard-php/rules/phpstan.yaml
scope:
  source: [src]
  exclude: []
quality:
  profiles:
    standard:
      limits:
        file: {lines: 800}
configuration:
  - id: phpstan.level
    file: phpstan.neon.dist
```

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

`equals` uses an exact, typed value. `one_of` permits any listed value. `min` and `max` constrain numbers, including both endpoints. `contains` matches text inside a string or anywhere in a nested document. `contains_any` matches one of several include names: a name that contains `/` matches a path fragment, and a bare name matches the whole entry. `not_contains` rejects text. `present` requires the field to exist. `absent` requires it to be missing, and it cannot be combined with another assertion. Multiple assertions on a field must all pass. Missing fields violate a rule even when its expected value is null; explicit null remains distinct from absence. `php` and `json5` rules are check-only: `json5` accepts comments and trailing commas, and `php` reads the literal `setRiskyAllowed` and `setRules` arguments without executing the file.

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

`guard init` writes the project file that imports the matching shipped presets, records Composer source roots and the current README headings, and adds only the overrides the project needs. A project is configured correctly when `guard check` passes that policy.

The legacy analyzer classes and `bin/loc-guard`, `bin/tree-guard` and `bin/doc-guard` remain available for existing callers supplying their old configuration files. New Composer installations expose the unified `guard` command.
