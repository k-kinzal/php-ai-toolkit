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
| [RequireExampleOnPublicApiRule](rules/RequireExampleOnPublicApiRule.md) | Requires a runnable `@example` on declarations marked `@visibility public` | `customRules.requireExampleOnClass`, `customRules.requireExampleOnMethod`, `customRules.requireExampleOnProperty`, `customRules.requireExampleOnConstant`, `customRules.requireExampleOnEnumCase` |
| [EnforceVisibilityScopeRule](rules/EnforceVisibilityScopeRule.md) | Enforces namespace visibility scopes declared with `@visibility` and treats explicitly public API as externally used | `customRules.visibilityInvalidScope`, `customRules.visibilityOutOfScope` |
| [ForbidNonDocCommentRule](rules/ForbidNonDocCommentRule.md) | Forbids `/* */` and `#` comments everywhere, and `//` comments outside `catch` blocks and array literals; `/** */` PHPDoc is allowed | `customRules.forbidNonDocComment` |
| [ForbidSingleLinePhpDocRule](rules/ForbidSingleLinePhpDocRule.md) | Forbids single-line PHPDoc on public elements; requires multi-line format | `customRules.forbidSingleLinePhpDoc` |
| [ForbidClassLikeNameSuffixRule](rules/ForbidClassLikeNameSuffixRule.md) | Forbids configured suffixes on class, interface, trait, and enum names | `customRules.forbiddenClassLikeNameSuffix` |
| [NoNonPublicMethodRule](rules/NoNonPublicMethodRule.md) | Forbids private methods and forbids protected methods outside abstract classes, traits, and override methods | `customRules.nonPublicMethod` |
| [ForbidEmptyCatchRule](rules/ForbidEmptyCatchRule.md) | Forbids catch blocks with an empty body | `customRules.emptyCatch` |
| [RequireThrowsTagOnDirectThrowRule](rules/RequireThrowsTagOnDirectThrowRule.md) | Requires `@throws` for exceptions thrown directly in a method and not caught within it | `customRules.missingThrowsTag` |
| [RequireExceptionChainingRule](rules/RequireExceptionChainingRule.md) | Requires new exceptions thrown inside catch blocks to chain the caught exception | `customRules.unchainedRethrow` |
| [ForbidBroadCatchRule](rules/ForbidBroadCatchRule.md) | Forbids catching `Throwable`, `Exception`, and the `LogicException`/`Error` families outside configured boundary paths | `customRules.broadCatch` |
| [ForbidGenericThrowsTagRule](rules/ForbidGenericThrowsTagRule.md) | Forbids `@throws \Exception` and `@throws \Throwable` tags | `customRules.genericThrowsTag` |
| [ForbidInternalMixedTypeRule](rules/ForbidInternalMixedTypeRule.md) | Forbids explicit concrete `mixed` in internal declarations while allowing public, inherited, magic-protocol, and template contracts | `customRules.internalMixedType` |
| [RequireListForArrayLiteralRule](rules/RequireListForArrayLiteralRule.md) | Requires `list<V>` instead of `array<int, V>` when a property or callable visibly owns a non-empty list literal; input parameters remain unrestricted | `customRules.arrayLiteralListType` |
| [RequireExhaustiveDispatchRule](rules/RequireExhaustiveDispatchRule.md) | Requires a `switch` or `match` that names its subject — `match ($suit)`, or `match ($payment::class)` for a sealed hierarchy — to name a branch for every value that subject can hold, the way Rust and Kotlin check a match over a closed type | `customRules.exhaustiveDispatch`, `customRules.exhaustiveDispatchDefault` |

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
| [NoBrokenCodeExpectationRule](rules/NoBrokenCodeExpectationRule.md) `*` | Forbids `expectException()` / `expectExceptionObject()` for `Throwable` and the `LogicException`/`Error` families | `customRules.noBrokenCodeExpectation` |
| [PhpUnitMockApiRule](rules/PhpUnitMockApiRule.md) `*` | Restricts mock API and enforces interface-only mocking | `customRules.phpUnitMockApi` |
| [ForbidDescriptivePhpDocInTestClassRule](rules/ForbidDescriptivePhpDocInTestClassRule.md) | Forbids descriptive PHPDoc text in test classes | `customRules.forbidDescriptivePhpDocInTestClass` |
| [TestNamingConventionRule](rules/TestNamingConventionRule.md) | Enforces naming conventions for test methods and data providers | `customRules.testNamingConvention` |

## Enabling and Disabling Rules

All rules inherit `toolkit.allRules`, which defaults to `true`. Override a
specific rule through its `enabled` value:

```neon
parameters:
    toolkit:
        forbidEmptyCatch:
            enabled: false
```

Set `allRules: false` to make every rule opt-in. Toolkit adoption keeps the
default; changing a switch is an explicit project policy decision.

## Redundant PHPStan Diagnostics

The toolkit suppresses a PHPStan or Strict Rules diagnostic only when an enabled
toolkit rule makes that diagnostic non-actionable. The toolkit error remains as
the single instruction for fixing the prohibited construct:

| Enabled toolkit rule | Suppressed diagnostic | Scope |
|----------------------|-----------------------|-------|
| `noNonPublicMethod` | `method.unused`, `method.finalPrivate`, `consistentConstructor.private` | Private method declarations |
| `noPrivateMethodInTestClass` | The same private-method diagnostics | Restricted test classes |
| `noPropertyInTestClass` | `property.unused`, `property.neverRead`, `property.neverWritten`, `property.onlyRead`, `property.onlyWritten` | Restricted test classes |
| `noClassConstantInTestClass` | `classConstant.unused` | Restricted test classes |
| `noControlFlowInTestMethod` | PHPStan and Strict Rules diagnostics emitted for the prohibited `if`, loop, `switch`, or `match` node | Test methods in restricted test classes |
| `requireThrowsTagOnDirectThrow` | `missingType.checkedException` | Direct escaping throws in methods; propagated checked exceptions remain reported |

This is semantic suppression through PHPStan's extension API, not a global
`ignoreErrors` entry. Disabling the dominating toolkit rule automatically
restores the corresponding PHPStan diagnostics. Independent findings remain
visible; for example, an unused private property in production code and a dead
broad catch are still reported.

## Parameters

The following values can be customized under `parameters.toolkit` in a project's
`phpstan.neon`:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `testNamespacePrefixes` | `['Tests']` | Test namespace prefixes |
| `restrictedTestNamespacePrefixes` | `['Tests\Unit', 'Tests\Integration']` | Test namespaces where strict rules apply |
| `visibilityExemptNamespacePrefixes` | `['Tests']` | Namespace subtrees allowed to reference declarations across `@visibility` boundaries |
| `srcUnitTestPairExcludePatterns` | `[]` | Patterns to exclude from test pair checks |
| `srcMarker` | `'/src/'` | Source code path marker |
| `unitTestMarker` | `'/tests/Unit/'` | Unit test path marker |
| `forbiddenNamespacePrefixes` | `['Tests\Support', 'Tests\Supports', 'Tests\Helper', 'Tests\Helpers', 'Tests\Util', 'Tests\Utils', 'Tests\Utility', 'Tests\Utilities']` | Namespace prefixes to forbid |
| `forbiddenClassLikeNameSuffixes` | See [`rules.neon`](../rules.neon) | Class-like declaration name suffixes to forbid |
| `broadCatchAllowedPaths` | `[]` | fnmatch patterns of boundary files allowed to catch `Throwable`, `Exception`, and the `LogicException`/`Error` families |
