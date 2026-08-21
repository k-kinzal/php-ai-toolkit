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
| A union of classes, e.g. `Circle\|Square\|Triangle` | those classes |
| `$shape::class` where every class of the union is `final` | those class names, as literal strings |
| `$shape::class` / `get_class($shape)` where `$shape` is an interface or abstract class | every class below it in the analysed code |
| Anything narrowed to fewer values earlier in the method | only what is left |
| `string`, `int`, `object`, an object subject that is not read through its class name | *(open — not reported)* |

A union of classes is one of PHP's two spellings of a sealed hierarchy, and it is read as one
where each branch carries its own condition, which is what `match (true)` and `switch (true)` do:

```php
// ERROR: Match expression sends Triangle to its "default" arm. ...
return match (true) {
    $shape instanceof Circle => $shape->area(),
    $shape instanceof Square => $shape->area(),
    default => throw new LogicException('unreachable'),
};
```

### Interfaces and abstract classes

The other spelling needs no union, and no annotation either. `match ($shape::class)` and
`switch (get_class($shape))` name the object the table is answering for, so the classes it has to
cover follow from that object's type — and the classes below an interface or an abstract class are
read out of the analysed code itself:

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

Two things follow from where the list comes from:

- The classes are gathered from the analysed paths, so the hierarchy is closed within them and
  nothing is claimed about code outside. That is the same boundary Kotlin draws (same module) and
  Java draws (same module, `permits` aside): a downstream implementation is invisible, and this
  check does not pretend otherwise.
- Because a class list is only complete once every file has been read, these errors are produced
  after the analysis rather than while the file is being read. They carry their file and line the
  same way, so `ignoreErrors` and editor navigation are unaffected.

`match (true)` is deliberately **not** read this way. Its subject is the constant `true`, so the
construct never states which type it is answering for, and any condition at all may sit in an arm:

```php
match (true) {
    $c instanceof ImportCommand => $this->import($c),
    $size > 10 => $this->chunked($c),        // nothing says this table is about commands
    default => $this->generic($c),
}
```

There is no set of classes such a table can be held to. Where the intent is a total dispatch over a
hierarchy, `$shape::class` says so; where it is a union type, the signature says so.

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
- A `match (true)` or `switch (true)` over an interface or an abstract class. That form states no
  subject, so `match (true) { $e instanceof NotFoundException => 404, default => 500 }` over
  `Throwable` is left alone. Over a union type it is checked, because the union states the set.
- A class-name dispatch whose branches are not all class names. One arm comparing against a plain
  string means the table is not answering for a set of classes, so nothing is required of it.
- A class-name dispatch that names no class of the subject's hierarchy at all. Like the
  claims-nothing case above, that is a comparison rather than a table.
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

A `match (true)` over `instanceof` conditions cannot be proven exhaustive by PHPStan — the subject
of the `match` is the constant `true` — so it needs a `default` arm to satisfy `match.unhandled`.
That is fine: this rule only reports the classes that arm actually absorbs, so once every class of
the union has its own arm the `default` is a formality and both rules are satisfied.

```php
return match (true) {
    $shape instanceof Circle => $shape->area(),
    $shape instanceof Square => $shape->area(),
    $shape instanceof Triangle => $shape->area(),
    default => throw new LogicException('unreachable'),
};
```

### Say which type the table is answering for

`match (true)` states nothing about its subject, so a hierarchy dispatch written that way is not
checked. Either read the class name, which names the object:

```php
-return match (true) {
-    $shape instanceof Circle => $shape->area(),
+return match ($shape::class) {
+    Circle::class => $shape->area(),
```

or name the alternatives in the signature, which turns the parameter itself into a closed type:

```php
-public function area(Shape $shape): float
+public function area(Circle|Square|Triangle $shape): float
```

The union can be given a name once with a PHPDoc type alias, so it does not have to be repeated:

```php
/**
 * @phpstan-type Shapes Circle|Square|Triangle
 */
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
