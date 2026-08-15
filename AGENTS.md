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
- **Static Analysis**: PHPStan (level max + strict-rules + checked-exception analysis for self-analysis)
- **Testing**: PHPUnit with ParaTest for parallel execution
- **Code Style**: PHP-CS-Fixer
- **Package Type**: Composer phpstan-extension (auto-registered via `extra.phpstan.includes`)

## Architecture

The toolkit provides PHPStan and PHPUnit integrations, shared runtime services, an installer CLI, and two independent guard CLIs.

| Layer | Responsibility | Entry point |
|-------|---------------|-------------|
| **Rule** | PHPStan rules that detect AI-specific code issues (26 rules) | `src/PhpStan/Rule/` — one class per rule at the top level; single-rule collaborators live in a per-rule subdirectory named after the rule without the `Rule` suffix (for example, `src/PhpStan/Rule/TestNamingConvention/`), while collaborators used by two or more rules live in `src/PhpStan/Rule/Shared/` |
| **Support** | Test class detection shared by PHPStan rules | `src/PhpStan/Support/` |
| **ThrowType** | Throw metadata for internal PHP functions missing from PHPStan (currently `token_get_all`) | `src/PhpStan/ThrowType/` |
| **ErrorFormatter** | Dual-mode PHPStan error formatter — human-readable or machine-readable depending on caller | `src/PhpStan/ErrorFormatter/AiRulesErrorFormatter.php` |
| **TestReporter** | PHPUnit extension that collects and formats test issues with AI-friendly messages | `src/PhpUnit/TestReporter/AiTestReporterExtension.php`, with `Subscriber/` and `Legacy/` |
| **Shared** | Agent detection and format mode used by ErrorFormatter and TestReporter | `src/Shared/` (`AgentDetector`, `FormatMode`) |
| **Installer CLI** | Installs skills and templates into target projects | `src/Installer/Cli/Application.php`, binary `bin/php-ai-toolkit` |
| **LocGuard CLI** | Checks source LOC, NCLOC, length, and cyclomatic complexity metrics | `src/LocGuard/` (`Cli/`, `Config/`, `Filesystem/`, `Analysis/` with `Token/`, `Complexity/`, `FunctionMetric/`, `ClassLikeMetric/`, and `FileMetric/`, and `Reporting/`), binary `bin/loc-guard`, config `loc.yaml` |
| **TreeGuard CLI** | Enforces per-directory file and subdirectory counts, recursive subtree totals, nesting depth, file and directory naming globs and case conventions, required files, and empty-directory detection | `src/TreeGuard/` (`Cli/`, `Config/`, `Filesystem/`, `Analysis/`, `Reporting/`), binary `bin/tree-guard`, config `tree.yaml` |

Integration: PHPStan loads `extension.neon`, which registers all 26 Rule services, their Support service, and the ThrowType extension service.
Optionally, `error-formatter.neon` registers the ErrorFormatter. PHPUnit loads the TestReporter extension via `phpunit.xml.dist`. The Installer CLI
(`bin/php-ai-toolkit`), LocGuard (`bin/loc-guard`), and TreeGuard (`bin/tree-guard`) operate independently. `deptrac.yaml` defines LocGuard and
TreeGuard as dependency-free toolkit layers: neither may depend on another toolkit layer. Self-analysis (`phpstan.neon`) additionally enables
PHPStan's checked-exception analysis (`exceptions.check.*` with `implicitThrows: false`): LogicException, RuntimeException, and Error families are
unchecked; everything else must be caught or declared with `@throws`.

```
src/
  Installer/
    Cli/               # Project setup application
      Command/         # Skill installation command and collaborators
  LocGuard/
    Analysis/          # Source metric analysis
      Token/           # PHP token navigation and line counting
      Complexity/      # Cyclomatic complexity calculation
      FunctionMetric/  # Function and method metrics
      ClassLikeMetric/ # Class, trait, interface, and enum metrics
      FileMetric/      # File LOC and NCLOC metrics
    Cli/               # LocGuard command-line application
    Config/            # loc.yaml loading and validation
    Filesystem/        # PHP source discovery
    Reporting/         # AI, text, and JSON reporters
  PhpStan/
    ErrorFormatter/    # Dual-mode PHPStan error formatter
    Rule/              # 26 top-level PHPStan rule classes
      TestNamingConvention/ # Example per-rule collaborator directory
      Shared/          # Collaborators used by multiple rules
    Support/           # Test class detection
    ThrowType/         # Throw metadata for internal PHP functions
  PhpUnit/
    TestReporter/      # Dual-mode PHPUnit test result reporting
      Subscriber/      # PHPUnit event subscribers
      Legacy/          # PHPUnit 9 listener support
  Shared/              # AgentDetector and FormatMode
  TreeGuard/
    Analysis/          # Directory structure analysis
    Cli/               # TreeGuard command-line application
    Config/            # tree.yaml loading and validation
    Filesystem/        # Directory tree scanning
    Reporting/         # AI, text, and JSON reporters
tests/
  Unit/                # Unit-test mirror of all six src/ top-level directories
    Installer/
      Cli/             # Installer CLI tests
    LocGuard/
      Analysis/        # Metric analysis tests, including all five subdirectory mirrors
      Cli/             # LocGuard CLI tests
      Config/          # LocGuard configuration tests
      Filesystem/      # LocGuard source discovery tests
      Reporting/       # LocGuard reporter tests
    PhpStan/
      ErrorFormatter/  # Error formatter tests
      Rule/            # Rule and rule collaborator tests
      Support/         # Test class detection tests
      ThrowType/       # Throw metadata extension tests
    PhpUnit/
      TestReporter/    # Test reporter tests
        Subscriber/    # Subscriber tests
        Legacy/        # PHPUnit 9 listener tests
    Shared/            # Shared runtime service tests
    TreeGuard/
      Analysis/        # Directory structure analysis tests
      Cli/             # TreeGuard CLI tests
      Config/          # TreeGuard configuration tests
      Filesystem/      # TreeGuard scanning tests
      Reporting/       # TreeGuard reporter tests
  Fixture/             # PHP fixture files consumed by rule and formatter tests
skills/                # Nine setup skills, including setup-toolkit-tree-guard
docs/                  # Documentation
extension.neon         # PHPStan extension — registers all rules and services
error-formatter.neon   # Optional error formatter (not auto-included)
phpstan.neon           # Self-analysis config (level max + strict-rules + checked exceptions)
phpunit.xml.dist       # PHPUnit config (strict mode + test reporter extension)
loc.yaml               # LocGuard source-metric limits
tree.yaml              # TreeGuard structure constraints
deptrac.yaml           # Architectural dependency rules
```

## Rule Design Principles

- AI-friendly error messages: Every error message must clearly state *what is wrong* and *how to fix it*. An AI agent reading the message should be able to resolve the violation without additional context.
- Specific and identifiable: Messages must include enough detail (e.g., the offending symbol name, the expected pattern) so that each violation can be individually targeted in `ignoreErrors` configurations. Vague messages like "invalid code" are not acceptable.

## Document References

- [PHPStan Rules](docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPStan Configuration](docs/phpstan.md): PHPStan settings and why each is needed
- [PHPUnit Configuration](docs/phpunit.md): PHPUnit settings and why each is needed
- [PHP-CS-Fixer Configuration](docs/php-cs-fixer.md): PHP-CS-Fixer settings and why each is needed
- [LocGuard Configuration](docs/loc-guard.md): LocGuard source metric limits and reporting
- [TreeGuard Configuration](docs/tree-guard.md): TreeGuard directory and file structure constraints
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
- [RequirePhpDocOnPublicApiRule](docs/rules/RequirePhpDocOnPublicApiRule.md): Requires PHPDoc comments on public API classes, methods, properties, and constants
- [RequireThrowsTagOnDirectThrowRule](docs/rules/RequireThrowsTagOnDirectThrowRule.md): Requires `@throws` for exceptions thrown directly in a method and not caught within it
- [SrcUnitTestPairRule](docs/rules/SrcUnitTestPairRule.md): Ensures every class in `src/` has a matching test in `tests/Unit/` and vice versa
- [TestNamingConventionRule](docs/rules/TestNamingConventionRule.md): Enforces PascalCase naming for test methods and data providers, and prohibits testing constructors/destructors directly
