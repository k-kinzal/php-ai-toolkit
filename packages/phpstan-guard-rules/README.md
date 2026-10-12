# phpstan-guard-rules

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Strict PHPStan rules for AI-assisted PHP development, covering production code,
public API contracts, and test design.

## Requirements

- PHP 8.0 or later
- PHPStan 1.12 or later on 1.x, and 2.x

## Getting Started

```console
$ composer require --dev k-kinzal/phpstan-guard-rules
```

Include the shipped rules and PHPStan's strict rules in `phpstan.neon`:

```neon
includes:
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/k-kinzal/phpstan-guard-rules/rules.neon

parameters:
    level: max
    paths:
        - src
        - tests
```

If `phpstan/extension-installer` already loads these files, omit the explicit
includes. Adjust the analysis paths to your project, then run the analysis:

```console
$ vendor/bin/phpstan analyse
```

See the [rule reference](docs/phpstan.md) for individual rules and configuration.

### Rector

Optional Rector rules can fix some of the findings. They require Rector 2.6.7 or
later on 2.x and PHPStan 2. Install Rector, then enable the safe group in `rector.php`:

```console
$ composer require --dev rector/rector:^2.6.7
```

```php
<?php

use Rector\Config\RectorConfig;
use Toolkit\Rector\SafeRules;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withRules(SafeRules::rules());
```

Preview the proposed changes:

```console
$ vendor/bin/rector process --dry-run
```

`SafeRules::rules()` provides the two safe rules; `RiskyRules::rules()` provides
the eight risky rules.
Review their behavioral or contract changes before adding the risky group.
The [rule reference](docs/phpstan.md#rector-support) records automatic-fix
availability and review requirements for every rule.

## License

[MIT](LICENSE)
