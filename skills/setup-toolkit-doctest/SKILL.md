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
- Read the installed PHPUnit version and available extension API. Use the modern
  extension path when the resolved target version supports it; use the legacy
  runner only when the actual graph resolves the legacy PHPUnit line.
- Read `phpunit.xml` (or `phpunit.xml.dist`) and its existing `<testsuites>` and `<extensions>`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Check whether the project autoloads everything it ships. A project with non-autoloadable function files needs a bootstrap.
- Check for a mutation testing config or CI step passing `--no-extensions`, and whether it should still run the examples.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

The unversioned requirement is intentional for a new install: Composer should
select the newest stable toolkit release compatible with the target project. Check
current package metadata and the target's PHP/PHPUnit graph first. If the toolkit is
already constrained, update its lock to the newest admitted release and preserve an
intentional pin unless changing that policy is in scope. Never copy this repository's
root constraint or lock resolution.

## Apply

For PHPUnit 10 or later, register the extension and add the suite. This is a
configuration-only integration: no test file is written.

```xml
<testsuites>
    <testsuite name="unit">
        <directory>REPLACE_WITH_UNIT_TEST_PATH</directory>
    </testsuite>
    <testsuite name="doctest">
        <file>vendor/k-kinzal/php-ai-toolkit/src/Doctest/DoctestSuite.php</file>
    </testsuite>
</testsuites>

<extensions>
    <bootstrap class="Toolkit\Doctest\DoctestExtension">
        <parameter name="directories" value="REPLACE_WITH_PRODUCTION_ROOTS"/>
    </bootstrap>
</extensions>
```

The `<file>` entry is the toolkit's installed, concrete suite, not a template.
Do not create or copy a project-local `DoctestSuite.php`, and do not subclass
`DoctestRunner` for an ordinary setup. The example uses Composer's default
`vendor-dir`; read the target project's value with `composer config vendor-dir`
and replace the leading `vendor` when it differs. If the file is not found, fix
that installed-package path instead of scaffolding a local suite.

| Parameter | Meaning |
|-----------|---------|
| `directories` | Comma-separated directories to scan |
| `files` | Comma-separated individual files to scan |
| `exclude` | Comma-separated fnmatch patterns to leave unscanned |
| `bootstrap` | A file to include once before the first example runs |
| `enabled` | `false` switches doctest off without removing the configuration |

Set `directories` from the discovered autoload roots. Leave `bootstrap` unset unless the project has code an autoloader cannot resolve.

Only when the resolved test graph actually runs PHPUnit 9 is a local compatibility
class required: that version has no extension API to read those parameters. Copy
`LegacyDoctestSuiteTest.php` from this skill to
`tests/Doctest/LegacyDoctestSuiteTest.php`, adapt its namespace and production
autoload roots, and register that directory as the doctest suite in the PHPUnit 9
configuration:

```xml
<testsuite name="doctest">
    <directory>tests/Doctest</directory>
</testsuite>
```

Replace both `REPLACE_WITH_TEST_NAMESPACE` and
`REPLACE_WITH_PRODUCTION_ROOT` in the copied class. They are deliberate sentinels;
do not infer either from this repository's `Tests` namespace or `src` layout.

The template extends
`Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner` and returns the exact
`Configuration` from `configure()`. Do not merely add the suite name: a PHPUnit 9
matrix leg with no concrete legacy runner discovers zero doctests and is not a
completed setup. Keep this directory out of the modern configuration because its
PHPDoc data-provider metadata is deliberately for PHPUnit 9; the modern suite uses
`DoctestSuite.php` and the extension parameters above.

### Runs that disable extensions

`--no-extensions` bootstraps nothing, and modern PHPUnit can build the test suite
before extensions are bootstrapped. The suite covers both cases by reading the
parameters the `<bootstrap>` element declares when the extension has not handed it
a configuration, so the examples run either way — nothing to configure.

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

`RequireExampleOnPublicApiRule` is registered by the toolkit's PHPStan extension and reports a declaration marked `@visibility public` that has no runnable example. It reports nothing until declarations carry that tag, so it is adopted one boundary at a time alongside the toolkit's visibility-scope PHPStan rule.

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
vendor/bin/phpunit --configuration phpunit9.xml.dist --testsuite doctest
```

Run the second command when PHPUnit 9 is in the support matrix. Confirm both runs
report the same expected examples (apart from explicitly documented version-only
sources), then write one example, confirm it passes, break it on purpose, and
confirm the failure names the example.

If the modern suite reports no tests, do not create a local suite class. Check the
installed path reported by `composer config vendor-dir`, the `<bootstrap>` class
name, the `directories` parameter, and that `enabled` is not `false`.

## Fixing Failures

A failing example means the documentation and the code disagree. Decide which one is wrong before editing either. Fix the code when the example documents what callers were promised; fix the example when the code is right and the documentation went stale.

Deleting an example, or dropping its marker so the line becomes a bare smoke test, is not a fix. It removes the check instead of satisfying it.

## References

- [Doctest Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doctest.md) — Notation, configuration, execution model, and how the port differs from upstream.
- [RequireExampleOnPublicApiRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/RequireExampleOnPublicApiRule.md) — The rule that requires examples on declared public API.
- [EnforceVisibilityScopeRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/EnforceVisibilityScopeRule.md) — The `@visibility` tag the rule keys off.
