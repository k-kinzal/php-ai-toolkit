# RequireExhaustiveDispatchRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.exhaustiveDispatch`, `customRules.exhaustiveDispatchDefault` |
| Scope | Every `switch` statement and every `match` expression |
| Rule classes | `RequireExhaustiveDispatchRule`, `RequireExhaustiveClassDispatchRule` |
| Configurable | No |

## What It Detects

Rust, Kotlin, and Swift refuse to compile a `match`/`when`/`switch` that leaves a variant of a
closed type out. That refusal is the point: adding a variant makes the compiler list every place
that has to be looked at again. PHP has no such check, and `default` removes even the runtime
`UnhandledMatchError`, so a case added to an enum today reaches `default` everywhere tomorrow and
the program keeps running with the wrong answer.

This rule reads the closed set of values out of the subject's own type, so nothing has to be
annotated:

```php
enum Suit: string
{
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Spades = 'spades';
    case Clubs = 'clubs';
}

// ERROR: Match expression sends Suit::Spades, Suit::Clubs to its "default" arm. Write an arm for
// each of those values so that a value added to the closed type is reported here instead of
// silently taking "default".
$colour = match ($suit) {
    Suit::Hearts, Suit::Diamonds => 'red',
    default => 'black',
};

// ERROR: Switch statement does not handle Suit::Spades, Suit::Clubs. Write a "case" for each of
// those values: the subject holds a closed set of values and this switch has no "default", so
// those fall through it unhandled.
switch ($suit) {
    case Suit::Hearts:
        return 'red';
    case Suit::Diamonds:
        return 'red';
}
```

### Which subjects are closed

A subject is closed when PHP itself bounds the values it admits. The rule asks the analyzer for
that list; where there is none, the dispatch is left alone.

| Subject type | Values |
|--------------|--------|
| An enum | its cases |
| `bool` | `true` and `false` |
| A union of literals, e.g. `'fast'\|'safe'\|'dry'` or a `@param self::MODE_*` tag | those literals |
| A nullable closed type, e.g. `?Suit` | `null` plus the cases |
| `$shape::class` or `get_class($shape)` | every instantiable class the subject can be, including those below an interface or an abstract class, read out of the analysed code |
| `$suit->value` on a backed enum | the case values |
| Anything narrowed to fewer values earlier in the method | only what is left |
| `string`, `int`, `object`, an object subject that is not read through its class name | *(open — not reported)* |
| Any subject of a `match (true)` or `switch (true)` | *(the construct names no subject — not reported)* |

### Which constructs state a subject

Exhaustiveness can only be asked of a construct that says what it is answering for, and PHP has
two ways of saying it.

The first is the subject written next to the keyword — `match ($suit)`, `switch ($mode)` — where
the values to cover are the values of that subject's type.

The second is the class name of an object read in the subject: `match ($shape::class)` and
`switch (get_class($shape))`. The object is named, so the classes to cover follow from its type,
and a branch of such a table can hold nothing but a class name, so nothing unrelated can enter it.
This is the form a sealed hierarchy needs, and it works for an interface or an abstract class as
well as for a union:

```php
interface Payment {}
final class Visa implements Payment {}
final class MasterCard implements Payment {}
final class BankTransfer implements Payment {}

// ERROR: Match expression sends BankTransfer to its "default" arm. Write an arm for each of
// those values so that a value added to the closed type is reported here instead of silently
// taking "default".
return match ($payment::class) {
    Visa::class => $this->card($payment),
    MasterCard::class => $this->card($payment),
    default => throw new LogicException('unreachable'),
};
```

Adding a fourth implementation of `Payment` anywhere in the project now fails analysis at this
line, which is the guarantee Kotlin gets from `sealed class` and Java from `sealed interface`.

Two things follow from where that class list comes from:

- The classes are gathered from the analysed paths, so the hierarchy is closed within them and
  nothing is claimed about code outside. That is the same boundary Kotlin draws (same module) and
  Java draws (same module, `permits` aside): a downstream implementation is invisible, and this
  check does not pretend otherwise.
- Because a class list is only complete once every file has been read, these errors are produced
  after the analysis rather than while the file is being read. They carry their file and line the
  same way, so `ignoreErrors` and editor navigation are unaffected.

### Why `match (true)` is never read

`match (true)` and `switch (true)` state no subject. The subject is a constant and every branch
carries a condition of its own, so an arm may test anything at all:

```php
match (true) {
    $shape instanceof Circle => $this->circle($shape),
    $size > 10 => $this->chunked($shape),    // nothing says this table is about $shape
    default => $this->generic($shape),
}
```

Nothing in the construct says the table is answering for `$shape` rather than for `$size`, or for
anything else an arm might reach. A set of values cannot be demanded of it, and that holds whether
`$shape` is typed as an interface or as `Circle|Square|Triangle`: the union says what `$shape` can
be, not what the table is for. Where the intent is a total dispatch, `$shape::class` says so.

### The two identifiers

| Identifier | Reported when |
|------------|---------------|
| `customRules.exhaustiveDispatch` | A `switch` names no `case` for a value and has no `default` either, so the value falls through the statement. |
| `customRules.exhaustiveDispatchDefault` | A `switch` or `match` has a `default` branch that absorbs values of the closed type. This is the check that makes adding a value break the build. |

### Not reported

- A `match` without a `default` arm. PHPStan already reports the values it leaves out under
  `match.unhandled`, and a second error on the same line would only duplicate it. A `switch` gets
  no such error from PHPStan, which is why this rule reports one.
- A dispatch that claims none of the values. That is a comparison which happens to sit on a closed
  type, not a half-finished dispatch — `if`-style code and single-branch `switch (true)` filters
  stay quiet.
- Any `match (true)` or `switch (true)`, for the reason above — including one whose branches all
  narrow the same expression. Write `$shape::class` where the table is meant to be total.
- A class-name dispatch whose branches are not all class names. One arm comparing against a plain
  string means the table is not answering for a set of classes, so nothing is required of it.
- A class-name dispatch that names no class of the subject's hierarchy at all. Like the
  claims-nothing case above, that is a comparison rather than a table.
- A subject that computes a class name rather than reading one, such as
  `match ($p === null ? Visa::class : $p::class)`. Only `$x::class` and `get_class($x)` name the
  object the table is answering for; anything else is a string whose values are unknown.
- A subject with more than 32 values. Past that the list stops being something a reader can act on,
  and the type is almost certainly not a hand-written set of alternatives.

## Why This Is an Error

1. **A closed type is a promise the dispatch has to keep**: An enum exists to say "one of exactly
   these". Every `default` over one turns that promise back into "anything", one call site at a
   time, and the analyzer stops being able to tell you where the next case has to be handled.

2. **Adding a case has to break the build, not the behaviour**: This is the whole value of an
   exhaustive `match` in Rust or Kotlin. With a `default` arm, adding `Suit::Joker` compiles, ships,
   and silently takes the `'black'` branch. With every arm written out, the analyzer names the four
   files to open.

3. **AI agents add enum cases**: Asked to support a new status, a model adds the case and updates
   the one dispatch it was looking at. `default => throw new LogicException('unreachable')` reads as
   defensive care and hides the other five dispatch sites — including the ones that quietly return a
   wrong value rather than throwing.

4. **`switch` has no analyzer backstop at all**: PHPStan reports an unhandled `match`; it reports
   nothing for a `switch`. A `switch` over an enum that misses a case falls straight through and the
   method returns `null` or continues with a stale variable.

## How to Fix

### Write the missing branches

```php
$colour = match ($suit) {
    Suit::Hearts, Suit::Diamonds => 'red',
    Suit::Spades, Suit::Clubs => 'black',
};
```

With every case named, the `default` arm is no longer needed at all: PHPStan proves the `match`
exhaustive and stops asking for one. Adding `Suit::Joker` then fails analysis at this line.

### Keep the default where the analyzer still wants one

A class-name dispatch cannot be proven exhaustive by PHPStan — it only knows the subject is some
`class-string<Payment>` — so it needs a `default` arm to satisfy `match.unhandled`. That is fine:
this rule reports only the classes that arm actually absorbs, so once every class has its own arm
the `default` is a formality and both checks are satisfied.

```php
return match ($payment::class) {
    Visa::class => $this->card($payment),
    MasterCard::class => $this->card($payment),
    BankTransfer::class => $this->transfer($payment),
    default => throw new LogicException('unreachable'),
};
```

### Say which type the table is answering for

`match (true)` states nothing about its subject, so a hierarchy dispatch written that way is not
checked. Read the class name instead, which names the object:

```php
-return match (true) {
-    $shape instanceof Circle => $shape->area(),
-    $shape instanceof Square => $shape->area(),
+return match ($shape::class) {
+    Circle::class => $shape->area(),
+    Square::class => $shape->area(),
     default => throw new LogicException('unreachable'),
 };
```

Note that `case Circle::class` matches `Circle` and not a subclass of it, so a class-name dispatch
treats every instantiable subclass as its own value to answer for. That is exact rather than
strict: it is what the comparison actually does at runtime.

### Turn the hierarchy into an enum

Where the classes carry no state of their own, an enum is the closed type PHP does have, and every
dispatch over it is checked without any further work.

### Suppress one of the two checks

The catch-all check is the stricter half. A project that wants unhandled `switch` values reported
but accepts `default` branches can keep the first and drop the second:

```neon
parameters:
    ignoreErrors:
        - identifier: customRules.exhaustiveDispatchDefault
```
