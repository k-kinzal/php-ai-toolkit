# php-ai-toolkit

[![docs](https://img.shields.io/badge/docs-php--ai--toolkit-0969da?logo=php&logoColor=white)](https://k-kinzal.github.io/php-ai-toolkit/)
[![CI](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/ci.yml)
[![Docs](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/docs.yml/badge.svg?branch=main)](https://github.com/k-kinzal/php-ai-toolkit/actions/workflows/docs.yml)
[![PHP](https://img.shields.io/badge/php-8.0%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

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

This package is not published on Packagist. Install it from the VCS repository by adding the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/k-kinzal/php-ai-toolkit.git"
        }
    ]
}
```

Then require it as a dev dependency:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

### 2. Install AI Agent Skills

```bash
vendor/bin/php-ai-toolkit install
```

Auto-detects AI agent directories (`.claude`, `.agents`, `.continue`, etc.) in your project root and installs skills. Use `--force` to overwrite, `--copy` to copy instead of symlinking.

### 3. Apply the toolkit

Run the end-to-end adoption skill:

- `/setup-php-ai-toolkit` — applies the complete opinionated baseline, repairs
  design and tests instead of weakening configuration, preserves public API and
  product-owned docs/AGENTS.md, and asks how DocGen should publish

The component skills are also available for focused setup or maintenance:

- `/setup-toolkit-agents-md` — AGENTS.md with project conventions and AI agent guidelines
- `/setup-toolkit-deptrac` — Deptrac architecture dependency rules for web apps, CLI apps, libraries, and modular projects
- `/setup-toolkit-doctest` — Doctest, the port of k-kinzal/doctest-php that runs PHPDoc examples as PHPUnit test cases
- `/setup-toolkit-docgen` — DocGen static documentation site with full types, relations, layers, doctest examples, and a two-revision diff mode
- `/setup-toolkit-github-actions` — GitHub Actions CI for tests, lint gates, PHP compatibility, and pinned actions
- `/setup-toolkit-fuzzing` — contract-driven, domain-aware fuzzing with reproducible corpora, crash artifacts, and scheduled CI
- `/setup-toolkit-infection` — Infection mutation testing with a whole-tree threshold and a stricter one for pull requests
- `/setup-toolkit-loc-guard` — LocGuard metrics checks for production source complexity and length limits
- `/setup-toolkit-php-compatibility` — PHPCompatibility gate that keeps the code runnable on the declared minimum PHP
- `/setup-toolkit-php-cs-fixer` — PHP-CS-Fixer configuration
- `/setup-toolkit-phpstan` — PHPStan at level max with strict rules and AI error formatter
- `/setup-toolkit-phpunit` — PHPUnit with strict configuration and AI test reporter
- `/setup-toolkit-pbt` — Eris property-based testing in an isolated PHPUnit group and dedicated CI workflow
- `/setup-toolkit-tree-guard` — TreeGuard directory and file structure constraints

Component skills share fixed toolkit defaults. They adapt project facts such as
autoload roots and supported PHP versions, but do not calibrate quality limits to
the first measured result.

## Documentation

The [API documentation site](https://k-kinzal.github.io/php-ai-toolkit/) is generated from the source by `docgen`
and published on every push to `main`.

- [DocGen](docs/docgen.md): DocGen documentation scope, caching, and generated site behavior
- [Doctest](docs/doctest.md): Running the examples written in PHPDoc blocks as PHPUnit tests, the assertion notation, and how the port differs from upstream
- [LocGuard](docs/loc-guard.md): LocGuard source metric limits and reporting
- [PHPStan AI Formatter](docs/phpstan-ai-formatter.md): The `ai` error formatter, its mode detection, and its output
- [PHPStan Rules](docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPUnit AI Reporter](docs/phpunit-ai-reporter.md): The failure reporter for PHPUnit 9.6 and 10.5 or later
- [TreeGuard](docs/tree-guard.md): TreeGuard directory and file structure constraints

## License

MIT
