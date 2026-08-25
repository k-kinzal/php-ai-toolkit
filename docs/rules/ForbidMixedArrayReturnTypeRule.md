# ForbidMixedArrayReturnTypeRule

> This is a legacy opt-in rule class and is no longer registered by `toolkit.neon`. The default extension uses [ForbidInternalMixedTypeRule](ForbidInternalMixedTypeRule.md), which permits deliberate public `mixed` contracts and forbids concrete `mixed` in internal declarations instead.

| Property | Value |
|----------|-------|
| Identifier | `customRules.mixedArrayReturnType` |
| Scope | `@return`, `@phpstan-return`, and `@psalm-return` on named functions and methods |
| Configurable | Yes (`mixedArrayReturnAllowedPaths`) |

## What It Detects

Reports a generic `array` or `non-empty-array` whose value type is explicitly `mixed` anywhere in a return declaration.

```php
final class ReportReader
{
    /**
     * @return array<string, mixed> ← ERROR: Replace mixed with the values read() can return.
     */
    public function read(string $path): array
    {
        // ...
    }
}
```

The following forms are equivalent violations:

- `@return array<mixed>` because its omitted key type defaults to `array-key`.
- `@return array<int, mixed>|null` because nullability does not make the array values more specific.
- `@return list<array<string, mixed>>` because the nested array still exposes unknown values.
- `@phpstan-return non-empty-array<string, mixed>` and `@psalm-return array<string, mixed>` because tool-specific tags define the effective static-analysis contract.

### Not reported

- Input declarations such as `@param array<string, mixed> $payload`.
- Property and local declarations such as `@var array<string, mixed>`.
- Specific value unions such as `@return array<string, bool|int|string>`.
- Array shapes such as `@return array{id: int, payload: mixed}`; the rule targets generic array value declarations, not individual shape fields.
- Other iterable forms such as `list<mixed>` and `iterable<string, mixed>`.
- Files matching `mixedArrayReturnAllowedPaths`, reserved for genuine untyped output boundaries.

## Why This Is an Error

A function or method creates or selects its own return value. Unlike an input boundary, it normally knows which values it can produce. Returning `array<K, mixed>` discards that knowledge and forces every caller to rediscover types with runtime checks.

The declaration is also an easy escape hatch for generated code: `mixed` makes almost any implementation type-check while leaving completion, refactoring, exhaustiveness checks, and downstream analysis ineffective.

## How to Fix

### Declare the value union

```php
/**
 * @return array<string, bool|int|string|null>
 */
public function readScalars(string $path): array
```

### Use an array shape for a fixed record

```php
/**
 * @return array{id: positive-int, title: non-empty-string, published: bool}
 */
public function articleSummary(): array
```

### Return a domain object for a larger contract

```php
public function read(string $path): Report
```

If the return value genuinely crosses an untyped runtime boundary, validate and normalize it before returning. That keeps `mixed` at the input point and gives callers a stable typed result.

If the method itself must expose arbitrary values — for example, the lowest-level wrapper around `unserialize()` or an `eval()` variable scope — declare that boundary explicitly:

```neon
parameters:
    customRules:
        mixedArrayReturnAllowedPaths:
            - 'src/Infrastructure/SerializedCache.php'
```

The default is `[]`; ordinary application and domain files remain strict.
