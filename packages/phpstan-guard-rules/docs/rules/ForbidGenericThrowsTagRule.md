# ForbidGenericThrowsTagRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.genericThrowsTag` |
| Scope | All class methods |
| Configurable | No |

## What It Detects

Reports `@throws` tags that resolve to the root `\Exception` or `\Throwable` types, including as members of a union tag.

```php
final class ReportImporter
{
    /**
     * Imports one report payload.
     *
     * @throws Exception   ← ERROR: Replace "@throws \Exception" on import() with the concrete exception types ...
     */
    public function import(string $raw): Report
    {
        // ...
    }
}
```

Class names are resolved through the file's `use` statements, so a project class that happens to be named `Exception` (e.g. `App\Exception`) is not reported.

### Not reported

- Concrete exception types: `@throws RuntimeException`, `@throws JsonException`, `@throws ImportFailedException`.
- Unions of concrete types: `@throws JsonException|TransportException`.
- Methods without their own `@throws` tag.

## Why This Is an Error

1. **No actionable contract**: `@throws Exception` tells a caller "something may go wrong" — they can only respond with `catch (Exception)`, which is itself a broad catch that swallows programmer errors (see [ForbidBroadCatchRule](ForbidBroadCatchRule.md)).

2. **Checked-exception analysis degenerates**: With `exceptions.check.missingCheckedExceptionInThrows` enabled, a generic tag satisfies the analyzer for *every* checked exception at once, so real, specific exception flows stop being verified.

3. **AI anti-pattern**: When forced to add a `@throws` tag, AI generators reach for `@throws \Exception` as the universally "correct" answer. It silences the analyzer while destroying the information the tag exists to carry. This rule forces the model to name what is actually thrown.

## How to Fix

### Name the concrete exceptions

```php
/**
 * Imports one report payload.
 *
 * @throws ParserException when the payload is not valid JSON
 * @throws ImportFailedException when the report violates the schema
 */
public function import(string $raw): Report
```

### If a dependency's API genuinely throws bare \Exception

Wrap the call at the adapter boundary and rethrow a concrete type:

```php
try {
    $date = new DateTimeImmutable($input);   // throws bare \Exception before PHP 8.3
} catch (Exception $exception) {
    throw new InvalidScheduleException(sprintf('Not a parsable date: %s', $input), 0, $exception);
}
```

The adapter file can be listed in `broadCatchAllowedPaths` if needed, keeping the generic type contained at one declared boundary.
