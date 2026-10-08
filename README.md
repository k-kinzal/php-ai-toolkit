# php-ai-toolkit

[![docs](https://img.shields.io/badge/docs-php--ai--toolkit-0969da?logo=php&logoColor=white)](https://k-kinzal.github.io/php-ai-toolkit/)
[![CI](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/ci.yml)
[![Docs](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/docs.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/docs.yml)
[![Context7](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/context7.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/context7.yml)
[![PHP](https://img.shields.io/badge/php-8.0%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/k-kinzal/php-ai-toolkit)

A PHPStan extension that detects anti-patterns commonly introduced by AI code generation, plus output formatters optimized for both AI agents and humans.

## Requirements

- PHP ^8.0
- PHPStan ^1.12 || ^2.0
- PHPUnit ^9.6 || ^10.5 || ^11 || ^12 || ^13

The PHPUnit test reporter supports PHPUnit 9.6 and 10.5 or later through
version-specific adapters. PHPUnit 10+ uses the event extension API, while
PHPUnit 9.6 uses the legacy listener API.

## Quick Start

### 1. Install

The repository contains five Composer packages. Each one carries its own analysis, test, and formatting configuration, and is installed on its own with `composer install` inside that package.

| Directory | Composer package | Responsibility |
| --- | --- | --- |
| `packages/guard-php` | `k-kinzal/guard-php` | `guard check`, `guard fix`, `guard init` |
| `packages/docgen-php` | `k-kinzal/docgen-php` | `docgen` and site assets |
| `packages/phpstan-guard-rules` | `k-kinzal/phpstan-guard-rules` | PHPStan rules and strict configuration |
| `packages/phpunit-ai-reporter` | `k-kinzal/phpunit-ai-reporter` | PHPUnit reporting, Doctest and shared output detection |
| `packages/phpstan-ai-formatter` | `k-kinzal/phpstan-ai-formatter` | PHPStan's `ai` formatter |

Package releases are not published to Packagist yet. To use a component from a
local checkout, add a path repository to the consuming project's `composer.json`:

```json
{
    "repositories": [{"type": "path", "url": "../php-ai-toolkit/packages/guard-php"}],
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require-dev": {"k-kinzal/guard-php": "dev-main"}
}
```

Run `composer update`, then `vendor/bin/guard init`. Composer only reads
repository declarations from the consuming project's root manifest.

### 2. Apply the toolkit

Generate and verify the project policy. A project is configured correctly when `guard check` passes the policy `guard init` wrote:

```sh
vendor/bin/guard init
vendor/bin/guard check
vendor/bin/guard fix --dry-run
vendor/bin/guard fix
```

`guard.yaml` configures source metrics, directory rules, Markdown structure and
JSON/YAML/XML/TOML/NEON field constraints. Required violations fail; recommendations
warn. Existing guard limits are retained when importing older policies. See the
[Guard documentation](packages/guard-php/docs/policies.md) for repair behavior and format limitations.

Run the end-to-end adoption skill:

- `/setup-php-ai-toolkit` — applies the complete opinionated baseline, repairs
  design and tests instead of weakening configuration, preserves public API and
  product-owned docs/AGENTS.md, and asks how DocGen should publish

The component skills are also available for focused setup or maintenance:

- `/setup-toolkit-agents-md` — AGENTS.md with project conventions and AI agent guidelines
- `/setup-toolkit-doc-guard` — Guard documentation policies for fixed section structure for README.md, AGENTS.md, and docs/
- `/setup-toolkit-deptrac` — Deptrac architecture dependency rules for web apps, CLI apps, libraries, and modular projects
- `/setup-toolkit-doctest` — Doctest, the port of k-kinzal/doctest-php that runs PHPDoc examples as PHPUnit test cases
- `/setup-toolkit-docgen` — DocGen static documentation site with full types, relations, layers, doctest examples, and a two-revision diff mode
- `/setup-toolkit-github-actions` — GitHub Actions CI for tests, lint gates, PHP compatibility, pinned actions, and Context7 refresh
- `/setup-toolkit-fuzzing` — contract-driven, domain-aware fuzzing with reproducible corpora, a scheduled campaign on the default branch, and findings reported as issues
- `/setup-toolkit-infection` — Infection mutation testing measured daily on the default branch, with a score below the whole-tree threshold reported as an issue
- `/setup-toolkit-loc-guard` — Guard source metric policies for production source complexity and length limits
- `/setup-toolkit-phpbench` — PHPBench subjects, consistent local commands, and same-runner pull-request comparisons with artifacts
- `/setup-toolkit-php-compatibility` — PHPCompatibility gate that keeps the code runnable on the declared minimum PHP
- `/setup-toolkit-php-cs-fixer` — PHP-CS-Fixer configuration
- `/setup-toolkit-phpstan` — PHPStan at level max with strict rules and AI error formatter
- `/setup-toolkit-phpunit` — PHPUnit with strict configuration and AI test reporter
- `/setup-toolkit-pbt` — Eris property-based testing in an isolated PHPUnit group and dedicated CI workflow
- `/setup-toolkit-readme` — minimal README with badges, an overview, Requirements, Getting Started, License, and the DeepWiki link
- `/setup-toolkit-tree-guard` — Guard directory and file structure policies

Component skills share fixed toolkit defaults. They adapt project facts such as
autoload roots and supported PHP versions, but do not calibrate quality limits to
the first measured result.

## Documentation

The [API documentation site](https://k-kinzal.github.io/php-ai-toolkit/) is generated from the source by `docgen`
and published on every push to `main`.

- [Guard](packages/guard-php/docs/policies.md): Unified checks, configuration policies, staged repairs and migration
- [DocGen](packages/docgen-php/docs/docgen.md): DocGen documentation scope, caching, and generated site behavior
- [Doc policy](packages/guard-php/docs/documents.md): Guard Markdown document structure constraints
- [Doctest](packages/phpunit-ai-reporter/docs/doctest.md): Running the examples written in PHPDoc blocks as PHPUnit tests, the assertion notation, and how the port differs from upstream
- [Loc policy](packages/guard-php/docs/metrics.md): Guard source metric limits and reporting
- [PHPStan AI Formatter](packages/phpstan-ai-formatter/docs/phpstan-ai-formatter.md): The `ai` error formatter, its mode detection, and its output
- [PHPStan Rules](packages/phpstan-guard-rules/docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPUnit AI Reporter](packages/phpunit-ai-reporter/docs/phpunit-ai-reporter.md): The failure reporter for PHPUnit 9.6 and 10.5 or later
- [Tree policy](packages/guard-php/docs/structure.md): Guard directory and file structure policies
## License

MIT
