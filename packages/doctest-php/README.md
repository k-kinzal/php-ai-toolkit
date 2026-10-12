# doctest-php

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Run PHPDoc examples as PHPUnit tests and report examples that no longer match
the code they document.

## Requirements

- PHP 8.0 or later
- PHPUnit 9.6, 10.5 or later on 10.x, and 11, 12, and 13

## Getting Started

```console
$ composer require --dev k-kinzal/doctest-php
```

Install a compatible PHPUnit release if your project does not already require one.
For PHPUnit 10.5 or later, add the suite and extension to your `phpunit.xml.dist`:

```xml
<testsuites>
    <testsuite name="doctest">
        <file>vendor/k-kinzal/doctest-php/src/Doctest/DoctestSuite.php</file>
    </testsuite>
</testsuites>
<extensions>
    <bootstrap class="Toolkit\Doctest\DoctestExtension">
        <parameter name="directories" value="src"/>
    </bootstrap>
</extensions>
```

Merge these entries with your existing suites and extensions. Keep
`bootstrap="vendor/autoload.php"` on the `phpunit` element so examples can load
your project's classes.

Add a runnable `@example` to a PHPDoc block in `src/`:

```php
/**
 * @example Adding two values
 *     \App\Calculator::add(2, 3) // => 5
 */
```

The example calls the documented code, so `App\Calculator::add()` must exist
and be autoloadable. Run the examples with PHPUnit:

```console
$ vendor/bin/phpunit --testsuite doctest
```

Each example becomes a test. A failed assertion reports the source file, example,
and line so you can update the code or correct the example.

### Example notation

Doctest reads multiline `@example` blocks and PHP fenced blocks inside PHPDoc.
Use fully qualified class names; examples do not inherit the source file's imports.

| Notation | Check |
| --- | --- |
| `expression // => value` | The expression returns the expected value. |
| `echo 'hello'; // Output: hello` | The statement writes the expected output. |
| `throw new \RuntimeException(); // throws RuntimeException` | The statement throws the expected exception. |
| `throw new \RuntimeException('failed'); // throws RuntimeException: failed` | The exception also has the expected message. |

Lines without an assertion still run and fail if they raise an unexpected exception.
Variables remain available to later lines in the same example. A single-line
`@example` is display-only and does not create a test.

### Configuration

The extension accepts `directories`, `files`, `exclude`, `bootstrap`, and `enabled`
parameters. `directories` and `files` are comma-separated paths relative to the
PHPUnit configuration file. `exclude` accepts comma-separated glob patterns.
`bootstrap` loads an additional file before running examples, and `enabled=false`
disables the suite. Select the directories or files to scan; neither is set by default.

### PHPUnit 9.6

Use the legacy suite and environment settings in `phpunit.xml.dist`:

```xml
<testsuites>
    <testsuite name="doctest">
        <file>vendor/k-kinzal/doctest-php/src/Doctest/Legacy/LegacyDoctestSuite.php</file>
    </testsuite>
</testsuites>
<php>
    <env name="DOCTEST_DIRECTORIES" value="src"/>
</php>
```

The same options are available as `DOCTEST_DIRECTORIES`, `DOCTEST_FILES`,
`DOCTEST_EXCLUDE`, `DOCTEST_BOOTSTRAP`, and `DOCTEST_ENABLED`. Run the same
`vendor/bin/phpunit --testsuite doctest` command.

## License

[MIT](LICENSE)
