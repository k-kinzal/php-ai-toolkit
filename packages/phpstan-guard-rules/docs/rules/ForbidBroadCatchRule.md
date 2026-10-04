# ForbidBroadCatchRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.broadCatch` |
| Scope | All code outside configured boundary paths |
| Configurable | Yes (`broadCatchAllowedPaths`) |

## What It Detects

Reports `catch` clauses that intercept types too broad to handle meaningfully:

- `Throwable` and `Exception` — the roots that catch everything.
- `LogicException` and its subclasses (`InvalidArgumentException`, `DomainException`, `BadMethodCallException`, ...) — programmer errors.
- `Error` and its subclasses (`TypeError`, `ValueError`, `DivisionByZeroError`, ...) — engine failures.

```php
final class ReportImporter
{
    public function import(string $raw): Report
    {
        try {
            return $this->parser->parse($raw);
        } catch (Throwable $exception) {
            // ERROR: Catch a specific exception type instead of "Throwable" ...
            return Report::empty();
        }
    }
}
```

Union catch types are checked per member: `catch (DivisionByZeroError|TransportException)` reports only the `DivisionByZeroError` part.

### Not reported

- Specific exception types: `RuntimeException` subclasses, `JsonException`, domain exceptions.
- Any catch inside a file matching `broadCatchAllowedPaths` — the designated boundary layer.

## Why This Is an Error

1. **Broad catches swallow bugs**: `catch (Exception)` also catches `LogicException`; `catch (Throwable)` also catches `TypeError`. A precondition violation that should crash loudly in CI instead becomes a silent fallback in production.

2. **LogicException / Error are not recoverable conditions**: They mean the code itself is wrong. Catching them converts a fixable bug into undefined behavior. The fix belongs at the throw site, not in a handler.

3. **AI anti-pattern**: `try { ... } catch (\Exception $e) { return null; }` is one of the most common AI-generated constructs — it makes failing tests pass by making failures invisible. Combined with checked-exception analysis, broad catches are also the cheapest way to silence `missingCheckedExceptionInThrows`; this rule closes that escape hatch.

4. **Boundaries are the exception, literally**: A CLI entry point, HTTP middleware, or worker loop legitimately catches `Throwable` to log and convert it into an exit code or 500 response. That is a deliberate architectural role — so it is declared in configuration, not scattered ad hoc.

## How to Fix

### Catch the specific type you can actually handle

```php
try {
    return $this->parser->parse($raw);
} catch (ParserException $exception) {
    throw new ImportFailedException('Import payload is not parsable.', 0, $exception);
}
```

### For LogicException / Error catches: fix the cause instead

```php
// Bad: hides the bug
try {
    $slice = array_slice($items, $offset, $length);
} catch (ValueError) {
    $slice = [];
}

// Good: enforce the precondition
if ($offset < 0) {
    throw new InvalidArgumentException(sprintf('Offset must be >= 0, got %d.', $offset));
}
$slice = array_slice($items, $offset, $length);
```

### Declare genuine boundary handlers in configuration

```neon
parameters:
    toolkit:
        broadCatchAllowedPaths:
            - 'src/*/Cli/Application.php'
            - 'src/Http/Middleware/ErrorHandler.php'
```

Patterns are `fnmatch` globs matched against the analyzed file path (also with a leading `*/`, so project-relative patterns work). Note that `*` crosses directory separators.

## Configuration

| Parameter | Default | Description |
|-----------|---------|-------------|
| `broadCatchAllowedPaths` | `[]` | fnmatch patterns of files allowed to catch broad types |
