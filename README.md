# php-ai-toolkit

A PHPStan extension that detects anti-patterns commonly introduced by AI code generation, plus output formatters optimized for both AI agents and humans.

## Requirements

- PHP ^8.0
- PHPStan ^1.12 || ^2.0
- PHPUnit ^10.5 || ^11 || ^12 || ^13 (for Test Reporter)

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
- `/setup-toolkit-php-cs-fixer` — PHP-CS-Fixer configuration
- `/setup-toolkit-loc-guard` — LocGuard metrics checks for production source complexity and length limits
- `/setup-toolkit-deptrac` — Deptrac architecture dependency rules for web apps, CLI apps, libraries, and modular projects
- `/setup-toolkit-agents-md` — AGENTS.md with project conventions and AI agent guidelines

Each skill reads your project structure and generates appropriate configuration.

## Documentation

- [PHPStan Rules](docs/phpstan-rules.md) — Custom rules and their error identifiers
- [PHPStan Configuration](docs/phpstan.md) — PHPStan settings and why each is needed
- [PHPUnit Configuration](docs/phpunit.md) — PHPUnit settings and why each is needed
- [PHP-CS-Fixer Configuration](docs/php-cs-fixer.md) — PHP-CS-Fixer settings and why each is needed
- [LocGuard Configuration](docs/loc-guard.md) — Production source metrics checks and thresholds
- [Deptrac Configuration](docs/deptrac.md) — Architecture dependency rules and adaptation guidance

## License

MIT
