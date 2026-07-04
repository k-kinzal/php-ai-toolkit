# ForbidClassLikeNameSuffixRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenClassLikeNameSuffix` |
| Scope | Class, interface, trait, and enum declaration names |
| Configurable | Yes |

## Configuration

The default suffix list is defined in [`extension.neon`](../../extension.neon). You can override the list under `customRules.forbiddenClassLikeNameSuffixes`:

```neon
parameters:
    customRules:
        forbiddenClassLikeNameSuffixes:
            - Helper
            - Manager
            - Service
```

## What It Detects

Reports class-like declarations whose short name ends with a configured suffix:

```php
class UserHelper
{
}

interface PaymentManager
{
}

trait RequestData
{
}

enum StatusHelper
{
    case Active;
}
```

### Not Reported

- Method names
- Function names
- Property names
- Constant names
- Variable names
- Parameter names
- Anonymous classes
- Names that only contain the configured word somewhere other than the end

## Matching Rules

Suffix matching is case-sensitive and uses the short declaration name, not the fully-qualified class name.

For example, with `Helper` configured:

```php
namespace App\Helper;

class UserReader
{
}
```

`UserReader` is allowed because the short class name does not end with `Helper`.

## Why This Is an Error

Generic suffixes such as `Helper`, `Manager`, and `Data` often hide the real responsibility of a type. They make it harder for humans and AI agents to infer the correct boundary, dependency direction, and behavior from the type name.

A class-like name should describe the domain role or technical contract directly.

## How to Fix

Rename the class, interface, trait, or enum so that its short name does not end with the configured suffix:

```php
// Bad
final class UserHelper
{
}

// Good
final class UserNameNormalizer
{
}
```
