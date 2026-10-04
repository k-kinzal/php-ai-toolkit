# ForbiddenCommentRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.phpstanIgnoreComment`, `customRules.infectionIgnoreAllComment` |
| Scope | All files |
| Configurable | No |

## What It Detects

Reports two kinds of suppression comments:

### 1. PHPStan ignore comments (`customRules.phpstanIgnoreComment`)

```php
// ERROR: Remove phpstan-ignore comment
/** @phpstan-ignore-next-line */
$value = $container->get(SomeService::class);

$result = $service->process(); // @phpstan-ignore-line

// @phpstan-ignore argument.type
doSomething($untypedValue);
```

### 2. Infection ignore-all comments (`customRules.infectionIgnoreAllComment`)

```php
// ERROR: Remove infection-ignore-all comment
/** @infection-ignore-all */
function calculateDiscount(int $price, int $rate): int
{
    return (int) ($price * $rate / 100);
}
```

## Why This Is an Error

### PHPStan ignore comments

`@phpstan-ignore` comments silence specific static analysis errors without fixing the underlying issue. When AI generates code, it may add these comments as a shortcut to make the analysis pass, hiding real type mismatches, missing null checks, or incorrect API usage.

These comments erode trust in static analysis over time. Each suppressed error is a potential bug that CI will never catch. As suppression comments accumulate, the analysis becomes increasingly meaningless.

### Infection ignore-all comments

`@infection-ignore-all` tells mutation testing to skip an entire function or method. This removes the safety net that ensures tests actually verify the behavior of that code. AI-generated code with this annotation may appear well-tested while having tests that would pass regardless of what the code does.

## How to Fix

### For PHPStan ignore comments

1. Remove the `@phpstan-ignore` comment
2. Re-run PHPStan to reveal the actual error it was suppressing
3. Fix the root cause

```php
// Bad: suppressing a type error
/** @phpstan-ignore-next-line */
$name = $user->getName();

// Good: add a proper null check
$name = $user->getName();
if ($name === null) {
    throw new \RuntimeException('User name must not be null');
}
```

```php
// Bad: suppressing an argument type error
/** @phpstan-ignore-next-line */
$result = processItems($maybeArray);

// Good: validate and narrow the type
if (!is_array($maybeArray)) {
    throw new \InvalidArgumentException('Expected an array');
}
$result = processItems($maybeArray);
```

AI agents must not add or modify `ignoreErrors`. If suppression is genuinely justified (for example, a PHPStan bug or an untyped third-party library), ask a human operator to add a narrowly scoped `ignoreErrors` entry with the error identifier and rationale:

```neon
parameters:
    ignoreErrors:
        -
            identifier: argument.type
            paths:
                - src/Legacy/UserAdapter.php
            reportUnmatched: false
```

### For Infection ignore-all comments

1. Remove the `@infection-ignore-all` comment
2. Run mutation testing to identify surviving mutants
3. Strengthen assertions or add test cases to kill them

```php
// Bad: skipping mutation testing
/** @infection-ignore-all */
function clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

// Good: properly tested, no suppression needed
function clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

// With tests:
// testClamp_belowMin_returnsMin
// testClamp_aboveMax_returnsMax
// testClamp_withinRange_returnsValue
// testClamp_atBoundaries_returnsBoundary
```

If an exception is genuinely justified, ask a human operator to decide it.
