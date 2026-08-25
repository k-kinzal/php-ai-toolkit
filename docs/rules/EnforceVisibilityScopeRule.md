# EnforceVisibilityScopeRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.visibilityInvalidScope`, `customRules.visibilityOutOfScope` |
| Scope | Class-like declarations, members, and written class-like references in PHPStan's analysed files |
| Configurable | Yes |

## Purpose

PHP exports every namespace declaration to the whole program. `@visibility` adds the namespace scopes that PHP's
language visibility cannot express, corresponding to Rust's `pub(crate)`, `pub(super)`, and `pub(in path)`. PHPStan
collects declarations and written references from the files it already analyses, then reports a reference made from
outside the declaration's scope.

```php
namespace App\Billing\Ledger;

/**
 * Appends entries to the ledger file.
 *
 * @visibility parent
 */
final class LedgerWriter
{
}
```

## Scope Values

| Value | Visible from |
|-------|--------------|
| `public` | Everywhere. This is equivalent to no tag for access, but explicitly declares public API intent. |
| `root` | The outermost segment of the declaring namespace and its descendants. |
| `parent` | The declaring namespace's parent and its descendants. |
| `namespace` | The declaring namespace and its descendants. |
| `App\Billing` | The named namespace and its descendants, plus the declaring namespace. |

Several tags form a union. A declaration carrying `@visibility namespace` and `@visibility App\Console` is visible
from both subtrees. A single lowercase namespace name must have a leading backslash: `@visibility \legacy`. Without
it, the value has the shape of a misspelled keyword and is reported.

The tag may be attached to classes, interfaces, traits, enums, methods, properties, class constants, and enum cases.
A member cannot widen its containing type: both a member scope and its class-like scope must admit the reference.

## References Checked

The rule deliberately resolves written names, preserving the former standalone check's module-boundary semantics:

- instantiation, static calls, static property access, constants, enum cases, and `::class`;
- `instanceof`, `extends`, `implements`, and trait use;
- parameter, return, property, and catch types;
- attributes.

`self`, `static`, and `parent` are not reported. Member scopes are enforced for named static access and constructors.
An instance call such as `$writer->append()` is not a written class name and is not resolved by this rule. Inherited
member lookups follow parents, interfaces, and traits collected from the analysed files.

## Invalid Scopes

`customRules.visibilityInvalidScope` reports a value that cannot narrow access, including:

- an unknown lowercase keyword such as `parrent`;
- invalid namespace syntax;
- `parent` where the parent is the global namespace;
- a scope keyword on a declaration in the global namespace;
- `public` combined with any narrowing tag.

An unusable tag is reported once at its declaration. It is not silently treated as a restriction, which would create
an error at every reference without a scope anyone could satisfy.

`customRules.visibilityOutOfScope` reports the written reference. Its message names the symbol, allowed namespaces,
reference kind, and narrowest scope that would admit the caller.

## Configuration

The rule is part of `rules.neon` and follows `toolkit.allRules`. Disable only this rule with:

```neon
parameters:
    toolkit:
        visibilityScope:
            enabled: false
```

PHPStan's `paths` and `excludePaths` define both sides of the check. A declaration outside those paths is not indexed,
and a reference outside them is not collected. No separate scanner configuration is needed.

Disabling `visibilityScope` stops only the boundary rule and its collectors. The unused-symbol extensions remain
active because `@visibility public` is independent public-API metadata also consumed by other toolkit rules.

Namespaces that may cross all scopes are configured under `visibilityExemptNamespacePrefixes`. `Tests` is exempt by
default because test code commonly exercises namespace-scoped implementation details:

```neon
parameters:
    toolkit:
        visibilityExemptNamespacePrefixes:
            - Tests
            - App\Generated
```

Invalid tags are still reported on declarations inside exempt namespaces.

## Public API and Unused Findings

`@visibility public` declares that callers outside the analysed repository are expected. Such a declaration may
correctly have no local references, so the toolkit registers PHPStan always-used extensions for methods, properties,
and class constants. It also registers an ignore-error extension that suppresses declaration-unused identifiers,
including `unused` and `dead` families emitted by compatible PHPStan extensions, only when the reported declaration
line itself carries `@visibility public`.

This is semantic suppression in PHPStan's extension layer. Source-level `@phpstan-ignore` comments and baseline
entries are neither required nor generated. Unrelated errors inside the declaration remain visible.

## Fixing a Violation

Prefer moving the reference into the owning namespace or adding a public front door. Widen the tag only when the
ownership boundary is genuinely broader. Removing the tag removes the declared boundary and is not a fix.
