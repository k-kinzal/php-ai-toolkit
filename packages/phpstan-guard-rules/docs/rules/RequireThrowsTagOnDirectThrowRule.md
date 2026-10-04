# RequireThrowsTagOnDirectThrowRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.missingThrowsTag` |
| Scope | All class methods |
| Configurable | No |

## What It Detects

Reports every `throw` statement in a method body whose exception is neither caught inside the same method nor declared in the method's `@throws` PHPDoc. Checked and unchecked exceptions are treated the same: what a method throws directly, it must declare.

```php
final class ConfigLoader
{
    public function load(string $path): Config
    {
        if (!is_file($path)) {
            // ERROR: Declare "@throws \App\ConfigException" in the PHPDoc of load() ...
            throw new ConfigException(sprintf('Config not found: %s', $path));
        }

        return $this->parse($path);
    }
}
```

### Not reported

- Throws caught by a matching `catch` in the same method (including supertype catches).
- Rethrows of a caught exception when the catch types are covered by the `@throws` tag.
- Throws inside closures, arrow functions, and anonymous classes — they escape when invoked, not where they are defined.
- Dynamic throws (`throw $this->makeError()`) whose class cannot be determined statically.
- Exceptions raised by called methods. Propagation through the call graph is intentionally not required for unchecked exceptions; PHPStan's own `exceptions.check.missingCheckedExceptionInThrows` handles propagation for checked exceptions.

## Why This Is an Error

1. **`@throws` only works if it exists at the origin**: PHPStan's exception analysis (dead catches, checked exceptions) is driven by `@throws` declarations. With `exceptions.implicitThrows: false`, an undeclared throw makes a perfectly valid `catch` in a caller look dead. Declaring the throw where it originates keeps the whole analysis sound.

2. **The contract stays visible**: A caller reading the signature learns what failure modes the method itself produces, without reading the body.

3. **AI anti-pattern**: AI generators add `throw` statements freely but almost never write the corresponding `@throws`. The result is an exception flow that neither humans nor static analysis can follow. This rule forces the documentation at the cheapest possible point — one line, at the site of the change.

## How to Fix

### Declare the thrown exception

```php
/**
 * Loads and validates the configuration file.
 *
 * @throws ConfigException when the file is missing or unparsable
 */
public function load(string $path): Config
```

### Or catch it in the same method

```php
public function tryLoad(string $path): ?Config
{
    try {
        return $this->doLoad($path);
    } catch (ConfigException $exception) {
        $this->logger->info('Falling back to defaults.', ['exception' => $exception]);

        return null;
    }
}
```

If the declared type is wrong (for example `@throws LogicException` while the body throws `RuntimeException`), correct the tag — PHPStan's `exceptions.check.tooWideThrowType` reports the stale side of such drift.

### When the throw is `\Exception` or `\Throwable`

Neither of the two fixes above applies, so the message asks for a third one:

```
Throw a concrete exception class here instead of \Exception, then declare it
with "@throws" in the PHPDoc of load(). Declaring "@throws \Exception" is
rejected as a generic tag and catching \Exception is rejected as a broad
catch, so neither of those resolves this.
```

Declaring the tag would trip [ForbidGenericThrowsTagRule](ForbidGenericThrowsTagRule.md) and catching it would trip [ForbidBroadCatchRule](ForbidBroadCatchRule.md), so the exception class itself has to become specific:

```php
// Before
throw new Exception('Config not found: ' . $path);

// After
throw new ConfigException('Config not found: ' . $path);
```

An exception raised by a *called* method and typed `\Exception` by that method's own `@throws` is a different case: this rule does not see it, and PHPStan reports it as `missingType.checkedException`. Narrowing it means wrapping the call in a `catch` at a boundary and rethrowing a concrete type.
