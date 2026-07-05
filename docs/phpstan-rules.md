# PHPStan Rules

Custom PHPStan rules provided by php-ai-toolkit. Each rule detects anti-patterns that actually occur in AI-generated code.

## General

Rules applied to all code.

| Rule | Description | Error Identifier |
|------|-------------|------------------|
| [ForbiddenCommentRule](rules/ForbiddenCommentRule.md) | Forbids `@phpstan-ignore` and `@infection-ignore-all` comments | `customRules.phpstanIgnoreComment`, `customRules.infectionIgnoreAllComment` |
| [ForbiddenMagicMethodCallRule](rules/ForbiddenMagicMethodCallRule.md) | Forbids direct calls to magic methods | `customRules.forbiddenMagicMethodCall` |
| [ForbiddenNamespaceRule](rules/ForbiddenNamespaceRule.md) | Forbids configured namespace prefixes such as `Tests\Support`, `Tests\Helper`, and `Tests\Util` | `customRules.forbiddenNamespace` |
| [OverrideMustHaveAttributeRule](rules/OverrideMustHaveAttributeRule.md) | Requires `#[Override]` attribute on overridden methods | `customRules.overrideMustHaveAttribute` |
| [SrcUnitTestPairRule](rules/SrcUnitTestPairRule.md) | Enforces 1:1 pairing between `src/` classes and `tests/Unit/` test classes | `customRules.srcUnitTestPair` |
| [RequirePhpDocOnPublicApiRule](rules/RequirePhpDocOnPublicApiRule.md) | Requires PHPDoc on all public API elements | `customRules.requirePhpDocOnPublicApi` |
| [ForbidNonDocCommentRule](rules/ForbidNonDocCommentRule.md) | Forbids `/* */` and `#` comments everywhere, and `//` comments outside `catch` blocks and array literals; `/** */` PHPDoc is allowed | `customRules.forbidNonDocComment` |
| [ForbidSingleLinePhpDocRule](rules/ForbidSingleLinePhpDocRule.md) | Forbids single-line PHPDoc on public elements; requires multi-line format | `customRules.forbidSingleLinePhpDoc` |
| [ForbidClassLikeNameSuffixRule](rules/ForbidClassLikeNameSuffixRule.md) | Forbids configured suffixes on class, interface, trait, and enum names | `customRules.forbiddenClassLikeNameSuffix` |
| [NoNonPublicMethodRule](rules/NoNonPublicMethodRule.md) | Forbids private methods and forbids protected methods outside abstract classes, traits, and override methods | `customRules.nonPublicMethod` |

## Test Class

Rules applied to test classes in the `Tests\Unit` / `Tests\Integration` namespaces. Rules marked with `*` apply to all `Tests\` namespaces.

| Rule | Description | Error Identifier |
|------|-------------|------------------|
| [NoPropertyInTestClassRule](rules/NoPropertyInTestClassRule.md) | Forbids property declarations in test classes | `customRules.noPropertyInTestClass` |
| [NoClassConstantInTestClassRule](rules/NoClassConstantInTestClassRule.md) | Forbids class constants in test classes | `customRules.noClassConstantInTestClass` |
| [NoPrivateMethodInTestClassRule](rules/NoPrivateMethodInTestClassRule.md) | Forbids private methods in test classes | `customRules.noPrivateMethodInTestClass` |
| [NoHelperMethodInTestClassRule](rules/NoHelperMethodInTestClassRule.md) | Forbids methods other than test/provider/override | `customRules.noHelperMethodInTestClass` |
| [NoControlFlowInTestMethodRule](rules/NoControlFlowInTestMethodRule.md) | Forbids control flow statements in test methods | `customRules.noControlFlowInTestMethod` |
| [NoTraitUseInTestClassRule](rules/NoTraitUseInTestClassRule.md) | Forbids trait usage in test classes | `customRules.noTraitUseInTestClass` |
| [NoReflectionInTestClassRule](rules/NoReflectionInTestClassRule.md) `*` | Forbids Reflection API usage in test classes | `customRules.noReflectionInTestClass` |
| [NoRedundantAssertInstanceOfRule](rules/NoRedundantAssertInstanceOfRule.md) `*` | Forbids redundant PHPUnit `assertInstanceOf()` calls for values with one statically-known type | `customRules.noRedundantAssertInstanceOf` |
| [PhpUnitMockApiRule](rules/PhpUnitMockApiRule.md) `*` | Restricts mock API and enforces interface-only mocking | `customRules.phpUnitMockApi` |
| [ForbidDescriptivePhpDocInTestClassRule](rules/ForbidDescriptivePhpDocInTestClassRule.md) | Forbids descriptive PHPDoc text in test classes | `customRules.forbidDescriptivePhpDocInTestClass` |
| [TestNamingConventionRule](rules/TestNamingConventionRule.md) | Enforces naming conventions for test methods and data providers | `customRules.testNamingConvention` |

## Disabling Rules

To disable a specific rule, suppress its error identifier in your project's `phpstan.neon`:

```neon
parameters:
    ignoreErrors:
        - identifier: customRules.infectionIgnoreAllComment
```

## Parameters

The following parameters can be customized in your project's `phpstan.neon`:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `testNamespacePrefixes` | `['Tests']` | Test namespace prefixes |
| `restrictedTestNamespacePrefixes` | `['Tests\Unit', 'Tests\Integration']` | Test namespaces where strict rules apply |
| `srcUnitTestPairExcludePatterns` | `[]` | Patterns to exclude from test pair checks |
| `srcMarker` | `'/src/'` | Source code path marker |
| `unitTestMarker` | `'/tests/Unit/'` | Unit test path marker |
| `forbiddenNamespacePrefixes` | `['Tests\Support', 'Tests\Supports', 'Tests\Helper', 'Tests\Helpers', 'Tests\Util', 'Tests\Utils', 'Tests\Utility', 'Tests\Utilities']` | Namespace prefixes to forbid |
| `forbiddenClassLikeNameSuffixes` | See [`extension.neon`](../extension.neon) | Class-like declaration name suffixes to forbid |
