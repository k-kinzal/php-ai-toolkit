# RequireListForArrayLiteralRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.arrayLiteralListType` |
| Scope | Property `@var` declarations and function or method return declarations backed by non-empty list literals |
| Configurable | No |

## What It Detects

Reports `array<int, V>` when the declaration describes a visibly owned, non-empty list literal.

```php
final class Labels
{
    /** @var array<int, string> */ // ERROR: use list<string>
    public array $values = ['foo', 'bar'];

    /**
     * @return array<int, string> // ERROR: use list<string>
     */
    public function all(): array
    {
        return ['foo', 'bar'];
    }
}
```

The rule checks `@var`, `@phpstan-var`, and `@psalm-var` on properties, and `@return`, `@phpstan-return`, and `@psalm-return` on named functions and methods. A nullable or union return branch is also checked:

```php
/**
 * @return array<int, non-empty-string>|null
 */
public function findLabels(bool $found): ?array
{
    if (!$found) {
        return null;
    }

    return ['foo', 'bar'];
}
```

Only a direct `array<int, V>` branch is replaced. For example, `array<int, array<string, bool>>` becomes `list<array<string, bool>>`; an `array<int, V>` nested inside the value type of another array is not inferred to be a list.

### Not reported

- `@param array<int, V>`. Inputs can legitimately accept arbitrary or sparse integer keys.
- A property initialized only with `[]`, or a callable that returns only `[]`. An empty array does not prove whether later values use list or sparse integer keys.
- Explicitly keyed literals such as `[2 => 'foo']` and associative literals such as `['name' => 'foo']`.
- A callable that also returns an uncertain expression such as `$values`, or another non-list array literal. Tightening its whole contract could reject a valid return path.
- Existing `list<V>` declarations and arrays with a key type other than exactly `int`.
- Returns inside nested closures, functions, or anonymous classes; they do not belong to the enclosing callable.

## Why This Is an Error

`array<int, V>` says only that every key is an integer. It permits gaps, negative keys, and arbitrary starting indexes. A literal such as `['foo', 'bar']` guarantees more: its keys are contiguous and start at zero. Writing `list<V>` preserves that fact for callers and lets PHPStan detect operations that would break it.

This distinction is especially useful for generated code. `array<int, V>` is an easy, superficially compatible fallback, but it discards a property of the value that is already visible in the implementation.

## How to Fix

Replace the reported generic array branch with the suggested list type:

```php
final class Labels
{
    /** @var list<string> */
    public array $values = ['foo', 'bar'];

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return ['foo', 'bar'];
    }
}
```

If integer keys are intentionally sparse or externally assigned, keep `array<int, V>` and make that intent visible in the implementation rather than representing the value as an implicit-key list literal.
