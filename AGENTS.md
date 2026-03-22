<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->
# AGENTS

A comprehensive toolkit for AI-assisted PHP development. This package provides PHPStan rules, PHPUnit reporters, configuration templates, and AGENTS.md
templates — everything needed to set up and maintain a PHP project where AI agents write and modify code. AI-generated code tends to introduce subtle issues
that pass standard analysis; the tools in this toolkit catch those patterns early and enforce a higher quality bar.

## Project Tradeoff Sliders

- Scope     ●————————→ HIGH — Deliver the full intended scope; do not cut corners or skip requirements.
- Quality   ●————————→ HIGH — Quality is the top priority. Correctness, test coverage, and strict static analysis come first.
- Time      ←————————● LOW — There is no deadline pressure. Take the time needed to get it right.
- Cost      ←————————● LOW — Resource constraints are not a concern. Invest in doing things properly.

When in doubt, prioritize quality over everything else. It is better to ship less with confidence than to ship more with uncertainty.

## Supported Versions

- **PHP**: 8.0 or later
- **PHPStan**: ^1.12 || ^2.0
- **PHPUnit**: ^10.5 || ^11 || ^12 || ^13

## Tech Stack

- **Language**: PHP 8.0+
- **Static Analysis**: PHPStan (level max + strict-rules for self-analysis)
- **Testing**: PHPUnit with ParaTest for parallel execution
- **Code Style**: PHP-CS-Fixer
- **Package Type**: Composer phpstan-extension (auto-registered via `extra.phpstan.includes`)

## Architecture

The toolkit provides three independent extension points that integrate into a PHP project's existing toolchain, plus a CLI for project setup.

| Layer | Responsibility | Entry point |
  |-------|---------------|-------------|
| **Rule** | PHPStan rules that detect AI-specific code issues (17 rules) | `src/Rule/` — one class per rule |
| **Support** | Shared services used by rules — test class detection, agent detection, format mode | `src/Support/` |
| **ErrorFormatter** | Dual-mode PHPStan error formatter — human-readable or machine-readable depending on caller |
  `src/ErrorFormatter/AiRulesErrorFormatter.php` |
| **TestReporter** | PHPUnit extension that collects and formats test issues with AI-friendly messages | `src/TestReporter/AiTestReporterExtension.php` |
| **Cli** | CLI binary for installing skills and templates into target projects | `src/Cli/Command/InstallCommand.php` |

Integration: PHPStan loads `extension.neon` which registers all Rule and Support services. Optionally, `error-formatter.neon` registers the ErrorFormatter.
PHPUnit loads the TestReporter extension via `phpunit.xml`. The CLI (`bin/php-ai-toolkit`) operates independently.

```
src/
  Cli/
    Command/           # CLI commands (InstallCommand)
  Rule/                # PHPStan rule implementations (one class per rule)
  Support/             # Shared support classes (AgentDetector, FormatMode, TestClassScope)
  ErrorFormatter/      # Custom PHPStan error formatter
  TestReporter/        # PHPUnit extension for dual-mode test result reporting
    Subscriber/        # PHPUnit event subscribers
tests/
  Unit/
    Cli/Command/       # CLI command tests
    Rule/              # Rule tests using PHPStan\Testing\RuleTestCase
    Support/           # Support class tests
    ErrorFormatter/    # Error formatter tests
    TestReporter/      # Test reporter tests
      Subscriber/      # Subscriber tests
  Support/             # Test helper classes (e.g. PhpUnitEventFactory)
  Fixture/             # PHP fixture files consumed by tests
skills/                # Setup skill templates (AGENTS.md, PHPStan, PHPUnit, PHP-CS-Fixer)
docs/                  # Documentation
extension.neon         # PHPStan extension — registers all rules and services
error-formatter.neon   # Optional error formatter (not auto-included)
phpstan.neon           # Self-analysis config (level max + strict-rules)
phpunit.xml.dist       # PHPUnit config (strict mode + test reporter extension)
```

## Rule Design Principles

- AI-friendly error messages: Every error message must clearly state *what is wrong* and *how to fix it*. An AI agent reading the message should be able to resolve the violation without additional context.
- Specific and identifiable: Messages must include enough detail (e.g., the offending symbol name, the expected pattern) so that each violation can be individually targeted in `ignoreErrors` configurations. Vague messages like "invalid code" are not acceptable.

## Document References

- [PHPStan Rules](docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPStan Configuration](docs/phpstan.md): PHPStan settings and why each is needed
- [PHPUnit Configuration](docs/phpunit.md): PHPUnit settings and why each is needed
- [PHP-CS-Fixer Configuration](docs/php-cs-fixer.md): PHP-CS-Fixer settings and why each is needed

**Rule Documentation**
- [ForbidDescriptivePhpDocInTestClassRule](docs/rules/ForbidDescriptivePhpDocInTestClassRule.md): Forbids descriptive PHPDoc text in test classes
- [ForbiddenCommentRule](docs/rules/ForbiddenCommentRule.md): Forbids suppression comments such as `@phpstan-ignore` and `@infection-ignore-all`
- [ForbiddenMagicMethodCallRule](docs/rules/ForbiddenMagicMethodCallRule.md): Reports direct calls to PHP magic methods like `__construct`, `__toString`, etc.
- [ForbidNonDocCommentRule](docs/rules/ForbidNonDocCommentRule.md): Forbids all non-PHPDoc comments (`//`, `/* */`, `#`)
- [ForbidSingleLinePhpDocRule](docs/rules/ForbidSingleLinePhpDocRule.md): Forbids single-line PHPDoc comments on public API elements
- [NoClassConstantInTestClassRule](docs/rules/NoClassConstantInTestClassRule.md): Forbids class constant declarations in test classes
- [NoControlFlowInTestMethodRule](docs/rules/NoControlFlowInTestMethodRule.md): Forbids control flow statements (if/for/while, etc.) inside test methods
- [NoHelperMethodInTestClassRule](docs/rules/NoHelperMethodInTestClassRule.md): Forbids methods in test classes other than test methods, data providers, and framework hooks
- [NoPrivateMethodInTestClassRule](docs/rules/NoPrivateMethodInTestClassRule.md): Forbids private method declarations in test classes
- [NoPropertyInTestClassRule](docs/rules/NoPropertyInTestClassRule.md): Forbids property declarations in test classes
- [NoReflectionInTestClassRule](docs/rules/NoReflectionInTestClassRule.md): Forbids usage of the Reflection API in test classes
- [NoTraitUseInTestClassRule](docs/rules/NoTraitUseInTestClassRule.md): Forbids trait use statements in test classes
- [OverrideMustHaveAttributeRule](docs/rules/OverrideMustHaveAttributeRule.md): Requires the `#[Override]` attribute when overriding a non-abstract parent method
- [PhpUnitMockApiRule](docs/rules/PhpUnitMockApiRule.md): Restricts PHPUnit mock API to interface-only mocking and detects prohibited mock APIs
- [RequirePhpDocOnPublicApiRule](docs/rules/RequirePhpDocOnPublicApiRule.md): Requires PHPDoc comments on public API classes, methods, properties, and constants
- [SrcUnitTestPairRule](docs/rules/SrcUnitTestPairRule.md): Ensures every class in `src/` has a matching test in `tests/Unit/` and vice versa
- [TestNamingConventionRule](docs/rules/TestNamingConventionRule.md): Enforces PascalCase naming for test methods and data providers, and prohibits testing constructors/destructors directly