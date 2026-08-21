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

### 3. Run setup skills

Run the following skills in your AI agent:

- `/setup-toolkit-phpstan` — PHPStan at level max with strict rules and AI error formatter
- `/setup-toolkit-phpunit` — PHPUnit with strict configuration and AI test reporter
- `/setup-toolkit-infection` — Infection mutation testing with a whole-tree threshold and a stricter one for pull requests
- `/setup-toolkit-php-cs-fixer` — PHP-CS-Fixer configuration
- `/setup-toolkit-php-compatibility` — PHPCompatibility gate that keeps the code runnable on the declared minimum PHP
- `/setup-toolkit-loc-guard` — LocGuard metrics checks for production source complexity and length limits
- `/setup-toolkit-tree-guard` — TreeGuard directory and file structure constraints
- `/setup-toolkit-scope-guard` — ScopeGuard namespace visibility scopes, the PHP counterpart of Rust's `pub(crate)`
- `/setup-toolkit-doc-gen` — DocGen static documentation site with full types, relations, layers, doctest examples, and a two-revision diff mode
- `/setup-toolkit-deptrac` — Deptrac architecture dependency rules for web apps, CLI apps, libraries, and modular projects
- `/setup-toolkit-github-actions` — GitHub Actions CI for tests, lint gates, PHP compatibility, and pinned actions
- `/setup-toolkit-agents-md` — AGENTS.md with project conventions and AI agent guidelines

Each skill reads your project structure and generates appropriate configuration.

## Documentation

The [API documentation site](https://k-kinzal.github.io/php-ai-toolkit/) is generated from the source by `doc-gen`
and published on every push to `main`.

- [PHPStan Rules](docs/phpstan-rules.md) — Custom rules and their error identifiers
- [PHPStan Configuration](docs/phpstan.md) — PHPStan settings and why each is needed
- [PHPUnit Configuration](docs/phpunit.md) — PHPUnit settings and why each is needed
- [Infection Configuration](docs/infection.md) — Mutation testing thresholds, and why they differ between the whole tree and a pull request
- [PHP-CS-Fixer Configuration](docs/php-cs-fixer.md) — PHP-CS-Fixer settings and why each is needed
- [PHPCompatibility Configuration](docs/php-compatibility.md) — The PHP version floor gate and why it runs on `phpcs`
- [LocGuard Configuration](docs/loc-guard.md) — Production source metrics checks and thresholds
- [TreeGuard Configuration](docs/tree-guard.md) — Directory and file structure constraints
- [ScopeGuard Configuration](docs/scope-guard.md) — Namespace visibility scopes declared with `@visibility`
- [DocGen Configuration](docs/doc-gen.md) — Static documentation site generation and scope control
- [Deptrac Configuration](docs/deptrac.md) — Architecture dependency rules and adaptation guidance
- [GitHub Actions Configuration](docs/github-actions.md) — CI jobs, PHP version coverage, and workflow hardening

## License

MIT
