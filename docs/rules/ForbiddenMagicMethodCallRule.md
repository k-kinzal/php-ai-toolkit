# ForbiddenMagicMethodCallRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenMagicMethodCall` |
| Scope | All classes |
| Configurable | No |

## What It Detects

Reports direct calls to PHP magic methods. The full list of detected methods:

`__construct`, `__destruct`, `__call`, `__callStatic`, `__get`, `__set`, `__isset`, `__unset`, `__sleep`, `__wakeup`, `__serialize`, `__unserialize`, `__toString`, `__invoke`, `__set_state`, `__clone`, `__debugInfo`

```php
// ERROR: Direct call to magic method __toString() is prohibited.
//        Use (string) cast: (string)$obj.
$text = $obj->__toString();

// ERROR: Direct call to magic method __clone() is prohibited.
//        Use the clone keyword: clone $obj.
$copy = $obj->__clone();

// ERROR: Direct call to magic method __invoke() is prohibited.
//        Call the object as a function: $obj(...$args).
$result = $handler->__invoke($request);
```

The error message includes the specific language construct to use for each magic method.

### Allowed: `parent::` calls

Static dispatch to a parent class is allowed because this is the standard pattern for calling parent constructors and other overridden methods:

```php
class ChildService extends BaseService
{
    public function __construct(private readonly Logger $logger)
    {
        parent::__construct(); // OK
    }
}
```

## Why This Is an Error

Magic methods are designed to be invoked implicitly by PHP language constructs. Calling them directly bypasses the language semantics and makes the code harder to understand:

- `$obj->__toString()` bypasses string casting, which may behave differently in edge cases
- `$obj->__clone()` bypasses the `clone` keyword, which handles internal reference copying
- `$handler->__invoke($request)` is semantically identical to `$handler($request)` but obscures the intent

AI code generators frequently produce direct magic method calls because they treat these as regular methods. This results in code that works but defies PHP conventions and reduces readability.

## How to Fix

Replace direct calls with the corresponding language constructs. The error message tells you exactly which construct to use.

| Direct call | Language construct |
|-------------|-------------------|
| `$obj->__toString()` | `(string) $obj` |
| `$obj->__clone()` | `clone $obj` |
| `$obj->__invoke($arg)` | `$obj($arg)` |
| `$obj->__construct(...)` | `new ClassName(...)` |
| `$obj->__destruct()` | `unset($obj)` or let it go out of scope |
| `$obj->__get('key')` | `$obj->key` |
| `$obj->__set('key', $val)` | `$obj->key = $val` |
| `$obj->__isset('key')` | `isset($obj->key)` |
| `$obj->__unset('key')` | `unset($obj->key)` |
| `$obj->__call('m', $a)` | `$obj->m(...$a)` |
| `Cls::__callStatic('m', $a)` | `Cls::m(...$a)` |
| `$obj->__sleep()` | `serialize($obj)` |
| `$obj->__wakeup()` | `unserialize($data)` |
| `$obj->__serialize()` | `serialize($obj)` |
| `$obj->__unserialize($d)` | `unserialize($data)` |
| `Cls::__set_state($arr)` | Reconstruct via constructor or factory method |
| `$obj->__debugInfo()` | `var_dump($obj)` |

```php
// Bad
$text = $obj->__toString();
$copy = $obj->__clone();
$result = $handler->__invoke($request);

// Good
$text = (string) $obj;
$copy = clone $obj;
$result = $handler($request);
```
