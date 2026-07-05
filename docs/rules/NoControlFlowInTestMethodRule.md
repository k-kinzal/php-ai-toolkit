# NoControlFlowInTestMethodRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testMethodControlFlow` |
| Scope | Test methods in restricted test classes |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports control flow statements inside test methods in `Tests\Unit` and `Tests\Integration` classes. The following statements are flagged:

- `if` / `else` / `elseif`
- `for`
- `foreach`
- `while`
- `do-while`
- `switch`
- `match`

```php
namespace Tests\Unit\Service;

final class ParserTest extends TestCase
{
    public function testParsesAllFormats(): void
    {
        $formats = ['json', 'xml', 'yaml'];

        // ERROR: Split test method testParsesAllFormats() so it contains no "foreach" statement.
        foreach ($formats as $format) {
            $result = Parser::parse($format, '...');
            self::assertNotNull($result);
        }
    }

    public function testConditionalBehavior(): void
    {
        $result = Calculator::compute(42);

        // ERROR: Split test method testConditionalBehavior() so it contains no "if" statement.
        if ($result > 100) {
            self::assertGreaterThan(100, $result);
        } else {
            self::assertLessThanOrEqual(100, $result);
        }
    }
}
```

### Not reported

Control flow inside closures, arrow functions, and anonymous classes is excluded, since these define a nested scope:

```php
public function testCallbackFiltering(): void
{
    $items = [1, 2, 3, 4, 5];

    // OK: foreach is inside a closure
    $result = array_filter($items, function (int $item): bool {
        if ($item > 3) {
            return true;
        }
        return false;
    });

    self::assertCount(2, $result);
}
```

## Why This Is an Error

Control flow in a test method is a strong signal that the test is doing too much:

1. **Multiple scenarios in one test**: A `foreach` loop testing multiple inputs means the test is actually N tests fused into one. When one iteration fails, the rest are skipped, hiding additional failures.

2. **Conditional assertions are meaningless**: An `if/else` around assertions means the test accepts different outcomes depending on runtime state. A good test has exactly one expected outcome.

3. **Hard to diagnose failures**: When a loop-based test fails at iteration 47, the failure message gives no context about which input caused the failure or why.

4. **AI anti-pattern**: AI generators often write loop-based tests to appear thorough, producing tests that iterate over cases rather than specifying each one precisely.

## How to Fix

### Replace loops with data providers

```php
final class ParserTest extends TestCase
{
    public static function providerFormats(): array
    {
        return [
            'json' => ['json', '{"key": "value"}'],
            'xml'  => ['xml', '<root><key>value</key></root>'],
            'yaml' => ['yaml', "key: value\n"],
        ];
    }

    #[DataProvider('providerFormats')]
    public function testParsesFormat(string $format, string $input): void
    {
        $result = Parser::parse($format, $input);

        self::assertNotNull($result);
        self::assertSame('value', $result->get('key'));
    }
}
```

### Replace conditionals with separate test methods

```php
// Bad
public function testCompute(): void
{
    $result = Calculator::compute(42);
    if ($result > 100) { ... } else { ... }
}

// Good: separate test per scenario
public function testComputeWithSmallInput(): void
{
    $result = Calculator::compute(42);
    self::assertSame(84, $result);
}

public function testComputeWithLargeInput(): void
{
    $result = Calculator::compute(9999);
    self::assertSame(19998, $result);
}
```

### Replace match/switch with data providers

```php
// Bad
public function testStatusMessages(): void
{
    foreach ([200, 404, 500] as $code) {
        $message = match ($code) {
            200 => 'OK',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        };
        self::assertSame($message, HttpStatus::message($code));
    }
}

// Good
public static function providerStatusCodes(): array
{
    return [
        'ok'        => [200, 'OK'],
        'not found' => [404, 'Not Found'],
        'error'     => [500, 'Internal Server Error'],
    ];
}

#[DataProvider('providerStatusCodes')]
public function testStatusMessage(int $code, string $expected): void
{
    self::assertSame($expected, HttpStatus::message($code));
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
