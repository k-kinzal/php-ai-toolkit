# phpunit-ai-reporter

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Report PHPUnit failures for coding agents and humans with source context and diffs.

## Requirements

- PHP 8.0 or later
- PHPUnit 9.6, 10.5 or later on 10.x, and 11, 12, and 13

## Getting Started

```console
$ composer require --dev k-kinzal/phpunit-ai-reporter
```

Install a compatible PHPUnit release if your project does not already require one.

For PHPUnit 10.5 or later, add the reporter to `phpunit.xml.dist` alongside your
existing extensions:

```xml
<extensions>
    <bootstrap class="Toolkit\PhpUnit\TestReporter\AiTestReporterExtension"/>
</extensions>
```

For PHPUnit 9.6, use its listener API instead:

```xml
<listeners>
    <listener class="Toolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener"/>
</listeners>
```

Run your test suite:

```console
$ vendor/bin/phpunit
```

### Reporter Output

Recognized coding-agent environments select AI output automatically; a non-empty
`AI_AGENT` value selects it explicitly. The report includes failures, unexpected
errors, and risky tests with locations, messages, source lines, and available diffs.
PHPUnit continues to report warnings, skipped tests, and incomplete tests.

On PHPUnit 10.5 or later, AI mode replaces progress and result output. The PHPUnit
9.6 listener adds its report to standard error. Human mode retains PHPUnit's
normal output and adds source context for issues on standard error. The reporter
disables itself when the `PARATEST` environment marker is present.

## License

[MIT](LICENSE)
