# NamespaceVisibilityRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.namespaceVisibility`, `customRules.namespaceVisibilityTag` |
| Scope | Every declaration carrying a `@visibility` tag, and every usage that reaches one |
| Configurable | Yes (`visibilityExemptNamespacePrefixes`) |

## What It Detects

PHP has two visibility levels for anything a namespace exports: `public`, which means public to the
whole program, and nothing. Rust spells the middle ground with `pub(crate)`, `pub(super)`, and
`pub(in path)`; PHP has no keyword for it, so this rule reads the intent from a PHPDoc tag and
enforces it.

Tag a declaration with `@visibility <scope>` and the rule reports every usage that reaches it from
outside that scope:

```php
namespace App\Billing\Ledger;

/**
 * Appends entries to the ledger file.
 *
 * @visibility parent
 */
final class LedgerWriter
{
    public function append(Entry $entry): void { /* ... */ }
}
```

```php
namespace App\Http;

use App\Billing\Ledger\LedgerWriter;

final class InvoiceController
{
    public function store(): void
    {
        // ERROR: Class App\Billing\Ledger\LedgerWriter is not visible from namespace "App\Http".
        // The declaration is marked "@visibility parent", so it may only be used from namespace
        // "App\Billing" and its sub-namespaces. Move the caller into that namespace, or widen the
        // declaration to "@visibility App".
        (new LedgerWriter())->append($entry);
    }
}
```

### Scopes

A scope is always one namespace plus everything below it, the way a Rust module contains its
descendants. The declaring namespace is always inside its own scope, so a declaration never hides
from the code next to it.

| Scope | Rust equivalent | Visible from |
|-------|-----------------|--------------|
| `public` | `pub` | Everywhere. Same as leaving the tag off; write it to state the intent. |
| `root` | `pub(crate)` | The outermost segment of the declaring namespace and everything below it. |
| `parent` | `pub(super)` | The namespace directly above the declaring one, and everything below it. |
| `namespace` | `pub(self)` | The declaring namespace and its sub-namespaces only. |
| `App\Billing` | `pub(in path)` | The named namespace and its sub-namespaces, plus the declaring namespace. |

For a grandparent and beyond, name the namespace: `@visibility App\Billing` says more about the
design than "two levels up" does, and it survives a move of the declaring class.

Several tags union: `@visibility namespace` plus `@visibility App\Console` opens the declaration to
its own namespace and to the console namespace, and to nothing else.

A namespace whose first segment is a single lowercase word collides with the keywords, so write it
with a leading backslash: `@visibility \legacy` names the namespace `legacy`, while
`@visibility legacy` is reported as a mistyped keyword.

### Where the tag can go

- Classes, interfaces, traits, and enums — restricts the whole type.
- Methods, properties, class constants, and enum cases — restricts one member of an otherwise
  public type. A member is never wider than its type: a `@visibility namespace` class keeps its
  untagged public methods inside that namespace too.

### What is checked

| Usage | Example |
|-------|---------|
| Instantiation | `new LedgerWriter()` |
| Method calls | `$writer->append(...)`, `LedgerWriter::open()`, `$writer?->append(...)` |
| Property access | `$writer->handle`, `LedgerWriter::$open` |
| Constants and cases | `LedgerWriter::MODE`, `Suit::Hearts` |
| Class name references | `LedgerWriter::class`, `$value instanceof LedgerWriter` |
| Inheritance | `extends`, `implements`, `use <Trait>` |
| Signature types | parameter, return, and property type declarations |

`self`, `static`, and `parent` are never reported: they can only name the class the caller is
already inside or one it inherits from, and that inheritance is checked at the declaration.

### Malformed tags

A scope that cannot be honoured is reported at the declaration under
`customRules.namespaceVisibilityTag`, because a tag that silently resolves to nothing would drop the
restriction it was written to add:

```php
/**
 * @visibility parrent
 */
```

> Fix "@visibility parrent" on class App\Billing\Ledger\LedgerWriter: one bare lowercase word is read
> as a scope keyword, and "parrent" is not one of "public", "root", "parent", "namespace"; write the
> keyword you meant, or write "\parrent" to name the namespace.

The same applies to `@visibility parent` on a class in a root namespace (its parent is the global
namespace, which restricts nothing), to any scope keyword on a class in the global namespace, and to
`@visibility public` written next to a narrowing tag.

## Why This Is an Error

1. **`public` is the only export PHP has**: Every helper a namespace needs internally is reachable
   from the entire program. The moment one caller outside the package touches it, the class is
   public API and can no longer be changed freely. Rust solves this with `pub(crate)`; this tag is
   the same statement, checked at analysis time instead of compile time.

2. **AI agents reach for whatever is reachable**: Asked to add a feature, an agent will call the
   first class whose name matches, wherever it lives. Layering that exists only in a diagram or a
   directory name does not survive that. The scope has to be attached to the declaration and
   enforced.

3. **It documents the boundary at the boundary**: `@visibility namespace` on a class states, next to
   the class, that it is an implementation detail. A reviewer, and an agent, sees it before reaching
   for it, and the error message names the namespace it belongs to.

4. **A tag with no checker is a comment**: `@internal` has meant "please do not use this" for two
   decades and has never stopped anyone. The value here is the checker, not the tag.

## How to Fix

### Move the caller into the scope

The usual answer: the code that wants the internal class belongs beside it.

```php
namespace App\Billing\Ledger;

final class LedgerAppender
{
    public function append(Entry $entry): void
    {
        (new LedgerWriter())->append($entry);
    }
}
```

### Widen the declaration

If the scope was drawn too narrowly, widen it. The error message names the narrowest scope that
would admit the caller.

```php
/**
 * @visibility App\Billing
 */
```

### Give the namespace a public front door

When several outside callers want the same internal class, that is a missing entry point, not a
missing permission. Keep the internals scoped and export one public type:

```php
namespace App\Billing;

/**
 * Records billing events. The only supported way into App\Billing\Ledger.
 */
final class BillingLog
{
    public function record(Entry $entry): void { /* delegates to the scoped writer */ }
}
```

### Let the constructor be the only scoped member

A class can stay publicly usable while only its own namespace may build it, which is how a factory
becomes the single construction path:

```php
final class Connection
{
    /**
     * @visibility namespace
     */
    public function __construct(private Socket $socket) {}
}
```

## Configuration

| Parameter | Default | Description |
|-----------|---------|-------------|
| `visibilityExemptNamespacePrefixes` | `['Tests']` | Namespace prefixes whose code may use any declaration regardless of scope |

```neon
parameters:
    customRules:
        visibilityExemptNamespacePrefixes:
            - 'Tests'
            - 'App\Console\Debug'
```

A prefix exempts its whole subtree. Tests are exempt by default because PHP has no counterpart to
Rust's `#[cfg(test)] mod tests` declared inside the module it covers: a unit test of a
`@visibility namespace` class necessarily sits in a different namespace, and
[SrcUnitTestPairRule](SrcUnitTestPairRule.md) requires that test to exist. Malformed tags are still
reported inside an exempt namespace — a tag that cannot be honoured is wrong wherever it is written.

## Prior Art

The tag has three ancestors, and borrows something from each:

- **phpDocumentor 1.x `@access private|protected|public`** — the original PHPDoc visibility tag,
  dropped once PHP gained real visibility keywords. It named the concept but could only repeat what
  the language already said.
- **PSR-19 `@internal`** — marks an element as internal to the project. It carries no scope, and
  nothing checks it.
- **Psalm `@psalm-internal Foo\Bar`** — restricts usage to a named namespace. This is the closest
  existing specification, and `@visibility Foo\Bar` means the same thing.

`@visibility` is a separate tag rather than a reuse of `@internal` because `@internal` accepts free
text (`@internal do not use`), which cannot be told apart from a scope. A dedicated tag keeps every
value meaningful, so a typo is an error instead of a silently different scope.
