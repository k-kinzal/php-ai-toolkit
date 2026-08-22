---
name: setup-toolkit-doctest
description: >-
  Set up doctest execution of PHPDoc examples for a PHP project. Use when asked
  to configure doctest, doctest-php, runnable examples in docblocks, @example
  blocks, executable documentation, testing code samples in comments, Python
  doctest or Rust doc tests for PHP, running docblock examples under PHPUnit,
  running one documented example on its own, requiring examples on public API,
  the RequireExampleOnPublicApiRule PHPStan rule, a doctest PHPUnit test suite,
  or CI that checks documentation examples still hold.
---

# Setup Doctest (Executable PHPDoc Examples)

This skill configures doctest, the toolkit's port of [k-kinzal/doctest-php](https://github.com/k-kinzal/doctest-php). It runs the examples written in PHPDoc blocks as PHPUnit test cases. Prose in a docblock says what a symbol is for; an example says what calling it does, and because the example is executed, it cannot quietly stop being true.

It is a PHPUnit extension plus a test suite. The project already has a runner, a reporter, and a CI job that reports through it, so a documented example that disagrees with the code is reported as a failing test.

## Prerequisites

Inspect the project before configuring:

- Confirm it requires `k-kinzal/php-ai-toolkit`.
- Read the installed PHPUnit major. 10 or later takes the extension; 9 has no extension API and needs the legacy runner.
- Read `phpunit.xml` (or `phpunit.xml.dist`) and its existing `<testsuites>` and `<extensions>`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Check whether the project autoloads everything it ships. A project with non-autoloadable function files needs a bootstrap.
- Check for a mutation testing config or CI step passing `--no-extensions`, and whether it should still run the examples.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Apply

Register the extension and add the suite. No test file is written:

```xml
<testsuites>
    <testsuite name="unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="doctest">
        <file>vendor/k-kinzal/php-ai-toolkit/src/Doctest/DoctestSuite.php</file>
    </testsuite>
</testsuites>

<extensions>
    <bootstrap class="PhpAiToolkit\Doctest\DoctestExtension">
        <parameter name="directories" value="src"/>
    </bootstrap>
</extensions>
```

| Parameter | Meaning |
|-----------|---------|
| `directories` | Comma-separated directories to scan |
| `files` | Comma-separated individual files to scan |
| `exclude` | Comma-separated fnmatch patterns to leave unscanned |
| `bootstrap` | A file to include once before the first example runs |
| `enabled` | `false` switches doctest off without removing the configuration |

Set `directories` from the discovered autoload roots. Leave `bootstrap` unset unless the project has code an autoloader cannot resolve.

On PHPUnit 9, extend `PhpAiToolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner` and return a `Configuration` from `configure()` instead; there is no extension to read parameters from.

### Runs that disable extensions

`--no-extensions` bootstraps nothing, and PHPUnit 10.5 builds the test suite before it bootstraps anything. The suite covers both by reading the parameters the `<bootstrap>` element declares when the extension has not handed it a configuration, so the examples run either way — nothing to configure.

Leaving them out of such a run is `enabled="false"`, or selecting the other suites:

```json5
"testFrameworkExtraArgs": "--no-extensions --testsuite unit",
```

## Adapting to the Project

The suite reports nothing until docblocks carry examples, so adoption is the work, not the configuration. Do not add examples in bulk. Take the entry points first:

- The types a consumer names to start using the package. An example there is the one readers look for.
- Methods whose contract is easy to get wrong — an argument order, a unit, a nullable return.
- Methods that throw. `// throws InvalidArgumentException: amount` documents the failure a caller has to handle and proves it still happens.
- Anything whose prose already says "for example". Turn it into a runnable one.

Report the surface found and confirm the first few examples run before writing more. An example that cannot be executed is worse than no example: it is a claim nothing checks.

## Notation

```php
/**
 * @example Adding two numbers
 *     (new \App\Calculator())->add(1, 2) // => 3
 */
```

````php
/**
 * ```php
 * (new \App\Calculator())->add(1, 2) // => 3
 * ```
 */
````

| Marker | Checks |
|--------|--------|
| `// => value` | The value of the expression is identical (`===`) to the value of `value` |
| `// Output: text` | What the statement printed equals `text` |
| `// throws Class` | The statement throws `Class`, or a subclass of it |
| `// throws Class: fragment` | It also throws with a message containing `fragment` |
| none | The line runs without raising anything |

Two rules decide whether an example a reader would write actually runs. State both when writing examples for a project:

1. **Names must be fully qualified.** Evaluated code inherits no import table, so `new \App\Calculator()`, never `new Calculator()`.
2. **A continued line must end with an operator or an opening bracket.** A multi-line call closes on the asserted line — `5) // => 15`, not a `)` on a line of its own.

A tag with no code under it, and a fence whose info string is not exactly `php`, are never run. Use one of those for a snippet that must not execute.

## Running One Example

The test case is named after the example, so PHPUnit's filter selects it. Quote the name, because a filter is a regular expression:

```bash
vendor/bin/phpunit --testsuite doctest
vendor/bin/phpunit --filter '/Calculator\:\:add\(\) example \#1\: Adding two numbers/'
```

The generated documentation site prints that command for every example.

## Requiring Examples on Public API

`RequireExampleOnPublicApiRule` is registered by the toolkit's PHPStan extension and reports a declaration marked `@visibility public` that has no runnable example. It reports nothing until declarations carry that tag, so it is adopted one boundary at a time alongside `/setup-toolkit-scope-guard`.

Tag a declaration `@visibility public` when the project means "this is the surface other code is invited to use", then give it an example. Do not tag in bulk to make the rule look adopted, and do not remove a tag to silence it.

## Recommended Composer Scripts

The examples already run with the rest of the suite. Add a script only for running them alone:

```json
{
    "scripts": {
        "doctest": "phpunit --testsuite doctest"
    }
}
```

Do not add a separate CI job and do not chain `@doctest` into `test`: the suite runs the examples wherever it runs, on every PHP version the matrix covers.

## Verification

After applying:

```bash
vendor/bin/phpunit --testsuite doctest
```

Confirm the run reports the expected number of test cases, then write one example, confirm it passes, break it on purpose, and confirm the failure names the example.

If the suite reports no tests, the configuration reached it empty: check the `<bootstrap>` class name, the `directories` parameter, and that `enabled` is not `false`.

## Fixing Failures

A failing example means the documentation and the code disagree. Decide which one is wrong before editing either. Fix the code when the example documents what callers were promised; fix the example when the code is right and the documentation went stale.

Deleting an example, or dropping its marker so the line becomes a bare smoke test, is not a fix. It removes the check instead of satisfying it.

## References

- [Doctest Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doctest.md) — Notation, configuration, execution model, and how the port differs from upstream.
- [RequireExampleOnPublicApiRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/RequireExampleOnPublicApiRule.md) — The rule that requires examples on declared public API.
- [ScopeGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/scope-guard.md) — The `@visibility` tag the rule keys off.
