# Extensions

Extensions add policies of your own, and the structures they read, to the same run as the built-in ones. A **policy** declares which files it needs and evaluates them into findings and file changes. A **structure** turns the bytes of a file into a value that policies share, such as parsed XML. The built-in metric, structure, document and field policies are implemented the same way.

## Policies

```php
namespace Guard\Policy;

use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Policy\Context;
use Guard\Policy\Plan;

interface Policy
{
    /** @return array<string, Input> */
    public function inputs(Context $context): array;

    public function evaluate(InputSet $inputs, Context $context): Plan;
}
```

`inputs()` names each set of files the policy needs. `evaluate()` receives them under the same names from `InputSet::get()`, already read and structured, and must not touch the file system itself.

This policy requires `declare(strict_types=1);` in every PHP file it is given:

```php
namespace App\Guard;

use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Policy\Context;
use Guard\Policy\Plan;
use Guard\Policy\Policy;
use Guard\Diagnostic\Finding;
use Guard\Structure\Text;

final class StrictTypesPolicy implements Policy
{
    /** @param list<string> $files */
    public function __construct(private array $files)
    {
    }

    public function inputs(Context $context): array
    {
        return ['sources' => new Input(new Selection('patterns', $this->files), 'text')];
    }

    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $findings = [];
        foreach ($inputs->get('sources')->files as $path => $file) {
            $text = $file->value();
            if ($text instanceof Text && !str_contains($text->content(), 'declare(strict_types=1);')) {
                $findings[] = new Finding($path, 'app.strict-types', 'required', 'Add declare(strict_types=1); after the opening <?php tag.');
            }
        }

        return new Plan($findings, []);
    }
}
```

### Inputs

`new Input($selection, $structure)` pairs a selection of files with the ID of the structure to build for each of them. Without a structure ID, the policy receives file metadata only, and the file is not read.

| Selection | Selects |
|-----------|---------|
| `new Selection('files', ['README.md'])` | Exact paths. A missing file is still listed, so the policy can report it. |
| `new Selection('patterns', ['docs/**/*.md'])` | Files matching globs. `*` stays within one segment and `**` matches any number. |
| `new Selection('descendants', ['src'], [], '.php')` | Files below directories, optionally limited to a name suffix. |
| `new Selection('directories', ['.'])` | Directory listings with their file and subdirectory names. |

The third argument lists exclusions. Paths are relative to the main `guard.yaml`. Every selection is intersected with the [collection scope](configuration.md#collection-scope), so a policy cannot read outside it, and a file outside it is simply absent from the result.

`InputSet::get($name)` returns a `FileSet`. Its `files` are keyed by path; each `StructuredFile` has `file` (with `path`, `relativePath` and `entry->file`), `readable`, and `value()`, which returns the structure or throws the error from parsing it. Its `directories` are `DirectoryListing` values with `relativePath`, `fileNames` and `dirNames`.

### Plans

`new Plan($findings, $changes, $blocking)` is the result of `evaluate()`:

| Argument | Description |
|----------|-------------|
| `$findings` | `Finding` values, each with a path, a rule ID, `required` or `recommended`, and a message. |
| `$changes` | `FileChange` values with the absolute path, the content that was read and the replacement. Propose them only when `$context->repair` is `true`. |
| `$blocking` | Findings that stop every write of the run, typically the required findings this policy could not repair. |

Guard writes the changes only during `guard fix`, and only when no policy returned a required blocking finding. Identical changes to one file are merged; different changes to one file fail the run before anything is written.

Write messages the way the built-in ones are written: name the file and the offending symbol, say what is expected, and say how to fix it.

## Structures

| ID | Value |
|----|-------|
| `text` | `Guard\Structure\Text`: the bytes of the file, from `content()`. |
| `php.tokens`, `php.metrics` | PHP tokens, and the line and complexity metrics built from them. |
| `markdown.headings`, `markdown.badges` | The headings of a Markdown file, and the badge block under its title. |
| `json`, `json5`, `yaml`, `yml`, `neon`, `toml`, `xml`, `php` | `Guard\Structure\ParsedDocument`. `copy()` returns a private editable document; `original()` returns the bytes. |

Each file is read once and each structure is built once per file, however many policies request it. To add a representation, implement `Guard\Structure\Structurer`:

```php
namespace Guard\Structure;

interface Structurer
{
    public function structure(Source $source): Subject;
}
```

`Source::text()` returns the bytes, and `Source::structure($id)` builds another registered structure to depend on, such as `markdown.headings`. A circular dependency is an error. Treat shared values as read-only; use `copy()` before changing a parsed document.

## Registering policies and structures

Implement `Guard\Policy\Policy` or `Guard\Structure\Structurer` directly. These are the interfaces used by built-in features; there is no separate extension interface or extension lifecycle.

Make the class autoloadable with Composer, for example through `autoload-dev`, run `composer dump-autoload`, and name it in `guard.yaml`:

```yaml
version: 1
extensions:
  App\Guard\StrictTypesPolicy:
    files: ['src/**/*.php']
  App\Guard\CustomStructurer: {}
```

Each option is a named constructor argument. The `files` option above calls `new StrictTypesPolicy(files: ['src/**/*.php'])`. Omitted arguments use their constructor defaults. A class without arguments uses `{}`. Validate the meaning of arguments in the constructor and throw `Guard\Diagnostic\PolicyException` with a concrete correction when a value is invalid. Unknown arguments, missing required arguments, and invalid argument types stop the run with exit code `2`.

The class name is its registration ID. A policy requesting the custom structure uses `new Input($selection, CustomStructurer::class)`. Finding rule IDs are independent of registration IDs; prefix them with your project or package name, such as `app.strict-types`.

Guard loads only configured classes, in declaration order, after the built-in registrations. All registrations complete before collection or evaluation, so a policy can request a structure declared later in the mapping. Components must be concrete classes with public constructors and implement one of the functional interfaces. Their code runs as trusted project code.

## Running Guard from PHP

`Guard\Cli\Application` runs the same commands in-process. Pass a registry to supply registrations directly. `Registry::defaults($configuration->policies)` composes the standard structures and configured policies using the same `addPolicy()` and `addStructure()` methods:

```php
use Guard\Cli\Application;
use Guard\Config\ConfigurationLoader;
use Guard\Execution\Registry;

$configuration = (new ConfigurationLoader())->load(__DIR__ . '/guard.yaml');
$registry = Registry::defaults($configuration->policies);
$registry->addPolicy('app.strict-types', new StrictTypesPolicy(['src/**/*.php']));

$application = new Application(__DIR__, static function (string $text): void {
    echo $text;
}, $registry);
exit($application->run(['check']));
```

A supplied registry replaces the defaults. Use `new Registry()` when every registration is supplied by the caller. `addPolicy($id, $policy, $reportOrder = 0)` and `addStructure($id, $structurer)` reject empty or duplicate IDs. Programmatic registrations may choose their own IDs.

Configured components are added to a copy of the registry on every run, so repeated runs do not register them twice. `Guard\Execution\Pipeline::run(Configuration $configuration, string $configPath, bool $repair = false)` returns the combined `Guard\Policy\Plan` without printing or writing. The pipeline gives each policy a `Guard\Policy\Context` containing the project root, configuration path, repair mode, and collection scope; policies do not receive the configuration loader or the other policies.

## Migrating registrations

Replace an old `Extension::register()` wrapper with direct registration of its policy and structurer classes. Replace `ConfigurableExtension::fromOptions()` with named constructor arguments and constructor validation. In YAML, replace the wrapper class name with each functional class name. Use `Registry::defaults($configuration->policies)` in place of `BuiltinExtension` when composing a registry from PHP.

Input declarations and collected results are now under `Guard\Input`. The policy API uses `Guard\Policy\Context` and `Guard\Policy\Plan`; proposed edits use `Guard\Policy\FileChange`, and findings and errors use `Guard\Diagnostic\Finding` and `Guard\Diagnostic\PolicyException`. Document readers are under `Guard\Structure\Document`. See [the architecture](architecture.md) for directory ownership.
