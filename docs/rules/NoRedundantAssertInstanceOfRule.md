# NoRedundantAssertInstanceOfRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.noRedundantAssertInstanceOf` |
| Scope | All test classes (`Tests\` namespace) |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports PHPUnit `assertInstanceOf()` calls where the asserted value already has one statically-known object type and PHPStan can prove that type is guaranteed to be an instance of the expected class or interface.

```php
final class AiTestReporterExtensionTest extends TestCase
{
    public function testImplementsExtensionInterface(): void
    {
        $extension = new AiTestReporterExtension();

        // ERROR: $extension is already known to be AiTestReporterExtension,
        // and AiTestReporterExtension implements Extension.
        self::assertInstanceOf(Extension::class, $extension);
    }
}
```

The rule applies to PHPUnit assertions called as `self::assertInstanceOf()`, `static::assertInstanceOf()`, `parent::assertInstanceOf()`, `$this->assertInstanceOf()`, `Assert::assertInstanceOf()`, and `TestCase::assertInstanceOf()`.

It intentionally does not report cases where the assertion narrows an unknown value:

```php
public function testFactoryResult(object $value): void
{
    self::assertInstanceOf(Extension::class, $value); // OK: narrows object to Extension
}
```

## Why This Is an Error

An `assertInstanceOf()` against a freshly constructed object or another single known type does not test behavior. It only repeats information PHPStan already knows from the expression type.

This creates low-value tests that make coverage look better without proving anything meaningful. AI-generated tests commonly include these assertions after `new Foo()` because they are easy to generate, but they do not catch regressions in the class's observable behavior.

## How to Fix

Remove the redundant assertion and assert the behavior the object is responsible for:

```php
final class ReportFormatterTest extends TestCase
{
    public function testFormatIncludesIssueLocation(): void
    {
        $formatter = new ReportFormatter();
        $issue = new TestIssue('/project/tests/FooTest.php', 42, 'Failed');

        $output = $formatter->format($issue);

        self::assertStringContainsString('tests/FooTest.php:42', $output);
    }
}
```

If the assertion is intended to narrow an unknown value, keep the value typed as `object`, `mixed`, or a union where the runtime check is meaningful. The rule only reports when the asserted value has exactly one statically-known object type.

## Configuration

Customize which namespaces are considered test classes:

```neon
parameters:
    customRules:
        testNamespacePrefixes:
            - 'Tests'
```
