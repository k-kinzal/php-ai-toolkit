# phpstan-guard-rules

Strict PHPStan rules for AI-assisted PHP development.

Part of [php-ai-toolkit](https://github.com/k-kinzal/php-ai-toolkit).

```sh
composer require --dev k-kinzal/phpstan-guard-rules:dev-main
```

See the [guide](docs/phpstan.md).

## Rector

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
