# ForbidInternalMixedTypeRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.internalMixedType` |
| Scope | Resolved PHPStan declarations outside unrestricted public or inherited contracts |
| Configurable | No |

## What It Detects

Reports explicit, concrete `mixed` in a declaration that is internal to the program. The check follows the resolved PHPStan type, so nested occurrences are included:

```php
/**
 * @visibility namespace
 */
final class PayloadDecoder
{
    /** @var array{payload: mixed} */
    private array $payload; // ERROR

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function decode(array $rows): string // ERROR
    {
        // ...
    }
}
```

The rule covers:

- Parameters and returns on scope-restricted classes, methods, and functions.
- Private properties, including promoted properties, even when their constructor is public.
- Properties on a scope-restricted class.
- Closure and arrow-function signatures.
- Explicit local `@var`, `@phpstan-var`, and `@psalm-var` declarations.
- Restricted class-level `@property`, `@method`, `@phpstan-type`, and `@psalm-type` declarations.
- `mixed` nested inside arrays, lists, iterables, callables, unions, and array shapes.

An omitted type remains PHPStan's implicit `mixed` and is handled by PHPStan's ordinary missing-type checks. This rule targets an explicit type decision that discards information.

## Public Boundary

Arbitrary values are valid at an unrestricted public boundary. The rule therefore permits `mixed` automatically when both the PHP API visibility and the effective ScopeGuard visibility expose the declaration:

```php
final class Input
{
    public function normalize(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected a string.');
        }

        return $value;
    }
}
```

No `@visibility` tag and `@visibility public` both mean unrestricted for this rule. Public and protected members are contract surfaces. A private member remains internal, and a member can never be wider than a scope-restricted class containing it.

The public boundary is allowed to accept or return any deliberate `mixed` contract. The architectural expectation is that arbitrary input is validated there and that the internal declarations receiving the result use the narrowed type.

## Inherited Contracts

An implementation is allowed to repeat `mixed` when the corresponding position is imposed by:

- A parent method.
- An implemented interface method.
- An abstract method imported from a trait.

The exception is position-specific. An interface parameter declared as `mixed` permits that implementation parameter; it does not permit an unrelated return type or another method owned by the implementation.

```php
interface Decoder
{
    public function decode(mixed $value): string;
}

/**
 * @visibility namespace
 */
final class JsonDecoder implements Decoder
{
    public function decode(mixed $value): string // allowed by Decoder
    {
        // validate and normalize
    }

    public function remember(mixed $value): void // ERROR: no inherited contract
    {
    }
}
```

This applies equally to vendor, built-in, and same-project public contracts.

## PHP Magic Protocols

Recognized PHP magic methods are language-defined protocol methods and their signatures are permitted on restricted classes. Arbitrary names beginning with `__` are not magic protocols. `__construct`, `__destruct`, and `__clone` continue through the ordinary visibility and inherited-contract checks because they do not impose an arbitrary-value signature by themselves.

## Templates Are Not Mixed

A template represents one type selected by a caller; it is not concrete `mixed`. Native PHP may need an untyped slot to carry it, but PHPStan still knows the slot as `T`:

```php
/**
 * @template T
 * @visibility namespace
 */
final class Box
{
    /** @var T */
    private $value;

    /** @param T $value */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
```

`TemplateMixedType` and template occurrences nested inside another type are consequently not reported.

## How to Fix

Choose the fix that describes the real boundary:

1. Validate the arbitrary value at an unrestricted public method and pass a narrowed union, shape, DTO, or domain object inward.
2. If consumers genuinely need an arbitrary-value contract, expose it as a public interface and implement that contract internally.
3. If the declaration is generic rather than arbitrary, declare a template such as `T` and use `T` at every corresponding position.
4. If the value set is closed, replace `mixed` with the complete union or array shape.

There is no path allowlist. Whether `mixed` is valid is a property of the type contract, not the file containing it.
