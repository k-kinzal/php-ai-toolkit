# NoTraitUseInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testClassTraitUse` |
| Scope | Test classes in restricted namespaces |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports `use` statements for traits inside test classes within `Tests\Unit` and `Tests\Integration` namespaces.

```php
namespace Tests\Unit\Service;

final class OrderServiceTest extends TestCase
{
    // ERROR: Move trait CreatesOrderTrait behavior to a dedicated collaborator and call it explicitly.
    use CreatesOrderTrait;

    public function testPlaceOrder(): void
    {
        $order = $this->createOrder();
        // ...
    }
}
```

```php
trait CreatesOrderTrait
{
    private OrderFactory $factory; // introduces a property
    private const DEFAULT_QUANTITY = 1; // introduces a constant

    private function createOrder(): Order // introduces a private method
    {
        return $this->factory->create(self::DEFAULT_QUANTITY);
    }
}
```

## Why This Is an Error

This extension enforces several restrictions on test classes: no properties, no class constants, no private methods, and no helper methods. Traits provide a mechanism to bypass all of these restrictions by injecting prohibited members indirectly.

A trait used in a test class can introduce:

- **Properties** that create shared mutable state between test methods
- **Class constants** that encourage fixture coupling
- **Private methods** that hide test setup logic
- **Helper methods** that add indirection

Even if a trait only contains "harmless" utility methods today, it can grow over time to include prohibited members without any rule catching the violation, since the members are defined in the trait file rather than the test class.

## How to Fix

### Option 1: Inline the trait logic into each test method

```php
final class OrderServiceTest extends TestCase
{
    public function testPlaceOrder(): void
    {
        $factory = new OrderFactory();
        $order = $factory->create(quantity: 1);

        self::assertSame(1, $order->quantity());
    }
}
```

### Option 2: Use a real library boundary

For genuinely reusable setup logic, use an existing library or create an independent internal library outside the `Tests` namespace:

```php
namespace Acme\TestingFixtures\Order;

final class ExampleOrder
{
    public static function default(): Order
    {
        return (new OrderFactory())->create(quantity: 1);
    }

    public static function withQuantity(int $quantity): Order
    {
        return (new OrderFactory())->create(quantity: $quantity);
    }
}
```

```php
// tests/Unit/Service/OrderServiceTest.php
final class OrderServiceTest extends TestCase
{
    public function testPlaceOrder(): void
    {
        $order = ExampleOrder::default();

        self::assertSame(1, $order->quantity());
    }

    public function testPlaceBulkOrder(): void
    {
        $order = ExampleOrder::withQuantity(100);

        self::assertSame(100, $order->quantity());
    }
}
```

## Configuration

Customize which namespaces are considered restricted:

```neon
parameters:
    customRules:
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'
```
