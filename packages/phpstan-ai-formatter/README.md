# phpstan-ai-formatter

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Format PHPStan diagnostics for coding agents and humans through the `ai` error
formatter.

## Requirements

- PHP 8.0 or later
- PHPStan 1.12 or later on 1.x, and 2.x

## Getting Started

```console
$ composer require --dev k-kinzal/phpstan-ai-formatter
```

Include the extension in `phpstan.neon`:

```neon
includes:
    - vendor/k-kinzal/phpstan-ai-formatter/extension.neon
```

The extension selects `errorFormat: ai`. If `phpstan/extension-installer` already
loads it, omit the explicit include. Run your usual analysis:

```console
$ vendor/bin/phpstan analyse
```

### Output

Recognized coding-agent environments select compact AI output automatically.
Set a non-empty `AI_AGENT` value to select it explicitly:

```console
$ AI_AGENT=1 vendor/bin/phpstan analyse
```

AI output includes project-relative locations, rule identifiers, messages, source
lines, and available tips. When an identifier occurs at least three times,
file-specific findings are grouped by identifier. Human output groups findings
by file and adds terminal source context and colors.

Both modes report the same findings and preserve PHPStan's exit status: `0` for
no errors, including warning-only results, and `1` when errors exist.

## License

[MIT](LICENSE)
