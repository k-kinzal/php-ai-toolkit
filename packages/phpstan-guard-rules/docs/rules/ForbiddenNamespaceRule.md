# ForbiddenNamespaceRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenNamespace` |
| Scope | Namespace declarations |
| Rector | Unavailable |
| Configurable | Yes |

## Configuration

The defaults are defined in [`rules.neon`](../../rules.neon). `toolkit.forbiddenNamespacePrefixes` matches from the start of a namespace. `toolkit.forbiddenNamespaceSegments` matches a complete name between backslashes at any depth. The default configuration is:

```neon
parameters:
    toolkit:
        forbiddenNamespacePrefixes:
            - 'Tests\Support'
            - 'Tests\Supports'
            - 'Tests\Helper'
            - 'Tests\Helpers'
            - 'Tests\Util'
            - 'Tests\Utils'
            - 'Tests\Utility'
            - 'Tests\Utilities'
            - 'Tests\Fixture'
            - 'Tests\Fixtures'
        forbiddenNamespaceSegments:
            - Model
            - Models
            - Infrastructure
            - Infrastructures
```

## What It Detects

Reports namespace declarations that exactly match a configured prefix, live under it, or contain a forbidden segment:

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

The default forbidden segments apply at every depth, including the root namespace:

```php
namespace Model;
namespace App\Models;
namespace App\Infrastructure;
namespace Acme\Billing\Infrastructures\Database;
```

Only namespace declarations are checked. Imports and references to classes in these namespaces are not reported by this rule.

### Not Reported

- Namespaces that only share a partial prefix, such as `Tests\Supporting`
- Partial segment matches, such as `App\ViewModel`, `App\Modeling`, or `App\InfrastructureFactory`
- Namespaces outside the configured prefixes and segments
- The global namespace

## Matching Rules

Both matching modes are case-sensitive. Prefix matching reports an exact match or a child namespace. Segment matching splits the namespace at each backslash and compares whole segments; `App\Model\User` matches `Model`, while `App\ViewModel` and `App\model` do not.

When several restrictions match one declaration, the rule reports one error. A matching prefix takes precedence; otherwise, the first forbidden segment in the namespace is reported. The identifier remains `customRules.forbiddenNamespace`.

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

`Model`, `Models`, `Infrastructure`, and `Infrastructures` describe broad technical categories without naming the responsibility of the code they contain. The default policy requires names that identify that responsibility, such as `Billing`, `Catalog`, or `Persistence`.

Generic test support, helper, utility, and fixture namespaces become dumping grounds for reusable setup code. They hide what each test actually needs, encourage cross-test coupling, and create a second test framework that AI agents tend to expand instead of simplifying.

Tests should prefer explicit setup in the test method. When reuse is genuinely valuable, it should be provided by a real dependency with a clear API boundary, not by a generic `Tests\Support`, `Tests\Helper`, `Tests\Util`, or `Tests\Fixture` bucket.

## How to Fix

For a forbidden segment, choose a name that describes the code's responsibility. For example, move `App\Models\Invoice` to `App\Billing\Invoice`. Update namespace declarations, references, and the corresponding file paths or autoload mappings.

For forbidden test support prefixes:

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

## Rector

**Unavailable** — no automatic transformation is provided.

A replacement namespace and all external references, autoload mappings, and configuration must be chosen together.
