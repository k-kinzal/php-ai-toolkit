# NoBrokenCodeExpectationRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.noBrokenCodeExpectation` |
| Scope | All test classes (`Tests\` namespace) |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports PHPUnit exception expectations whose expected type only appears when the code under test is already broken:

- `Throwable` — every failure at once.
- `LogicException` and its subclasses (`InvalidArgumentException`, `DomainException`, `BadMethodCallException`, ...) — programmer errors.
- `Error` and its subclasses (`TypeError`, `ValueError`, `DivisionByZeroError`, ...) — engine failures.

```php
final class ReportImporterTest extends TestCase
{
    public function testRejectsNegativeOffset(): void
    {
        // ERROR: Delete this test case instead of expecting "InvalidArgumentException"
        // in expectException(): InvalidArgumentException is a programmer error ...
        $this->expectException(InvalidArgumentException::class);

        (new ReportImporter())->import('', -1);
    }
}
```

Both `expectException(SomeType::class)` and `expectExceptionObject(new SomeType(...))` are checked, called as `$this->expectException()`, `self::expectException()`, `static::expectException()`, or `parent::expectException()`.

### Not reported

- `RuntimeException` and its subclasses — the failures a caller is expected to handle.
- `Exception` and its non-`LogicException` descendants, such as `JsonException` or a domain exception extending `Exception`.
- `expectException($dynamicClassName)` where the expected type is not a literal `::class` constant, and `expectExceptionObject($object)` where the object has no single statically known class.
- `expectExceptionMessage()`, `expectExceptionCode()`, and the other expectation refinements.

## Why This Is an Error

1. **The test asserts a bug, not a behavior**: `LogicException` and `Error` mean the code itself is wrong — a precondition was violated, a type did not match, a division had a zero divisor. A test that expects one of them says "when this code is broken, it breaks". That is true of every program and proves nothing about the class under test.

2. **It contradicts the exception taxonomy**: [ForbidBroadCatchRule](ForbidBroadCatchRule.md) forbids *catching* `Throwable` and the `LogicException`/`Error` families because they must be fixed at the source rather than handled. `expectException()` is a catch in disguise, so the same types are equally out of place in a test.

3. **`Throwable` expectations verify nothing**: A passing `expectException(Throwable::class)` cannot distinguish the failure the test meant to trigger from a typo in the test itself, a `TypeError` from a wrong argument, or an unrelated fatal further down the call stack.

4. **AI anti-pattern**: When a generated test needs an assertion and the happy path is hard to set up, the cheapest thing to produce is a failure path — feed the method garbage and expect `InvalidArgumentException` or `TypeError`. It adds coverage and a green check without ever exercising what the class is for.

## How to Fix

### Delete the test case

The usual fix is removal. A test whose only assertion is that broken input breaks has no regression to protect, and deleting it costs nothing:

```php
// Delete: asserts that PHP rejects a wrong type, which PHP always does.
public function testRejectsNonStringPath(): void
{
    $this->expectException(TypeError::class);

    (new ReportImporter())->import(42);
}
```

### If the failure really is part of the contract, throw a recoverable type

An unreadable file, an unparsable payload, or a rejected remote response are runtime conditions a caller must handle. Make the production code say so, then expect that type:

```php
// src/Report/ReportImporter.php
throw new ReportSourceUnreadable(sprintf('Report source is unreadable: %s', $path));
// extends RuntimeException

// tests/Unit/Report/ReportImporterTest.php
public function testUnreadableSourceIsReported(): void
{
    $this->expectException(ReportSourceUnreadable::class);

    (new ReportImporter())->import('/no/such/report.json');
}
```

### Prefer asserting the observable result over asserting the failure

Where the method returns a value or leaves an observable trace, assert that instead of the exception. It pins down behavior the caller depends on rather than the way the code gives up.

## Configuration

Customize which namespaces are considered test classes:

```neon
parameters:
    customRules:
        testNamespacePrefixes:
            - 'Tests'
```
