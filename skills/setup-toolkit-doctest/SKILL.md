---
name: setup-toolkit-doctest
description: >-
  Set up doctest execution of PHPDoc examples for a PHP project. Use when asked
  to configure doctest, runnable examples in docblocks, @example blocks,
  executable documentation, testing code samples in comments, Python doctest or
  Rust doc tests for PHP, running docblock examples under PHPUnit, running one
  documented example on its own, requiring examples on public API, the
  RequireExampleOnPublicApiRule PHPStan rule, a doctest PHPUnit test suite, or
  CI that checks documentation examples still hold.
---

# Setup Doctest (Executable PHPDoc Examples)

This skill configures doctest, the php-ai-toolkit PHPUnit test case that runs the examples written in PHPDoc blocks. Prose in a docblock says what a symbol is for; an example says what calling it does, and because the example is executed, it cannot quietly stop being true.

Doctest is a PHPUnit test case, not a command of its own. The project already has a runner, a reporter, and a CI job that reports through it; a documented example that disagrees with the code is a failing test, so it is reported as one.

## Prerequisites

Inspect the project before configuring:

- Confirm it requires `k-kinzal/php-ai-toolkit`.
- Read the installed PHPUnit major: 10 or later takes `DoctestTestCase`, 9 takes `LegacyDoctestTestCase`.
- Read `phpunit.xml` (or `phpunit.xml.dist`) and the existing `<testsuites>`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Check whether the project autoloads everything it ships. A project with non-autoloadable function files needs a bootstrap.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Apply

Add one test class. Put it in its own directory rather than beside the unit tests, so it can be selected as a suite and so a project that pairs `src/` classes with `tests/Unit/` classes is not asked to pair this one:

```php
<?php

declare(strict_types=1);

namespace Tests\Doctest;

use PhpAiToolkit\PhpUnit\Doctest\DoctestTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Medium;

/**
 * Runs the examples this package documents its public API with.
 */
#[CoversNothing]
#[Medium]
final class DocumentedExampleTest extends DoctestTestCase
{
}
```

Then register the suite:

```xml
<testsuites>
    <testsuite name="unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="doctest">
        <directory>tests/Doctest</directory>
    </testsuite>
</testsuites>
```

The defaults scan `src` under the directory PHPUnit runs from, so an empty subclass is usually the whole setup. Override only what the project needs:

| Method | Default | Meaning |
|--------|---------|---------|
| `doctestRoot()` | `getcwd()` | The directory the scanned paths and the bootstrap are relative to. |
| `doctestPaths()` | `['src']` | Files and directories scanned for documented examples. |
| `doctestExcludes()` | `[]` | fnmatch globs of root-relative paths to skip. |
| `doctestBootstrap()` | `null` | A file to include once before the first example runs. |

Set `doctestPaths()` from the discovered autoload roots when they are not `src`. Leave `doctestBootstrap()` alone unless the project has code an autoloader cannot resolve.

On PHPUnit 9, extend `PhpAiToolkit\PhpUnit\Doctest\Legacy\LegacyDoctestTestCase` instead. A project that supports both majors carries one subclass of each and excludes the PHPUnit 9 one from the modern configuration.

## Adapting to the Project

The suite reports nothing until docblocks carry examples, so adoption is the work, not the configuration. Do not add examples in bulk. Take the entry points first:

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

Every example has an identifier — the symbol it documents, then `#` and its position in that docblock. PHPUnit filters are regular expressions and an identifier is not one, so it is quoted:

```bash
vendor/bin/phpunit --testsuite doctest
vendor/bin/phpunit --filter '/App\\Calculator\:\:add\(\)\#2/'
```

Every failure prints that command, and so does the generated documentation site, so it never has to be built by hand.

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

Do not add a separate CI job and do not add `@doctest` to `test`: the suite runs the examples wherever it runs, on every PHP version the matrix covers, and running them twice only doubles the time.

## Verification

After applying:

```bash
vendor/bin/phpunit --testsuite doctest
```

Confirm the run reports the expected number of test cases, then write one example, confirm it passes, break it on purpose, and confirm the failure names the example and prints the command that re-runs it.

## Fixing Failures

A failing example means the documentation and the code disagree. Decide which one is wrong before editing either. Fix the code when the example documents what callers were promised; fix the example when the code is right and the documentation went stale.

Deleting an example, or dropping its marker so the line becomes a bare smoke test, is not a fix. It removes the check instead of satisfying it.

## References

- [Doctest Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doctest.md) — Notation, identifiers, execution model, and configuration.
- [RequireExampleOnPublicApiRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/RequireExampleOnPublicApiRule.md) — The rule that requires examples on declared public API.
- [ScopeGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/scope-guard.md) — The `@visibility` tag the rule keys off.
