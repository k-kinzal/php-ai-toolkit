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
  suite only when the actual graph resolves the legacy PHPUnit line.
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

Only when the resolved test graph actually runs PHPUnit 9 does the configuration
differ. That version reads test metadata from doc-comments, so `DoctestSuite.php`
cannot run there, and it instantiates its hook extensions only after the test
suite and its data providers are built, so no extension can hand a suite its
parameters. Point the PHPUnit 9 configuration at the toolkit's installed legacy
suite and export the same parameters through the `<php>` element, each named
after the parameter in upper case behind `DOCTEST_`:

```xml
<testsuites>
    <testsuite name="doctest">
        <file>vendor/k-kinzal/php-ai-toolkit/src/Doctest/Legacy/LegacyDoctestSuite.php</file>
    </testsuite>
</testsuites>

<php>
    <env name="DOCTEST_DIRECTORIES" value="REPLACE_WITH_PRODUCTION_ROOTS"/>
</php>
```

This is configuration only as well: do not copy a `LegacyDoctestSuiteTest.php`
into the project and do not subclass `LegacyDoctestRunner` for an ordinary setup.
A relative path in a variable resolves against the working directory PHPUnit is
started from, so run PHPUnit 9 from the directory holding its configuration; the
toolkit's `tests/run.php` and the Composer scripts already do. Do not merely add
the suite: a PHPUnit 9 configuration that names it without `DOCTEST_DIRECTORIES`
discovers zero doctests and is not a completed setup. Keep the legacy suite out of
the modern configuration, because PHPUnit 12 or later no longer reads the
doc-comment metadata it relies on; the modern suite uses `DoctestSuite.php` and
the extension parameters above.

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

Each example must establish its own state. For random APIs, construct providers
and other collaborators before seeding if their bootstrap can consume randomness;
check examples both in isolation and in the full suite. Assert promised output
properties rather than incidental seeded spellings across dependency versions.

Keep setup focused on PHPDoc and its runner. Do not add a developer guide or
rewrite the README as a side effect. When product documentation is explicitly in
scope, describe the consumer API and preserve unrelated sections. Markdown examples
are not covered by the PHPDoc suite: execute them separately before claiming that
all documentation examples pass.

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

If either suite reports no tests, do not create a local suite class. Check the
installed path reported by `composer config vendor-dir`, the `<bootstrap>` class
name and its `directories` parameter or, on PHPUnit 9, the `DOCTEST_DIRECTORIES`
variable, and that `enabled` is not `false`.

## Fixing Failures

A failing example means the documentation and the code disagree. Decide which one is wrong before editing either. Fix the code when the example documents what callers were promised; fix the example when the code is right and the documentation went stale.

Deleting an example, or dropping its marker so the line becomes a bare smoke test, is not a fix. It removes the check instead of satisfying it.

## References

- [Doctest Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doctest.md) — Notation, configuration, execution model, and how the port differs from upstream.
- [RequireExampleOnPublicApiRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/RequireExampleOnPublicApiRule.md) — The rule that requires examples on declared public API.
- [EnforceVisibilityScopeRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/EnforceVisibilityScopeRule.md) — The `@visibility` tag the rule keys off.
