# AGENTS

A comprehensive toolkit for AI-assisted PHP development. This package provides PHPStan rules, PHPUnit reporters, configuration templates, and AGENTS.md templates — everything needed to set up and maintain a PHP project where AI agents write and modify code. AI-generated code tends to introduce subtle issues that pass standard analysis; the tools in this toolkit catch those patterns early and enforce a higher quality bar.

## Project Tradeoff Sliders

- Scope     ●————————→ HIGH — Deliver the full intended scope; do not cut corners or skip requirements.
- Quality   ●————————→ HIGH — Quality is the top priority. Correctness, test coverage, and strict static analysis come first.
- Time      ←————————● LOW — There is no deadline pressure. Take the time needed to get it right.
- Cost      ←————————● LOW — Resource constraints are not a concern. Invest in doing things properly.

When in doubt, prioritize quality over everything else. It is better to ship less with confidence than to ship more with uncertainty.

## Rule Design Principles

- AI-friendly error messages: Every error message must clearly state *what is wrong* and *how to fix it*. An AI agent reading the message should be able to resolve the violation without additional context.
- Specific and identifiable: Messages must include enough detail (e.g., the offending symbol name, the expected pattern) so that each violation can be individually targeted in `ignoreErrors` configurations. Vague messages like "invalid code" are not acceptable.

## Supported Versions

- PHP 8.0 or later

## Directory Structure

```
src/
  Rule/              # PHPStan rule implementations (one class per rule)
  Support/           # Shared support classes (AgentDetector, FormatMode, etc.)
  ErrorFormatter/    # Custom error formatters
  TestReporter/      # PHPUnit extension for dual-mode test result reporting
    Subscriber/      # PHPUnit event subscribers
tests/
  Unit/
    Rule/            # Rule tests using PHPStan\Testing\RuleTestCase
    Support/         # Support class tests
    ErrorFormatter/  # Error formatter tests
    TestReporter/    # Test reporter tests
  Support/           # Test helper classes (e.g. PhpUnitEventFactory)
  Fixture/           # PHP fixture files consumed by tests
extension.neon       # PHPStan extension — registers all rules and services
error-formatter.neon # Optional error formatter (not auto-included)
phpstan.neon         # Self-analysis config (level max + strict-rules)
phpunit.xml.dist     # PHPUnit config (strict mode + test reporter extension)
docs/                # Documentation
```

## Document Index

- [PHPStan Rules](docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPStan Configuration](docs/phpstan.md): PHPStan settings and why each is needed
- [PHPUnit Configuration](docs/phpunit.md): PHPUnit settings and why each is needed
- [PHP-CS-Fixer Configuration](docs/php-cs-fixer.md): PHP-CS-Fixer settings and why each is needed

**Rule Documentation**

- [ForbidDescriptivePhpDocInTestClassRule.md](docs/rules/ForbidDescriptivePhpDocInTestClassRule.md): Forbids descriptive PHPDoc text in test classes
- [ForbiddenCommentRule.md](docs/rules/ForbiddenCommentRule.md): Forbids suppression comments such as `@phpstan-ignore` and `@infection-ignore-all`
- [ForbiddenMagicMethodCallRule.md](docs/rules/ForbiddenMagicMethodCallRule.md): Reports direct calls to PHP magic methods like `__construct`, `__toString`, etc.
- [ForbidNonDocCommentRule.md](docs/rules/ForbidNonDocCommentRule.md): Forbids all non-PHPDoc comments (`//`, `/* */`, `#`)
- [ForbidSingleLinePhpDocRule.md](docs/rules/ForbidSingleLinePhpDocRule.md): Forbids single-line PHPDoc comments on public API elements
- [NoClassConstantInTestClassRule.md](docs/rules/NoClassConstantInTestClassRule.md): Forbids class constant declarations in test classes
- [NoControlFlowInTestMethodRule.md](docs/rules/NoControlFlowInTestMethodRule.md): Forbids control flow statements (if/for/while, etc.) inside test methods
- [NoHelperMethodInTestClassRule.md](docs/rules/NoHelperMethodInTestClassRule.md): Forbids methods in test classes other than test methods, data providers, and framework hooks
- [NoPrivateMethodInTestClassRule.md](docs/rules/NoPrivateMethodInTestClassRule.md): Forbids private method declarations in test classes
- [NoPropertyInTestClassRule.md](docs/rules/NoPropertyInTestClassRule.md): Forbids property declarations in test classes
- [NoReflectionInTestClassRule.md](docs/rules/NoReflectionInTestClassRule.md): Forbids usage of the Reflection API in test classes
- [NoTraitUseInTestClassRule.md](docs/rules/NoTraitUseInTestClassRule.md): Forbids trait use statements in test classes
- [OverrideMustHaveAttributeRule.md](docs/rules/OverrideMustHaveAttributeRule.md): Requires the `#[Override]` attribute when overriding a non-abstract parent method
- [PhpUnitMockApiRule.md](docs/rules/PhpUnitMockApiRule.md): Restricts PHPUnit mock API to interface-only mocking and detects prohibited mock APIs
- [RequirePhpDocOnPublicApiRule.md](docs/rules/RequirePhpDocOnPublicApiRule.md): Requires PHPDoc comments on public API classes, methods, properties, and constants
- [SrcUnitTestPairRule.md](docs/rules/SrcUnitTestPairRule.md): Ensures every class in `src/` has a matching test in `tests/Unit/` and vice versa
- [TestNamingConventionRule.md](docs/rules/TestNamingConventionRule.md): Enforces PascalCase naming for test methods and data providers, and prohibits testing constructors/destructors directly