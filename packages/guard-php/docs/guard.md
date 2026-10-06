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
- `extensions` maps Composer-autoloadable extension class names to their option mappings.
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
- `extensions` merges by class name. A later declaration replaces that class's entire option mapping, retaining its registration position; other extension classes remain registered. Options are owned by extensions and are not recursively merged.

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

Every policy follows the same pipeline:

1. Policies declare named inputs: which paths to select and which structure each selection needs.
2. One `Guard\Collect\Collector` combines those requests. A shared directory queue visits overlapping roots together, and each physical directory is read at most once. Literal pattern prefixes skip unrelated ancestors; exact filenames require no directory scan.
3. Each selected file is read at most once. Only requested structures are built, once per physical file and structure id. Metadata-only requests never read file content.
4. Policies evaluate their prepared inputs and return plans. The CLI applies permitted changes and reports the combined findings.

There are no collectors for particular filenames, file types or policy families. `MetricLimits`, `DirectoryEntries`, `HeadingStructure` and `FieldConstraints` are ordinary implementations of the same `Policy` interface. They declare their own inputs; the engine does not recognize their names or divide execution into source, directory and documentation subsystems. The existing YAML sections are converted to policy registrations by `ConfigurationLoader`.

File selection, structuring and policy evaluation are separate contracts:

| Contract | Responsibility |
| --- | --- |
| `Policy::inputs(Context): array` | Declare named `Input` values, each pairing a `Selection` with an optional structure id. |
| `Selection` | Select exact `files`, glob `patterns`, recursive `descendants`, or `directories` with entry metadata. |
| `Structurer::structure(Source): Subject` | Structure already-read bytes without accessing the filesystem. |
| `Policy::evaluate(InputSet, Context): Plan` | Inspect prepared input sets and propose findings or repairs without filesystem access. |

`Selection` accepts paths, exclusions and an optional filename suffix. Recursive file selections use segment-aware exclusions and do not follow directory symlinks. Directory selections provide directory listings, keep the established `fnmatch` exclusions and prune excluded children; they do not allocate a separate file result for every listed entry. Glob selections preserve hidden-name and double-star semantics. Different selections retain their own exclusions while sharing filesystem reads. A null structure id requests metadata alone.

PHP metrics and PHP configuration values depend on the same `php.tokens` structure, so requesting both tokenizes the file once. Markdown headings retain every heading level; policies apply their own level limits. Configuration documents retain their original bytes and provide private editable copies, including independent XML trees. Raw source buffers and intermediate structures are released after fulfilling that file's requests; the collector retains only the results requested by policies.

Policies execute in registration order. Selection and parsing errors are retained with the affected input, preserving error precedence even though collection is shared. A binding's `reportOrder` controls finding order independently of evaluation order; equal values retain registration order. Required source findings still fail the command while permitting valid configuration repairs. Unsatisfied required field constraints block all proposed writes.

## Registering extensions

`Guard\Extension\Registry` registers structures and policies. `BuiltinExtension` registers the built-in structurers and the policy bindings produced by the configuration loader. Declare external extensions in `guard.yaml`; the ordinary `vendor/bin/guard` command loads them through the project's Composer autoloader before collecting any policy inputs:

```yaml
version: 1
extensions:
  Example\Guard\XmlSchemaExtension:
    files: ['**/*.xml']
    exclude: ['vendor/**']
    schema: schemas/catalog.xsd
```

Implement `Guard\Extension\Extension::register(Registry): void` to register policies and reusable structures. An extension with no options needs a public no-argument constructor and is configured with `Your\Extension: {}`. An extension with options implements `Guard\Extension\ConfigurableExtension`, which adds `public static function fromOptions(array $options): self`. The factory validates its own option names and values, returns an extension instance, and reports invalid options with `PolicyException`. Its constructor may take any dependencies; Guard calls the factory. Registration and factories declare work, while collection and evaluation perform it.

Only explicitly configured classes are loaded; Guard does not scan installed packages for extensions or require arbitrary PHP files from YAML. Add project extensions to Composer's `autoload` or `autoload-dev` and run `composer dump-autoload`. Install third-party extensions with Composer and use their documented class names. Classes must implement the interface; missing classes, unsupported options and duplicate registry ids produce a named configuration error and exit code 2. Extension code runs as trusted project code.

Configured extensions register in configuration order after the built-in registrations. Namespace policy and structure ids, for example `company.xml-schema`, to avoid collisions. Each run constructs optionless extensions or invokes each configured factory again, using a registry scoped to that run. All policy input requirements then enter the same Collector: an extension adds neither another directory scan nor another read or parse of an already-requested file and structure.

Programmatic registration is also supported. A supplied registry contains the caller's base registrations: register `BuiltinExtension` first when extending the standard checks. Guard clones this registry and appends configured extensions for each run, so repeated runs do not accumulate registrations.

```php
use Guard\Cli\Application;
use Guard\Config\ConfigurationLoader;
use Guard\Extension\BuiltinExtension;
use Guard\Extension\Registry;

$configuration = (new ConfigurationLoader())->load(__DIR__ . '/guard.yaml');
$registry = new Registry();
(new BuiltinExtension($configuration))->register($registry);
$registry->addPolicy('project.required-documents', new RequiredDocumentsPolicy());

$app = new Application(__DIR__, static function (string $text): void {
    echo $text;
}, $registry);
exit($app->run(['check']));
```

A policy declares its own requests, for example:

```php
public function inputs(Guard\Execution\Context $context): array
{
    return [
        'documents' => new Guard\Collect\Input(
            new Guard\Collect\Selection('patterns', ['docs/**/*.md']),
            'markdown.headings',
        ),
        'readme' => new Guard\Collect\Input(
            new Guard\Collect\Selection('files', ['README.md']),
        ),
    ];
}
```

`evaluate()` receives those names through `InputSet::get()`. A file set contains `files`, keyed by their selected path spelling, and `directories`, keyed by relative directory paths. Each `StructuredFile` exposes its file metadata, readability and `value()` method. Missing exact files remain visible as metadata so a policy can decide whether absence is a violation. Parsing failures surface when the corresponding value is inspected.

Register an additional representation with `addStructure($id, $structurer)` and request its id from policies. A structurer consumes `Source::text()` and can request another registered representation through `Source::structure($id)`. Dependencies share a per-file cache, including parsing failures; circular dependencies are rejected. Structurers and policies treat shared subjects as read-only. Structure registration alone performs no work.

Register a policy with `addPolicy($id, $policy, $reportOrder = 0)`. Multiple policies can request the same files and structures; collection and structuring are shared without requiring an extension to implement its own scanner. Identical proposed file replacements are combined, while conflicting replacements fail before writes. Implement `Extension::register(Registry): void` to package registrations.

`Context` supplies the project configuration, policy-file path and repair mode. Policy-specific options belong in policy constructors. Paths declared by a policy are relative to the main configuration file's directory, including when its extension declaration is imported. `Pipeline::run(Context): Plan` also runs configured extensions without CLI output or writes. Collection caches are scoped to one invocation, so subsequent checks observe file changes.

### XML Schema extension example

The complete, integration-tested [XML Schema extension](../examples/xml-schema/src/XmlSchemaExtension.php), [policy](../examples/xml-schema/src/XmlSchemaPolicy.php) and [validator](../examples/xml-schema/src/SchemaValidation.php) live outside Guard's production namespace and autoload map. Copy these three files into your project's `tools/guard/` directory and add this entry to your existing Composer configuration, then run `composer dump-autoload`:

```json
{
  "autoload-dev": {
    "psr-4": {"Example\\Guard\\": "tools/guard/"}
  }
}
```

Use the `extensions` configuration above and provide `schemas/catalog.xsd`. Run `vendor/bin/guard check` normally. The example defaults to `**/*.xml` and excludes `vendor/**`; its options can change both. Its policy declares two inputs:

```php
return [
    'documents' => new Input(new Selection('patterns', $this->files, $this->exclude, '', true), 'xml'),
    'schema' => new Input(new Selection('files', [$this->schema], [], '', true), 'xml'),
];
```

Both request the existing `xml` structure. The extension reuses `ParsedDocument`, takes an isolated `copy()` of its `XmlDocument`, and accesses `dom()` to validate the parsed DOM. It does not reread or reparse the target XML. XSD bytes are also collected once, and an existing XML field policy shares the same parsed input. Extensions needing another representation can register a `Structurer` instead of adding a collector.

The example uses PHP's [`DOMDocument::schemaValidateSource()`](https://www.php.net/manual/en/domdocument.schemavalidatesource.php), which supports XSD 1.0. Validation compiles the supplied schema for each DOM validation; PHP's DOM API does not expose a reusable compiled schema. The example accepts self-contained XSD and rejects `xs:include`, `xs:import` and `xs:redefine`, so libxml cannot read undeclared schema dependencies. Supporting multi-file schemas would require declaring those dependencies as inputs and supplying them to a suitable validator.

Schema violations return required findings with the target path, schema path and validation details, producing exit code 1 in text and JSON reports. They also block proposed repairs. Malformed XML, missing inputs and invalid schemas produce exit code 2. The example proposes no repairs; it checks the collected snapshot. `ParsedDocument::copy()` keeps DOM mutations and validation side effects isolated from other policies.

## Refactoring compatibility

The CLI contract remains `guard check|apply|init`, the existing `guard.yaml` schema, text and JSON reports, exit codes, import precedence, and repair behavior. The integration suite compares these against output captured before the pipeline refactor, including every target file's bytes after checks, dry runs, repairs, conflicts and repeated repairs. Some diagnostics retain legacy configuration names to preserve existing messages.
