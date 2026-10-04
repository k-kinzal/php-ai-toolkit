# ForbidEmptyCatchRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.emptyCatch` |
| Scope | All code |
| Configurable | No |

## What It Detects

Reports every `catch` block whose body contains no statements. A comment inside the block does not count as handling.

```php
final class ReportUploader
{
    public function upload(Report $report): void
    {
        try {
            $this->client->send($report);
        } catch (TransportException $exception) {
            // ERROR: Handle the caught TransportException in this empty catch block: ...
        }
    }
}
```

Non-binding catches (`catch (Throwable)`) are reported the same way.

## Why This Is an Error

1. **Failures become invisible**: The operation failed, but the program continues as if it succeeded. The bug surfaces later, far from its cause, with no trace of the original exception.

2. **AI anti-pattern**: When static analysis or a failing test complains about an exception, AI generators frequently "fix" the symptom by swallowing the exception instead of handling the failure. Combined with checked-exception analysis (`exceptions.check.missingCheckedExceptionInThrows`), an empty catch is the cheapest way to silence the analyzer — this rule closes that escape hatch.

3. **Silent catch is almost never the intent**: Even genuinely ignorable failures (best-effort cleanup, optional caches) deserve an explicit statement — a log call, a metric, or a deliberate fallback value — so a reader can tell the ignore is intentional.

## How to Fix

### Rethrow as a domain exception

```php
try {
    $data = $this->parser->parse($raw);
} catch (ParserException $exception) {
    throw new ImportFailedException('Import payload is not parsable.', 0, $exception);
}
```

### Log and recover deliberately

```php
try {
    $this->cache->warm($key);
} catch (CacheException $exception) {
    $this->logger->warning('Cache warm-up failed.', ['exception' => $exception]);
}
```

### Fall back with an explicit value

```php
try {
    return $this->remote->fetchLimit();
} catch (TransportException $exception) {
    $this->logger->info('Falling back to default limit.', ['exception' => $exception]);

    return self::DEFAULT_LIMIT;
}
```

If the exception genuinely cannot occur, remove the `try`/`catch` entirely instead of keeping a dead handler.
