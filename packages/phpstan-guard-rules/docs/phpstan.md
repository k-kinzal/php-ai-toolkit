# PHPStan Rules

Custom PHPStan rules provided by php-ai-toolkit. Each rule detects anti-patterns that actually occur in AI-generated code.

## Rector Support

Every rule below is classified as **Safe**, **Risky**, or **Unavailable** for automatic
changes. Enable the safe group with `withRules(SafeRules::rules())` or the risky
group with `withRules(RiskyRules::rules())` in `rector.php`. Both classes are in
`Toolkit\Rector`; their lists can be combined, and individual Rector classes can
also be registered with `withRules()`.
A classification describes only the supported cases; each rule’s Rector section
lists skipped cases and review requirements.

The legacy opt-in [ForbidMixedArrayReturnTypeRule](rules/ForbidMixedArrayReturnTypeRule.md#rector)
is also classified as **Unavailable**. Internal helper rules for mixed types and
class dispatch share their parent rule’s classification.

## General

Rules applied to all code.

| Rule | Description | Error Identifier | Rector |
|------|-------------|------------------|--------|
| [ForbiddenCommentRule](rules/ForbiddenCommentRule.md) | Forbids `@phpstan-ignore` and `@infection-ignore-all` comments | `customRules.phpstanIgnoreComment`, `customRules.infectionIgnoreAllComment` | [Risky](rules/ForbiddenCommentRule.md#rector) |
| [ForbidFileTermRule](rules/ForbidFileTermRule.md) | Forbids configured literal terms in files matching restricted path patterns | `customRules.forbiddenFileTerm` | [Unavailable](rules/ForbidFileTermRule.md#rector) |
| [ForbidDependencyRule](rules/ForbidDependencyRule.md) | Forbids symbol and file-reading dependencies between configured paths; allows each root directory to depend only on itself and src by default | `customRules.forbiddenDependency` | [Unavailable](rules/ForbidDependencyRule.md#rector) |
| [ForbiddenMagicMethodCallRule](rules/ForbiddenMagicMethodCallRule.md) | Forbids direct calls to magic methods | `customRules.forbiddenMagicMethodCall` | [Risky](rules/ForbiddenMagicMethodCallRule.md#rector) |
| [ForbiddenNamespaceRule](rules/ForbiddenNamespaceRule.md) | Forbids configured namespace prefixes such as `Tests\Support`, `Tests\Helper`, `Tests\Util`, and `Tests\Fixture` | `customRules.forbiddenNamespace` | [Unavailable](rules/ForbiddenNamespaceRule.md#rector) |
| [OverrideMustHaveAttributeRule](rules/OverrideMustHaveAttributeRule.md) | Requires `#[Override]` attribute on overridden methods | `customRules.overrideMustHaveAttribute` | [Safe](rules/OverrideMustHaveAttributeRule.md#rector) |
| [SrcUnitTestPairRule](rules/SrcUnitTestPairRule.md) | Enforces 1:1 pairing between `src/` classes and `tests/Unit/` test classes | `customRules.srcUnitTestPair` | [Unavailable](rules/SrcUnitTestPairRule.md#rector) |
| [RequirePhpDocOnPublicApiRule](rules/RequirePhpDocOnPublicApiRule.md) | Requires PHPDoc on all public API elements | `customRules.requirePhpDocOnPublicApi` | [Unavailable](rules/RequirePhpDocOnPublicApiRule.md#rector) |
| [RequireExampleOnPublicApiRule](rules/RequireExampleOnPublicApiRule.md) | Requires a runnable `@example` on declarations marked `@visibility public` | `customRules.requireExampleOnClass`, `customRules.requireExampleOnMethod`, `customRules.requireExampleOnProperty`, `customRules.requireExampleOnConstant`, `customRules.requireExampleOnEnumCase` | [Unavailable](rules/RequireExampleOnPublicApiRule.md#rector) |
| [EnforceVisibilityScopeRule](rules/EnforceVisibilityScopeRule.md) | Enforces namespace visibility scopes declared with `@visibility` and treats explicitly public API as externally used | `customRules.visibilityInvalidScope`, `customRules.visibilityOutOfScope` | [Unavailable](rules/EnforceVisibilityScopeRule.md#rector) |
| [EnforceMutationContractRule](rules/EnforceMutationContractRule.md) | Makes parameter, receiver, and global mutation explicit and propagates effects through statically resolved calls | `customRules.mutationInvalidContract`, `customRules.mutationUndeclared`, `customRules.mutationOverrideWidened` | [Unavailable](rules/EnforceMutationContractRule.md#rector) |
| [ForbidNonDocCommentRule](rules/ForbidNonDocCommentRule.md) | Forbids `/* */` and `#` comments everywhere, and `//` comments outside `catch` blocks and array literals; `/** */` PHPDoc is allowed | `customRules.forbidNonDocComment` | [Risky](rules/ForbidNonDocCommentRule.md#rector) |
| [ForbidSingleLinePhpDocRule](rules/ForbidSingleLinePhpDocRule.md) | Forbids single-line PHPDoc on public elements; requires multi-line format | `customRules.forbidSingleLinePhpDoc` | [Safe](rules/ForbidSingleLinePhpDocRule.md#rector) |
| [ForbidClassLikeNameSuffixRule](rules/ForbidClassLikeNameSuffixRule.md) | Forbids configured suffixes on class, interface, trait, and enum names | `customRules.forbiddenClassLikeNameSuffix` | [Unavailable](rules/ForbidClassLikeNameSuffixRule.md#rector) |
| [NoNonPublicMethodRule](rules/NoNonPublicMethodRule.md) | Forbids private methods and forbids protected methods outside abstract classes, traits, and override methods; constructors are exempt | `customRules.nonPublicMethod` | [Unavailable](rules/NoNonPublicMethodRule.md#rector) |
| [ForbidEmptyCatchRule](rules/ForbidEmptyCatchRule.md) | Forbids catch blocks with an empty body | `customRules.emptyCatch` | [Risky](rules/ForbidEmptyCatchRule.md#rector) |
| [RequireThrowsTagOnDirectThrowRule](rules/RequireThrowsTagOnDirectThrowRule.md) | Requires `@throws` for exceptions thrown directly in a method and not caught within it | `customRules.missingThrowsTag` | [Risky](rules/RequireThrowsTagOnDirectThrowRule.md#rector) |
| [RequireExceptionChainingRule](rules/RequireExceptionChainingRule.md) | Requires new exceptions thrown inside catch blocks to chain the caught exception | `customRules.unchainedRethrow` | [Risky](rules/RequireExceptionChainingRule.md#rector) |
| [ForbidBroadCatchRule](rules/ForbidBroadCatchRule.md) | Forbids catching `Throwable`, `Exception`, and the `LogicException`/`Error` families outside configured boundary paths | `customRules.broadCatch` | [Unavailable](rules/ForbidBroadCatchRule.md#rector) |
| [ForbidGenericThrowsTagRule](rules/ForbidGenericThrowsTagRule.md) | Forbids `@throws \Exception` and `@throws \Throwable` tags | `customRules.genericThrowsTag` | [Unavailable](rules/ForbidGenericThrowsTagRule.md#rector) |
| [ForbidInternalMixedTypeRule](rules/ForbidInternalMixedTypeRule.md) | Forbids explicit concrete `mixed` in internal declarations while allowing public, inherited, magic-protocol, and template contracts | `customRules.internalMixedType` | [Unavailable](rules/ForbidInternalMixedTypeRule.md#rector) |
| [RequireListForArrayLiteralRule](rules/RequireListForArrayLiteralRule.md) | Requires `list<V>` instead of `array<int, V>` when a property or callable visibly owns a non-empty list literal; input parameters remain unrestricted | `customRules.arrayLiteralListType` | [Risky](rules/RequireListForArrayLiteralRule.md#rector) |
| [RequireExhaustiveDispatchRule](rules/RequireExhaustiveDispatchRule.md) | Requires a `switch` or `match` that names its subject — `match ($suit)`, or `match ($payment::class)` for a sealed hierarchy — to name a branch for every value that subject can hold, the way Rust and Kotlin check a match over a closed type | `customRules.exhaustiveDispatch`, `customRules.exhaustiveDispatchDefault` | [Unavailable](rules/RequireExhaustiveDispatchRule.md#rector) |

## Test Class

Rules applied to test classes in the `Tests\Unit` / `Tests\Integration` namespaces. Rules marked with `*` apply to all `Tests\` namespaces.

| Rule | Description | Error Identifier | Rector |
|------|-------------|------------------|--------|
| [NoPropertyInTestClassRule](rules/NoPropertyInTestClassRule.md) | Forbids property declarations in test classes | `customRules.noPropertyInTestClass` | [Unavailable](rules/NoPropertyInTestClassRule.md#rector) |
| [NoClassConstantInTestClassRule](rules/NoClassConstantInTestClassRule.md) | Forbids class constants in test classes | `customRules.noClassConstantInTestClass` | [Unavailable](rules/NoClassConstantInTestClassRule.md#rector) |
| [NoPrivateMethodInTestClassRule](rules/NoPrivateMethodInTestClassRule.md) | Forbids private methods in test classes | `customRules.noPrivateMethodInTestClass` | [Unavailable](rules/NoPrivateMethodInTestClassRule.md#rector) |
| [NoHelperMethodInTestClassRule](rules/NoHelperMethodInTestClassRule.md) | Forbids methods other than test/provider/override | `customRules.noHelperMethodInTestClass` | [Unavailable](rules/NoHelperMethodInTestClassRule.md#rector) |
| [NoControlFlowInTestMethodRule](rules/NoControlFlowInTestMethodRule.md) | Forbids control flow statements in test methods | `customRules.noControlFlowInTestMethod` | [Unavailable](rules/NoControlFlowInTestMethodRule.md#rector) |
| [NoTraitUseInTestClassRule](rules/NoTraitUseInTestClassRule.md) | Forbids trait usage in test classes | `customRules.noTraitUseInTestClass` | [Unavailable](rules/NoTraitUseInTestClassRule.md#rector) |
| [NoReflectionInTestClassRule](rules/NoReflectionInTestClassRule.md) `*` | Forbids Reflection API usage in test classes | `customRules.noReflectionInTestClass` | [Unavailable](rules/NoReflectionInTestClassRule.md#rector) |
| [NoRedundantAssertInstanceOfRule](rules/NoRedundantAssertInstanceOfRule.md) `*` | Forbids redundant PHPUnit `assertInstanceOf()` calls for values with one statically-known type | `customRules.noRedundantAssertInstanceOf` | [Unavailable](rules/NoRedundantAssertInstanceOfRule.md#rector) |
| [NoBrokenCodeExpectationRule](rules/NoBrokenCodeExpectationRule.md) `*` | Forbids `expectException()` / `expectExceptionObject()` for `Throwable` and the `LogicException`/`Error` families | `customRules.noBrokenCodeExpectation` | [Unavailable](rules/NoBrokenCodeExpectationRule.md#rector) |
| [PhpUnitMockApiRule](rules/PhpUnitMockApiRule.md) `*` | Restricts mock API and enforces interface-only mocking | `customRules.phpUnitMockApi` | [Unavailable](rules/PhpUnitMockApiRule.md#rector) |
| [ForbidDescriptivePhpDocInTestClassRule](rules/ForbidDescriptivePhpDocInTestClassRule.md) | Forbids descriptive PHPDoc text in test classes | `customRules.forbidDescriptivePhpDocInTestClass` | [Risky](rules/ForbidDescriptivePhpDocInTestClassRule.md#rector) |
| [TestNamingConventionRule](rules/TestNamingConventionRule.md) | Enforces naming conventions for test methods and data providers | `customRules.testNamingConvention` | [Unavailable](rules/TestNamingConventionRule.md#rector) |

## Enabling and Disabling Rules

Rules inherit `toolkit.allRules`, which defaults to `true`, except mutation
contracts, which are opt-in so an existing unannotated project does not change
semantics on upgrade. Override a specific rule through its `enabled` value:

```neon
parameters:
    toolkit:
        forbidEmptyCatch:
            enabled: false
```

Set `allRules: false` to make every rule opt-in. Toolkit adoption keeps the
default; changing a switch is an explicit project policy decision.

Enable mutation contracts explicitly with `toolkit.mutation.enabled: true`.

## Redundant PHPStan Diagnostics

The toolkit suppresses a PHPStan or Strict Rules diagnostic only when an enabled
toolkit rule makes that diagnostic non-actionable. The toolkit error remains as
the single instruction for fixing the prohibited construct:

| Enabled toolkit rule | Suppressed diagnostic | Scope |
|----------------------|-----------------------|-------|
| `noNonPublicMethod` | `method.unused`, `method.finalPrivate` | Private method declarations; PHPStan does not emit either for constructors, and `consistentConstructor.private` stays visible because the rule exempts constructors |
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
| `forbiddenTermsByPath` | `[]` | Map of restricted file path patterns to case-insensitive literal terms forbidden anywhere in matching files |
| `dependencyProjectRoot` | `%currentWorkingDirectory%` | Absolute root for dependency boundary patterns |
| `forbiddenDependencies` | Root directories may depend on themselves and `src/**`; `vendor/**` and root files are exempt | Directed `from`/`to` path policies with optional `excludeFrom`, `excludeTo`, and `allowSameRootDirectory` |
| `dependencyFileReaders` | `[]` | Additional function or `Class::method` file readers with argument `position` and `name`; built-ins remain active |
| `forbiddenNamespacePrefixes` | `['Tests\Support', 'Tests\Supports', 'Tests\Helper', 'Tests\Helpers', 'Tests\Util', 'Tests\Utils', 'Tests\Utility', 'Tests\Utilities', 'Tests\Fixture', 'Tests\Fixtures']` | Namespace prefixes to forbid |
| `forbiddenClassLikeNameSuffixes` | See [`rules.neon`](../rules.neon) | Class-like declaration name suffixes to forbid |
| `broadCatchAllowedPaths` | `[]` | fnmatch patterns of boundary files allowed to catch `Throwable`, `Exception`, and the `LogicException`/`Error` families |
