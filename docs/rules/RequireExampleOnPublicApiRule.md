# RequireExampleOnPublicApiRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.requireExampleOnClass`, `customRules.requireExampleOnMethod`, `customRules.requireExampleOnProperty`, `customRules.requireExampleOnConstant`, `customRules.requireExampleOnEnumCase` |
| Scope | Declarations carrying `@visibility public` (excluding restricted test namespaces) |
| Configurable | Via `restrictedTestNamespacePrefixes` |

## What It Detects

Reports a declaration that states it is public API without documenting an example doctest can run:

```php
/**
 * Appends entries to the ledger.
 *
 * @visibility public
 */
final class Ledger  // ERROR: Add an @example block to class Ledger: it is declared public API with "@visibility public", ...
{
    /**
     * The ledger format version.
     *
     * @visibility public
     */
    public const VERSION = '1.0';  // ERROR: constant Ledger::VERSION

    /**
     * Appends one entry.
     *
     * @visibility public
     */
    public function append(Entry $entry): void  // ERROR: method Ledger::append()
    {
        // ...
    }
}
```

### Not reported

- Declarations without a `@visibility` tag. Leaving the tag off also leaves a symbol reachable from
  everywhere, but it says nothing about intent — see *Why the tag decides* below.
- Declarations whose `@visibility` narrows the scope: `namespace`, `parent`, `root`, or a named
  namespace.
- Declarations that already carry a runnable example, in either notation doctest recognizes.
- Classes in restricted test namespaces (`Tests\Unit`, `Tests\Integration` by default).
- Anonymous classes.

### Display-only examples do not satisfy the rule

A single-line `@example` tag documents a shape rather than a program. Doctest renders it and never
runs it, so it does not count:

```php
/**
 * @visibility public
 *
 * @example $ledger->append($entry)
 */
final class Ledger  // ERROR: still reported; nothing here can be executed.
```

## Why This Is an Error

1. **Prose goes stale silently; an example cannot.** A description of what a method does is checked
   by nobody. An example runs as a PHPUnit test case, so the moment the code stops behaving as
   documented, the build says so.

2. **Public API is a promise, and a promise should be demonstrable.** The declaration that a symbol
   is public is the moment to show what calling it looks like — the parameters that go together, the
   shape that comes back, the exception a caller has to expect.

3. **AI-generated code documents types, not usage.** Generated PHPDoc reliably restates the
   signature and rarely shows a call. An executable example is the part a generator skips and the
   part a reader needs.

## Why the tag decides

The rule keys off `@visibility public` rather than off "everything public in PHP", because those are
different statements. PHP's `public` keyword says a symbol is *reachable*; ScopeGuard's
`@visibility public` says it is *intended for use by other code* — the tag exists to state that
intent. Requiring an example on the first would put a demonstration on every internal collaborator a
package happens to expose; requiring it on the second puts one on exactly the surface a project has
declared it supports.

That also makes the requirement adoptable: a project turns the rule on, reports nothing, and then
tags one boundary at a time. See [ScopeGuard Configuration](../scope-guard.md) for the tag itself.

## How to Fix

Add an example that runs. Either notation works.

An `@example` tag with indented code under it:

```php
/**
 * Appends entries to the ledger.
 *
 * @visibility public
 *
 * @example Appending an entry
 *     $ledger = new Ledger();
 *     $ledger->append(new Entry('rent', 1200));
 *     $ledger->count() // => 1
 */
final class Ledger
{
    /**
     * Appends one entry.
     *
     * @visibility public
     *
     * @example Rejecting an entry with no amount
     *     (new Ledger())->append(new Entry('rent', 0)) // throws InvalidArgumentException: amount
     */
    public function append(Entry $entry): void
    {
        // ...
    }
}
```

Or a fenced `php` block:

````php
/**
 * The ledger format version.
 *
 * @visibility public
 *
 * ```php
 * Ledger::VERSION // => '1.0'
 * ```
 */
public const VERSION = '1.0';
````

Assert on the example with `// => value`, `// Output: text`, or `// throws ExceptionClass`. A line
without a marker still runs, and still fails the example if it raises — which is a real check, just a
weaker one. See [Doctest Configuration](../doctest.md) for the full notation.

### Not fixes

- Removing the `@visibility public` tag. That drops the declaration of intent instead of documenting
  it, and it also drops the ScopeGuard statement the tag was written to make.
- Narrowing the scope to `namespace` to silence the rule. If the symbol really is internal, narrowing
  is correct and the reference sites will say so; if it is not, this hides a design decision behind a
  lint fix.
- Writing an example with no assertion when the behaviour is assertable. It passes, but it documents
  less than the prose above it.

## Relationship to doctest

The rule requires exactly what the doctest test suite executes: extraction is delegated to the same
grammar, so a block this rule accepts is a block that becomes a PHPUnit test case, and an example the
runner would skip does not satisfy the rule. Once the rule is green, running the suite is what keeps
it honest.
