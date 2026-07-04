# NoPrivateMethodInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testClassPrivateMethod` |
| Scope | Test classes in restricted namespaces |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports private method declarations in test classes within `Tests\Unit` and `Tests\Integration` namespaces.

```php
namespace Tests\Unit\Service;

final class PaymentServiceTest extends TestCase
{
    public function testCharge(): void
    {
        $gateway = $this->createGatewayStub(willSucceed: true);
        // ...
    }

    public function testChargeFailure(): void
    {
        $gateway = $this->createGatewayStub(willSucceed: false);
        // ...
    }

    // ERROR: Private method createGatewayStub() is prohibited in Tests\Unit and Tests\Integration classes.
    private function createGatewayStub(bool $willSucceed): GatewayInterface
    {
        $stub = $this->createStub(GatewayInterface::class);
        $stub->method('charge')->willReturn($willSucceed ? new Success() : new Failure());
        return $stub;
    }
}
```

## Why This Is an Error

Private helper methods in test classes obscure test intent:

1. **Indirection hides setup**: When a test method delegates its setup to a private helper, the reader must jump between methods to understand what the test actually does. A test should read top-to-bottom as a complete specification.

2. **Growing complexity**: Private helpers tend to accumulate parameters and conditional logic over time, becoming "mini factories" that serve multiple tests with different configurations. This complexity belongs in a dedicated builder or factory class, not in the test class itself.

3. **AI anti-pattern**: AI code generators frequently extract private helpers to reduce apparent duplication in tests. While this looks cleaner, it trades explicitness for abstraction in a context where explicitness matters most.

## How to Fix

### Option 1: Inline the logic

For simple setup, inline the helper directly into each test method:

```php
namespace Tests\Unit\Service;

final class PaymentServiceTest extends TestCase
{
    public function testCharge(): void
    {
        $gateway = $this->createStub(GatewayInterface::class);
        $gateway->method('charge')->willReturn(new Success());

        $service = new PaymentService($gateway);
        $result = $service->charge(1000);

        self::assertTrue($result->isSuccessful());
    }

    public function testChargeFailure(): void
    {
        $gateway = $this->createStub(GatewayInterface::class);
        $gateway->method('charge')->willReturn(new Failure());

        $service = new PaymentService($gateway);
        $result = $service->charge(1000);

        self::assertFalse($result->isSuccessful());
    }
}
```

### Option 2: Use a real library boundary

For complex setup logic that is genuinely reused, use an existing library or create an independent internal library outside the `Tests` namespace:

```php
namespace Acme\TestingFixtures\Payment;

final class GatewayStub
{
    public static function succeeding(TestCase $test): GatewayInterface
    {
        $stub = $test->createStub(GatewayInterface::class);
        $stub->method('charge')->willReturn(new Success());
        return $stub;
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
