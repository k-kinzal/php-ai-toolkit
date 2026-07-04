# ForbiddenNamespaceRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenNamespace` |
| Scope | Namespace declarations |
| Configurable | Yes |

## Configuration

The default forbidden namespace prefixes are defined in [`extension.neon`](../../extension.neon). You can override the list under `customRules.forbiddenNamespacePrefixes`:

```neon
parameters:
    customRules:
        forbiddenNamespacePrefixes:
            - 'Tests\Support'
            - 'Tests\Supports'
            - 'Tests\Helper'
            - 'Tests\Helpers'
            - 'Tests\Util'
            - 'Tests\Utils'
            - 'Tests\Utility'
            - 'Tests\Utilities'
```

## What It Detects

Reports namespace declarations that exactly match a configured prefix or live under it:

```php
namespace Tests\Support;

final class UserFixture
{
}
```

```php
namespace Tests\Helpers\Database;

final class DatabaseFixture
{
}
```

### Not Reported

- Namespaces that only share a partial prefix, such as `Tests\Supporting`
- Namespaces outside the configured prefixes
- The global namespace

## Matching Rules

Matching is case-sensitive. A namespace is reported only when it is an exact match or a child namespace.

For example, with `Tests\Support` configured:

```php
namespace Tests\Support;
```

is reported, and:

```php
namespace Tests\Support\Database;
```

is also reported. This namespace is allowed because it is only a partial text match:

```php
namespace Tests\Supporting;
```

## Why This Is an Error

Generic test support, helper, and utility namespaces become dumping grounds for reusable setup code. They hide what each test actually needs, encourage cross-test coupling, and create a second test framework that AI agents tend to expand instead of simplifying.

Tests should prefer explicit setup in the test method. When reuse is genuinely valuable, it should be provided by a real dependency with a clear API boundary, not by a generic `Tests\Support`, `Tests\Helper`, or `Tests\Util` bucket.

## How to Fix

Use an existing library when the behavior is already solved by a maintained dependency.

For reusable project-specific behavior, create an independent internal library outside the `Tests` namespace. Give it a domain-specific namespace and API so it can be analyzed and maintained like production support code.

For simple setup, accept duplication and write the setup directly inside each test method:

```php
final class UserServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $user = new User('Alice', 'alice@example.com', 30);
        $service = new UserService();

        $service->create($user);

        self::assertNotEmpty($user->name());
    }
}
```
