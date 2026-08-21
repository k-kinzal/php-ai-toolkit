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

## Branching Strategy

This project is developed on `main` only. Commit directly to `main`, whatever the size of the change.

- Do not create branches. A branch cannot be merged back here, so work left on one is stranded.
- Do not open pull requests. They are not part of this project's workflow.
- The common agent habit of branching before committing on a default branch does not apply here: `main` is the working branch.

## Supported Versions

- **PHP**: 8.0 or later
- **PHPStan**: ^1.12 || ^2.0
- **PHPUnit**: ^9.6 || ^10.5 || ^11 || ^12 || ^13
- **nikic/php-parser**: ^4.19 || ^5.0
- **phpstan/phpdoc-parser**: ^1.33 || ^2.0

Every one of those lines is exercised. The PHP 8.0 lock resolves PHPStan 1.12 with php-parser 4, phpdoc-parser 1, and
PHPUnit 9.6, because the only deptrac line that installs on PHP 8.0 pins php-parser 4 and the PHPStan 2 phar carries
php-parser 5 under the same class names; the PHP 8.1 and later locks resolve PHPStan 2 with php-parser 5,
phpdoc-parser 2, and PHPUnit 10.5 or newer. Code that reads a parser node or prints a documented type therefore has to
work under either major of both parsers: a class only one of them ships is never named, and what a rule reports is
ordered by the rule rather than by the analyzer. See [GitHub Actions Configuration](docs/github-actions.md) for how
the locks are refreshed.

## Tech Stack

- **Language**: PHP 8.0+
- **Static Analysis**: PHPStan (level max + strict-rules + checked-exception analysis for self-analysis)
- **Documentation Tests**: Doctest, running the examples written in PHPDoc blocks — `composer doctest` on every supported PHP minor, and `DoctestTestCase` for projects that would rather run them through PHPUnit
- **Testing**: PHPUnit with ParaTest for parallel execution — `composer test:unit` runs the suite in one process on every supported PHP minor, `composer test` splits it across processes once
- **Mutation Testing**: Infection, run only by the `mutation` job in CI — there are deliberately no `infection` Composer scripts, so `composer infection` reporting "Command infection is not defined" is the design, not a break
- **Code Style**: PHP-CS-Fixer
- **PHP Version Floor**: PHPCompatibility on `phpcs`, gating every file against the declared 8.0 floor
- **Source Guards**: LocGuard, TreeGuard, and ScopeGuard — first-party CLIs shipped by this package for source metrics, directory structure, and namespace visibility
- **Architecture**: Deptrac, over layers discovered from the directory structure
- **Documentation**: DocGen, publishing the API site to GitHub Pages on every push to `main`
- **Package Type**: Composer phpstan-extension (auto-registered via `extra.phpstan.includes`)

Every tool named here is wired into `composer lint` or a CI job and has a page under `docs/`. Adding a tool to this project means doing all three: a gate that is configured but never runs on `main` is one the project only believes it has.

## Rule Design Principles

- AI-friendly error messages: Every error message must clearly state *what is wrong* and *how to fix it*. An AI agent reading the message should be able to resolve the violation without additional context.
- Specific and identifiable: Messages must include enough detail (e.g., the offending symbol name, the expected pattern) so that each violation can be individually targeted in `ignoreErrors` configurations. Vague messages like "invalid code" are not acceptable.

## Document References

- [PHPStan Rules](docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPStan Configuration](docs/phpstan.md): PHPStan settings and why each is needed
- [PHPUnit Configuration](docs/phpunit.md): PHPUnit settings and why each is needed
- [Infection Configuration](docs/infection.md): Mutation testing thresholds for the whole source tree and for pull requests
- [PHP-CS-Fixer Configuration](docs/php-cs-fixer.md): PHP-CS-Fixer settings and why each is needed
- [PHPCompatibility Configuration](docs/php-compatibility.md): The PHP version floor gate and why it runs on `phpcs`
- [LocGuard Configuration](docs/loc-guard.md): LocGuard source metric limits and reporting
- [TreeGuard Configuration](docs/tree-guard.md): TreeGuard directory and file structure constraints
- [ScopeGuard Configuration](docs/scope-guard.md): Namespace visibility scopes declared with `@visibility`
- [Doctest Configuration](docs/doctest.md): Running the examples written in PHPDoc blocks, example identifiers, and the assertion notation
- [DocGen Configuration](docs/doc-gen.md): DocGen documentation scope, caching, and generated site behavior
- [Deptrac Configuration](docs/deptrac.md): Architectural layer discovery and dependency rules
- [GitHub Actions Configuration](docs/github-actions.md): CI coverage, quality gates, and workflow hardening

**Rule Documentation**
- [ForbidBroadCatchRule](docs/rules/ForbidBroadCatchRule.md): Forbids catching Throwable, Exception, and the LogicException/Error families outside configured boundary paths
- [ForbidClassLikeNameSuffixRule](docs/rules/ForbidClassLikeNameSuffixRule.md): Forbids class, interface, trait, and enum names ending with configured generic suffixes
- [ForbidDescriptivePhpDocInTestClassRule](docs/rules/ForbidDescriptivePhpDocInTestClassRule.md): Forbids descriptive PHPDoc text in test classes
- [ForbidEmptyCatchRule](docs/rules/ForbidEmptyCatchRule.md): Forbids catch blocks with an empty body
- [ForbidGenericThrowsTagRule](docs/rules/ForbidGenericThrowsTagRule.md): Forbids `@throws \Exception` and `@throws \Throwable` tags
- [ForbiddenCommentRule](docs/rules/ForbiddenCommentRule.md): Forbids suppression comments such as `@phpstan-ignore` and `@infection-ignore-all`
- [ForbiddenMagicMethodCallRule](docs/rules/ForbiddenMagicMethodCallRule.md): Reports direct calls to PHP magic methods like `__construct`, `__toString`, etc.
- [ForbiddenNamespaceRule](docs/rules/ForbiddenNamespaceRule.md): Forbids namespaces that match or descend from configured namespace prefixes
- [ForbidNonDocCommentRule](docs/rules/ForbidNonDocCommentRule.md): Forbids all non-PHPDoc comments (`//`, `/* */`, `#`)
- [ForbidSingleLinePhpDocRule](docs/rules/ForbidSingleLinePhpDocRule.md): Forbids single-line PHPDoc comments on public API elements
- [NoBrokenCodeExpectationRule](docs/rules/NoBrokenCodeExpectationRule.md): Forbids expecting Throwable and the LogicException/Error families in PHPUnit exception expectations
- [NoClassConstantInTestClassRule](docs/rules/NoClassConstantInTestClassRule.md): Forbids class constant declarations in test classes
- [NoControlFlowInTestMethodRule](docs/rules/NoControlFlowInTestMethodRule.md): Forbids control flow statements (if/for/while, etc.) inside test methods
- [NoHelperMethodInTestClassRule](docs/rules/NoHelperMethodInTestClassRule.md): Forbids methods in test classes other than test methods, data providers, and framework hooks
- [NoNonPublicMethodRule](docs/rules/NoNonPublicMethodRule.md): Forbids private methods and restricts protected methods to inheritance boundaries
- [NoPrivateMethodInTestClassRule](docs/rules/NoPrivateMethodInTestClassRule.md): Forbids private method declarations in test classes
- [NoPropertyInTestClassRule](docs/rules/NoPropertyInTestClassRule.md): Forbids property declarations in test classes
- [NoRedundantAssertInstanceOfRule](docs/rules/NoRedundantAssertInstanceOfRule.md): Forbids PHPUnit `assertInstanceOf()` calls when the asserted type is already statically guaranteed
- [NoReflectionInTestClassRule](docs/rules/NoReflectionInTestClassRule.md): Forbids usage of the Reflection API in test classes
- [NoTraitUseInTestClassRule](docs/rules/NoTraitUseInTestClassRule.md): Forbids trait use statements in test classes
- [OverrideMustHaveAttributeRule](docs/rules/OverrideMustHaveAttributeRule.md): Requires the `#[Override]` attribute when overriding a non-abstract parent method
- [PhpUnitMockApiRule](docs/rules/PhpUnitMockApiRule.md): Restricts PHPUnit mock API to interface-only mocking and detects prohibited mock APIs
- [RequireExceptionChainingRule](docs/rules/RequireExceptionChainingRule.md): Requires new exceptions thrown inside catch blocks to chain the caught exception
- [RequireExampleOnPublicApiRule](docs/rules/RequireExampleOnPublicApiRule.md): Requires a runnable `@example` on declarations marked `@visibility public`
- [RequireExhaustiveDispatchRule](docs/rules/RequireExhaustiveDispatchRule.md): Requires a switch or match that names its subject — `match ($suit)`, or `match ($payment::class)` for a sealed hierarchy — to name a branch for every value that subject can hold
- [RequirePhpDocOnPublicApiRule](docs/rules/RequirePhpDocOnPublicApiRule.md): Requires PHPDoc comments on public API classes, methods, properties, and constants
- [RequireThrowsTagOnDirectThrowRule](docs/rules/RequireThrowsTagOnDirectThrowRule.md): Requires `@throws` for exceptions thrown directly in a method and not caught within it
- [SrcUnitTestPairRule](docs/rules/SrcUnitTestPairRule.md): Ensures every class in `src/` has a matching test in `tests/Unit/` and vice versa
- [TestNamingConventionRule](docs/rules/TestNamingConventionRule.md): Enforces PascalCase naming for test methods and data providers, and prohibits testing constructors/destructors directly
