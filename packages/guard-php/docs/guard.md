# Guard

Guard combines source metrics, directory constraints, Markdown structure and configuration-value policies in `guard.yaml`. Install `k-kinzal/guard-php` to publish `vendor/bin/guard`. PHP 8.0 or newer and the DOM extension are required.

## Commands

```sh
vendor/bin/guard init
vendor/bin/guard init --import=metrics,structure,phpstan
vendor/bin/guard check
vendor/bin/guard apply --dry-run
vendor/bin/guard apply
vendor/bin/guard check --format=json
```

`--config=path/to/guard.yaml` selects another policy. All target paths are relative to that policy's directory, regardless of the working directory. `init` refuses to overwrite an existing policy and does not change tool settings. With no `--import`, it writes imports for the shipped presets that match the project. `--import` names presets explicitly and does not add the ones it omits. Existing `loc.yaml`, `tree.yaml` and `doc-guard.yaml` are copied into the project file. `loc.yaml` and `tree.yaml` suppress the `metrics` and `structure` imports so those thresholds stay intact.

Exit codes are `0` for success (including recommendations), `1` for required violations and `2` for invalid configuration, malformed documents or an operational failure. A successful dry run means the proposed changes would satisfy the configuration rules; it makes no writes.

## Project policy

The schema organizes responsibilities rather than nesting three old command configurations:

- `metrics.source` and `metrics.exclude` select PHP files for metric checks. `metrics.profiles`, `metrics.default` and `metrics.assignments` define metric policies and path-specific assignments. Profile `extends` and all existing limits keep their previous meaning.
- `structure.paths`, `structure.exclude` and `structure.directories` declare directory rules. Every matching rule is enforced.
- `documentation.files` and `documentation.scan` declare Markdown headings and discover undeclared documents. `documentation.exclude` excludes specified paths from discovery, while explicitly declared documents are still checked.
- `configuration` contains named field constraints, shared by `check` and `apply`.
- `imports` lists other guard documents to load before the project file. Omit it to define the whole policy in the project file.

## Imports

Paths in `imports` are relative to the file that lists them. An imported file may import more files. Guard loads imports in order, then applies the current file. A later file overrides an earlier one, and the project file overrides all of them. A circular import is an error.

Shipped presets live in `vendor/k-kinzal/guard-php/rules/`:

- `metrics.yaml` and `structure.yaml` are the generic metric and directory rules.
- `phpstan.yaml` and `phpstan-guard-rules.yaml` check the PHPStan level, includes, and toolkit rules.
- `phpunit9.yaml` through `phpunit13.yaml` check the PHPUnit major that each configuration file targets. `doctest9.yaml` through `doctest13.yaml` check executable PHPDoc examples.
- `php-cs-fixer.yaml`, `phpcs.yaml`, `deptrac.yaml`, `infection.yaml`, and `composer.yaml` check the core toolkit setup.
- `github-actions.yaml` and `mutation.yaml` check the pull-request workflow and the scheduled mutation workflow.
- `pbt.yaml`, `fuzz.yaml`, `phpbench.yaml`, and `docgen.yaml` apply only when those tools are adopted.

Import only the presets you want. A section that is neither imported nor written in the project file is not checked. Writing the rules in the project file and listing no imports is a complete custom policy.

Overrides keep the imported values for every key you leave out:

- A `configuration` entry with the same `id` replaces only the keys it sets. Point a shipped rule at `phpstan.neon.dist` without copying its assertion.
- `metrics.profiles` merges by profile name. A limit replaces only the metrics you set. `metrics.source` and `metrics.exclude` replace the imported lists when the project file sets them.
- `structure.directories` merges by `path`. A directory rule replaces only the keys you set, and a new path is added.
- `documentation.files` replaces one document at a time. `scan` and `exclude` replace the imported lists when they are set.

`guard init` writes imports for the presets that match the project. `metrics` and `structure` are included when source roots exist. PHPStan, PHP-CS-Fixer, PHPCompatibility, Deptrac, Infection, and Composer are included when their configuration or package is present. Each PHPUnit major gets its own preset: versioned files such as `phpunit10.xml.dist` select that major, and `phpunit.xml.dist` is PHPUnit 13 when those versioned files exist. A project with only `phpunit.xml.dist` uses the lock version, or a constraint that names one major. Doctest presets are added when that PHPUnit file already contains a doctest suite, or when PHPStan requires public-API examples. Property-based tests, fuzzing, PHPBench, and DocGen are added only when those packages are installed. GitHub Actions presets are added when `.github/workflows/ci.yml` or `mutation.yml` exists. When the detected file is not the preset default, init writes an `id` and `file` override. Source roots other than `src` get extra `structure.directories` entries that use the same constraints.

```yaml
version: 1
imports:
  - vendor/k-kinzal/guard-php/rules/metrics.yaml
  - vendor/k-kinzal/guard-php/rules/phpstan.yaml
metrics:
  source: [src]
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
metrics:
  source: [src]
  exclude: []
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

The executable is `bin/guard`, exposed as `vendor/bin/guard` by Composer. The standalone analyzer classes and individual guard executables have been removed. `guard init` still reads legacy configuration files as migration input; it does not run legacy analyzers. PHP callers use the `Guard\` namespace, mapped directly to `src/`.


## Collection and policies

Every check and apply uses the same pipeline:

1. Registered collectors select inputs, read them, and return structured `Guard\Collect\Subject` values.
2. Registered policies receive subjects matching their registered class or interface and return a `Guard\Execution\Plan`.
3. The CLI commits permitted changes and renders the combined findings through `Guard\Reporting\Reporter`.

| Collector | Collected subject | Built-in policy |
|-----------|-------------------|-----------------|
| `PhpCollector` | `PhpSources`: paths, physical lines, NCLOC, class and function metrics | `LocPolicy` |
| `TreeCollector` | `DirectoryTree`: exclusion-filtered listings and child relationships | `TreePolicy` |
| `MarkdownCollector` | `MarkdownDocuments`: parsed headings, missing files, discovery and exclusions | `DocPolicy` |
| `ConfigurationCollector` | `ConfigurationDocument`: original bytes, parsed values and applicable field rules | `ConfigurationPolicy` |

Collectors do not apply thresholds or produce violations. Policies do not read source files or write repairs. Each selected input is collected once per invocation, then every matching policy consumes that subject. Collectors may yield inputs incrementally, retaining the established error order across configuration files. Findings are assembled in policy registration order, independently of collection order. Markdown collection keeps all heading levels; `DocPolicy` applies each document's `max_level`. Directory policies share a complete tree so subtree totals and depth checks see the same snapshot.

A plan contains findings, proposed byte changes, and findings that block repairs. Required source violations still fail the command while permitting valid configuration repairs. Unsatisfied required configuration constraints block all proposed writes. Applying a configuration policy uses a private document copy; another policy sees the original collected values.

## Registering extensions

`Guard\Extension\Registry` is the explicit extension boundary. `BuiltinExtension` registers the shipped collectors and policies using the same API available to callers. A supplied registry is complete: register `BuiltinExtension` first when extending the standard checks.

```php
use Guard\Cli\Application;
use Guard\Collect\Tree\DirectoryTree;
use Guard\Extension\BuiltinExtension;
use Guard\Extension\Registry;

$registry = new Registry();
(new BuiltinExtension())->register($registry);
$registry->addPolicy('project.directory-policy', DirectoryTree::class, new ProjectDirectoryPolicy());

$app = new Application(getcwd(), static function (string $text): void {
    echo $text;
}, $registry);
exit($app->run(['check']));
```

Implement `Guard\Policy\Policy::evaluate(Subject $information, Context $context): Plan` for a policy. Use `addPolicy($id, $subjectType, $policy)` to associate it with a collected class or interface. Policies run in registration order, and every matching policy runs; registering a second policy does not replace the first. Ids must be non-empty and unique within the collector or policy registry.

To read a new kind of input, implement `Guard\Collect\Collector::collect(Context $context): iterable` and register it with `addCollector($id, $collector)`. Yield subject objects containing the structured information policies need. Register the corresponding policy with the subject's type. If multiple policies propose changes to the same file, identical proposals are combined and conflicting proposals fail before any writes. An extension can implement `Guard\Extension\Extension::register(Registry $registry): void` to package both registrations.

`Context` supplies the validated project configuration, policy file path, and whether repairs are requested. Extension-specific settings can be passed to a collector or policy constructor. Registration is currently a PHP API; automatic Composer discovery and additional YAML extension keys are not enabled.

The complete registry can also run through `Guard\Execution\Pipeline::run(Context $context): Plan` without CLI output or file writes. The engine contains no list of built-in policy types.

## Refactoring compatibility

The CLI contract remains `guard check|apply|init`, the existing `guard.yaml` schema, text and JSON reports, exit codes, import precedence, and repair behavior. The integration suite compares these against output captured before the pipeline refactor, including every target file's bytes after checks, dry runs, repairs, conflicts and repeated repairs. Some diagnostics retain legacy configuration names to preserve existing messages.
