---
name: setup-toolkit-doctest
description: >-
  Set up doctest execution of PHPDoc examples for a PHP project. Use when asked
  to configure doctest, doctest.yaml, runnable examples in docblocks, @example
  blocks, executable documentation, testing code samples in comments, Python
  doctest or Rust doc tests for PHP, running one documented example on its own,
  running docblock examples under PHPUnit, requiring examples on public API,
  the RequireExampleOnPublicApiRule PHPStan rule, Composer scripts for doctest,
  or CI jobs that check documentation examples still hold.
---

# Setup Doctest (Executable PHPDoc Examples)

This skill configures `doctest`, the php-ai-toolkit CLI that runs the examples written in PHPDoc blocks. Prose in a docblock says what a symbol is for; an example says what calling it does, and because the example is executed, it cannot quietly stop being true.

## Prerequisites

Inspect `composer.json` before configuring:

- Confirm the target project requires `k-kinzal/php-ai-toolkit`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Check whether the project autoloads everything it ships. A project with non-autoloadable function files needs a `bootstrap`.
- Read the installed PHPUnit major: 10 or later takes `DoctestTestCase`, 9 takes `LegacyDoctestTestCase`.
- Check existing Composer scripts and CI jobs.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-doctest/doctest.yaml` and apply it to the project root as `doctest.yaml`.

| Setting | Default | Meaning |
|---------|---------|---------|
| `paths` | `['src']` | Files and directories scanned for documented examples. |
| `exclude` | `[]` | fnmatch globs of project-relative paths to skip, for generated sources. |
| `bootstrap` | none | A file to include once before the first example runs. |
| `report.reporter` | `ai` | `ai`, `text`, or `json`. |
| `report.order_by` | `['path', 'line']` | Failure ordering: `path`, `line`, `symbol`. |

Set `paths` from the discovered autoload roots. Leave `bootstrap` unset unless the project has code an autoloader cannot resolve: the command loads the Composer autoloader already.

## Adapting to the Project

The command reports nothing until docblocks carry examples, so adoption is the work, not the configuration. Do not add examples in bulk. Take the entry points first:

- The types a consumer names to start using the package. An example there is the one readers look for.
- Methods whose contract is easy to get wrong — an argument order, a unit, a nullable return.
- Methods that throw. `// throws InvalidArgumentException: amount` documents the failure a caller has to handle and proves it still happens.
- Anything whose prose already says "for example". Turn it into a runnable one.

Report the surface found and confirm the first few examples run before writing more. An example that cannot be executed is worse than no example: it is a claim nothing checks.

## Notation

Two notations are recognized:

```php
/**
 * @example Adding two numbers
 *     (new Calculator())->add(1, 2) // => 3
 */
```

````php
/**
 * ```php
 * (new Calculator())->add(1, 2) // => 3
 * ```
 */
````

| Marker | Checks |
|--------|--------|
| `// => value` | The value of the expression is identical (`===`) to the value of `value` |
| `// Output: text` | What the statement printed equals `text`, ignoring one trailing newline |
| `// throws Class` | The statement throws `Class`, or a subclass of it |
| `// throws Class: fragment` | It also throws with a message containing `fragment` |
| none | The line runs without raising anything |

Variables carry from one line of an example to the next. The namespace and imports of the documenting file are replayed in front of the example, so an example spells a class the way the file around it spells it.

A single-line `@example expr` tag, and a fence whose info string is not exactly `php`, are rendered but never run. Use one of those for a snippet that must not execute, such as a class declaration shown for illustration.

## Running One Example

Every example has an identifier — the symbol it documents, then `#` and its position in that docblock:

```bash
vendor/bin/doctest --list
vendor/bin/doctest --filter='App\Calculator::add()#2'
```

The identifier is printed next to every failure, so the report hands back the command that reproduces it.

## Running Under PHPUnit

Add one test class to the project's test suite so every example runs as its own PHPUnit test:

```php
namespace Tests\Unit;

use Override;
use PhpAiToolkit\PhpUnit\Doctest\DoctestTestCase;

final class DocumentedExampleTest extends DoctestTestCase
{
    #[Override]
    public static function doctestConfigPath(): string
    {
        return __DIR__ . '/../../doctest.yaml';
    }
}
```

On PHPUnit 9, extend `PhpAiToolkit\PhpUnit\Doctest\Legacy\LegacyDoctestTestCase` instead: it binds the same provider through a doc-comment annotation, which is how PHPUnit 9 reads metadata.

Choose one place to run them. A project whose CI already reports through PHPUnit gains little from a second job; a project that wants documentation checked separately from its unit tests should use the command.

## Requiring Examples on Public API

`RequireExampleOnPublicApiRule` is registered by the toolkit's PHPStan extension and reports a declaration marked `@visibility public` that has no runnable example. It reports nothing until declarations carry that tag, so it is adopted one boundary at a time alongside `/setup-toolkit-scope-guard`.

Tag a declaration `@visibility public` when the project means "this is the surface other code is invited to use", then give it an example. Do not tag in bulk to make the rule look adopted, and do not remove a tag to silence it.

## Reporter

Keep `report.reporter: ai` by default for this toolkit. The AI reporter prints `DOCTEST_PASSED` or `DOCTEST_FAILED` on the first line, a summary, remediation guidance, and the rerun command for every failure.

## Recommended Composer Scripts

Add a script that matches the project:

```json
{
    "scripts": {
        "doctest": "doctest --config=doctest.yaml"
    }
}
```

Keep it out of `lint`. Doctest executes code, so it belongs with the tests, not with the static gates. Add it to the test step of CI, or to `test` if the project has an aggregate script:

```json
{
    "scripts": {
        "test": [
            "@test:unit",
            "@doctest"
        ]
    }
}
```

Do not add it twice: a project running examples through `DoctestTestCase` already runs them in its PHPUnit job.

## Verification

After applying:

```bash
vendor/bin/doctest --config=doctest.yaml
```

Exit codes:

- `0`: every example held
- `1`: at least one example failed
- `2`: configuration or runtime error

Confirm the summary line reports the expected file count, then write one example, confirm it passes, break it on purpose, and confirm the failure names it.

## Fixing Failures

A failing example means the documentation and the code disagree. Decide which one is wrong before editing either. Fix the code when the example documents what callers were promised; fix the example when the code is right and the documentation went stale.

Deleting an example, or dropping its marker so the line becomes a bare smoke test, is not a fix. It removes the check instead of satisfying it.

## References

- [Doctest Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doctest.md) — Notation, identifiers, execution model, and CLI behavior.
- [RequireExampleOnPublicApiRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/RequireExampleOnPublicApiRule.md) — The rule that requires examples on declared public API.
- [ScopeGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/scope-guard.md) — The `@visibility` tag the rule keys off.
