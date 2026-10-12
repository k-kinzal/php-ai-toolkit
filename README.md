# php-ai-toolkit

[![docs](https://img.shields.io/badge/docs-php--ai--toolkit-0969da?logo=php&logoColor=white)](https://k-kinzal.github.io/php-ai-toolkit/)
[![CI](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/ci.yml)
[![Docs](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/docs.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/docs.yml)
[![Context7](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/context7.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/context7.yml)
[![PHP](https://img.shields.io/badge/php-8.0%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/k-kinzal/php-ai-toolkit)

Tools for AI-assisted PHP development: project policy checks, PHPStan rules,
readable analysis and test reports, executable PHPDoc examples, and API documentation.

## Packages

Each package has its own Composer manifest, configuration, and tests. Follow its
README for requirements, installation, usage, and available agent skills.

| Package | Purpose |
| --- | --- |
| [guard-php](packages/guard-php/README.md) | Check and repair source, structure, documentation, and configuration policies. |
| [docgen-php](packages/docgen-php/README.md) | Generate PHP API documentation and coverage sites. |
| [phpstan-guard-rules](packages/phpstan-guard-rules/README.md) | Detect PHP and test patterns that need stricter analysis. |
| [doctest-php](packages/doctest-php/README.md) | Run PHPDoc examples as PHPUnit tests. |
| [phpunit-ai-reporter](packages/phpunit-ai-reporter/README.md) | Report test failures for agents and humans. |
| [phpstan-ai-formatter](packages/phpstan-ai-formatter/README.md) | Format PHPStan diagnostics for agents and humans. |

## License

[MIT](LICENSE)
