# EnforceMutationContractRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.mutationInvalidContract`, `customRules.mutationUndeclared`, `customRules.mutationOverrideWidened` |
| Scope | Named functions and methods in PHPStan's analysed files |
| Configurable | Yes; opt-in |

## Purpose

The rule makes changes that escape a callable explicit. A parameter is read-only unless its `@param` description
starts with `+mut`; an instance receiver and global state are read-only unless the callable has the corresponding
`@mutation` target. Local variables remain mutable without an annotation.

```php
/**
 * @param Cart $cart +mut cart receiving the item
 * @mutation $this, global
 */
public function add(Cart $cart, Item $item): void
{
    $cart->items[] = $item;
    ++$this->addedItems;
    ++$GLOBALS['items_added'];
}
```

`+mut` must be the first token after the parameter name. It is part of the parameter description rather than a
second tag, so the type, name, permission, and prose stay on one line. The marker is also recognized on
`@psalm-param` and `@phpstan-param`; a marker on any matching parameter tag grants the effect.

`@mutation` accepts only `$this`, `global`, or both as a comma-separated value:

```php
/** @mutation $this */
public function advance(): void
{
    ++$this->position;
}
```

## Effects Checked

The rule follows assignments, increments, decrements, `unset`, property and array writes, static property writes,
function-static variables, imported globals, and simple local aliases. Reassigning a parameter is a parameter mutation even when PHP passed its value by value;
the contract describes which inputs the implementation writes, not only which writes a caller can observe.

Effects propagate through statically resolved calls. Passing a read-only parameter to a callee's `+mut` parameter,
calling a `$this`-mutating method on a read-only parameter, or calling a `global`-mutating callable from a callable
without that permission reports the caller. Propagation is transitive and recursive calls are resolved to a fixed
point.

Constructor writes to the newly created `$this` are local initialization and need no `@mutation $this`. Parameter
and global effects in a constructor are still checked.

## Inheritance

Method effects are inherited by parameter position, so an implementation receives an interface or parent method's
permissions even if parameter names differ. An override may use fewer effects, but it may not declare an effect its
parent contract did not permit. Widening is reported with `customRules.mutationOverrideWidened` because a caller
typed against the parent would otherwise receive a weaker guarantee than the implementation provides.

Only parent and interface declarations included in PHPStan's analysed files participate in this comparison.
An inherited declaration supplied only by a dependency is an external boundary.

## Guarantee Boundary

The rule guarantees contracts for named functions and methods whose declarations PHPStan analyses, and for calls
PHPStan can resolve to those declarations. Dynamic function names, dynamic method names, `__call`, reflection,
callbacks invoked by an unanalysed higher-order function, closures, arrow functions, built-ins, and dependency
implementations are not assigned an inferred mutation effect. Wrap such a boundary in a named project callable and
declare the wrapper's effects when the mutation matters to callers.

Origins computed by dynamic variable access or reflection are likewise outside the alias model. Ordinary locals,
parameter aliases, `$this`, superglobals, `global` imports, and static properties are covered.

PHP 8 `readonly` remains a separate language guarantee. A mutation annotation cannot make assignment to a readonly
property legal, and PHPStan continues to report that assignment. Conversely, a readonly property containing an
object does not make the object's interior immutable; changing that object through a parameter or receiver still
requires the mutation contract.

## Configuration

Mutation checking is opt-in because enabling it on an existing project turns every unannotated callable into a
read-only contract. Enable the rule after adding contracts to the desired analysis scope:

```neon
parameters:
    toolkit:
        mutation:
            enabled: true
```

PHPStan's `paths` and `excludePaths` define the whole-program boundary. Enabling the option registers the declaration
and operation collectors together with the final rule; disabling it removes all three.

## Fixing a Violation

Prefer keeping the callable read-only when the write is incidental: copy the value, construct a result, or move the
state change behind an already mutable boundary. When mutation is part of the callable's contract, add `+mut` to the
matching `@param` line or add the smallest required `@mutation` target. Do not add `global` merely because a callee
happens to use it; first decide whether that dependency belongs in the caller's public effect contract.
