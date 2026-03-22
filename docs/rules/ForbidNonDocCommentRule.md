# ForbidNonDocCommentRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.nonDocComment` |
| Scope | All files |
| Configurable | No |

## What It Detects

Reports any non-PHPDoc comment. All `T_COMMENT` tokens are forbidden — this includes `//` line comments, `/* */` block comments, and `#` shell-style comments. Only `/** */` PHPDoc blocks (`T_DOC_COMMENT`) are allowed.

```php
// ERROR: Non-PHPDoc comment is prohibited
// This is a line comment
function foo(): void {}

// ERROR: Non-PHPDoc comment is prohibited
/* This is a block comment */
function bar(): void {}

// ERROR: Non-PHPDoc comment is prohibited
# This is a hash comment
function baz(): void {}

// OK: PHPDoc is allowed
/** @var string $name */
$name = getName();
```

Comments containing `@phpstan-ignore` or `@infection-ignore-all` are skipped by this rule because they are already handled by [ForbiddenCommentRule](ForbiddenCommentRule.md).

## Why This Is an Error

Code should be self-explanatory through clear naming, small methods, and proper type declarations. Non-PHPDoc comments (especially `//` and `/* */`) are noise that AI agents frequently generate to explain obvious logic.

When `//` comments alone are forbidden, AI agents escape by converting to `/* */` block comments. Forbidding all `T_COMMENT` tokens closes this loophole entirely.

PHPDoc (`/** */`) remains allowed because it serves a functional purpose: type annotations (`@var`, `@param`, `@return`), cross-references (`@see`), and tool directives (`@dataProvider`, `@extends`).

## How to Fix

1. **If the comment explains *what* the code does** — delete it and improve naming instead
2. **If the comment documents an API contract** — convert to a `/** */` PHPDoc block
3. **If the comment is a type annotation like `/* @var */`** — fix to `/** @var */` (the `/*` form is a bug; PHPStan only reads `/** */`)

```php
// Bad: line comment explaining obvious logic
// Get the user's name
$name = $user->getName();

// Good: no comment needed
$name = $user->getName();
```

```php
// Bad: block comment for a type annotation
/* @var string $name */

// Good: proper PHPDoc block
/** @var string $name */
```
