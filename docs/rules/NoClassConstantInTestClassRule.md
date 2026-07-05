# NoClassConstantInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testClassConstant` |
| Scope | Test classes in restricted namespaces |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports class constant declarations in test classes within `Tests\Unit` and `Tests\Integration` namespaces.

```php
namespace Tests\Unit\Service;

final class OrderServiceTest extends TestCase
{
    // ERROR: Inline class constant DEFAULT_QUANTITY inside the test methods that use it.
    private const DEFAULT_QUANTITY = 5;
    // ERROR: Inline class constant CUSTOMER_EMAIL inside the test methods that use it.
    private const CUSTOMER_EMAIL = 'test@example.com';

    public function testPlaceOrder(): void
    {
        $order = Order::create(self::CUSTOMER_EMAIL, self::DEFAULT_QUANTITY);
        // ...
    }

    public function testPlaceOrderWithDiscount(): void
    {
        $order = Order::create(self::CUSTOMER_EMAIL, self::DEFAULT_QUANTITY);
        // ...
    }
}
```

## Why This Is an Error

Class constants in test classes encourage shared fixture values across multiple test methods. This creates several problems:

1. **Fixture coupling**: When multiple tests share the same constant value, changing that value can break unrelated tests. Each test should define the exact values it needs.

2. **Hidden meaning**: A constant like `DEFAULT_QUANTITY = 5` obscures why that particular value matters for a given test. Inline values make the test's intent explicit: "this test needs a quantity of 5 because..."

3. **False DRY**: Deduplicating test data via constants is a misapplication of DRY. Test data is not logic — it is specification. Each test should specify its own input independently.

## How to Fix

Replace constants with inline literal values in each test method:

```php
namespace Tests\Unit\Service;

final class OrderServiceTest extends TestCase
{
    public function testPlaceOrder(): void
    {
        $order = Order::create('alice@example.com', 3);

        self::assertSame('alice@example.com', $order->customerEmail());
        self::assertSame(3, $order->quantity());
    }

    public function testPlaceOrderWithDiscount(): void
    {
        $order = Order::create('bob@example.com', 10);
        $order->applyDiscount(15);

        self::assertSame(850, $order->totalCents());
    }
}
```

Each test now clearly shows what values it uses and why. If a test needs a specific quantity to trigger a discount threshold, the inline value `10` makes that relationship visible.

## Configuration

Customize which namespaces are considered restricted:

```neon
parameters:
    customRules:
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'
```
