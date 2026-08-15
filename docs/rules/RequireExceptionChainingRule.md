# RequireExceptionChainingRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.unchainedRethrow` |
| Scope | All code |
| Configurable | No |

## What It Detects

Reports a `throw` inside a `catch` block that creates a new exception (or calls a factory) without referencing the caught exception. Non-binding catches (`catch (Type)`) that throw a new exception are reported as well, because there is no variable to chain.

```php
try {
    $data = Yaml::parseFile($path);
} catch (ParseException $exception) {
    // ERROR: Pass the caught $exception to the exception thrown in this catch block ...
    throw new ConfigException('Invalid config file.');
}
```

### Not reported

- Plain rethrows: `throw $exception;`
- Throws whose expression references the caught variable anywhere: `throw new ConfigException('Invalid config file.', 0, $exception);` or `throw ConfigException::from($exception);`
- Throws inside nested `try` blocks, closures, arrow functions, and anonymous classes — nested catches are evaluated independently with their own caught variable.
- Throws of other variables (`throw $prepared;`) — the rule cannot track data flow and stays conservative.

## Why This Is an Error

1. **The stack trace dies here**: A wrapped exception without `$previous` erases where the original failure happened. The top-level handler logs the wrapper's trace, which points at the catch block — not at the real cause.

2. **Debugging AI-generated code depends on causality**: When an AI agent investigates a production error, `getPrevious()` chains are often the only machine-readable link from a domain error back to the I/O or parser failure underneath. Breaking the chain makes both human and AI debugging guesswork.

3. **AI anti-pattern**: AI generators frequently translate "wrap low-level exceptions" into `throw new DomainException($e->getMessage())` — keeping the text, discarding the object. The message survives; the type, code, trace, and previous chain do not.

## How to Fix

### Pass the caught exception as `$previous`

```php
try {
    $data = Yaml::parseFile($path);
} catch (ParseException $exception) {
    throw new ConfigException('Invalid config file.', 0, $exception);
}
```

### Or use a chaining factory

```php
} catch (TransportException $exception) {
    throw ImportFailedException::because('upstream unavailable', $exception);
}
```

### Bind the variable in a non-binding catch

```php
// Bad
} catch (ParseException) {
    throw new ConfigException('Invalid config file.');
}

// Good
} catch (ParseException $exception) {
    throw new ConfigException('Invalid config file.', 0, $exception);
}
```

Note: the rule checks that the caught variable is referenced, not that it lands in the `$previous` slot specifically. `throw new DomainException($exception->getMessage())` passes the check but still loses the chain — code review should keep preferring the `$previous` argument.
