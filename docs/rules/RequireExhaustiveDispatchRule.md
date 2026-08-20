# RequireExhaustiveDispatchRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.exhaustiveDispatch`, `customRules.exhaustiveDispatchDefault` |
| Scope | Every `switch` statement and every `match` expression |
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
| Anything narrowed to fewer values earlier in the method | only what is left |
| `string`, `int`, `object`, an interface, an extensible class | *(open — not reported)* |

A union of classes is PHP's spelling of a sealed hierarchy, and it is read as one only where each
branch carries its own condition, which is what `match (true)` and `switch (true)` do:

```php
// ERROR: Match expression sends Triangle to its "default" arm. ...
return match (true) {
    $shape instanceof Circle => $shape->area(),
    $shape instanceof Square => $shape->area(),
    default => throw new LogicException('unreachable'),
};
```

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
- A dispatch over an interface or an extensible class. PHP has no `sealed`, so such a type is open:
  `match (true) { $e instanceof NotFoundException => 404, default => 500 }` over `Throwable` is a
  deliberate partial handling and is left alone. Write the union of the classes to say otherwise.
- `switch (get_class($shape))`. `get_class()` is typed `class-string<Circle>|class-string<Square>`
  rather than the literal class names, so there is no closed set of values to check. Write
  `$shape::class`, which the analyzer resolves to the literal names — and which this rule therefore
  does check — whenever every class of the union is `final`.
- `match ($shape::class)` where one class of the union is not `final`. `case Circle::class` does not
  match a subclass of `Circle`, so the analyzer keeps the value as `class-string<Circle>` rather
  than one literal name, and the dispatch is left alone. Use `instanceof` conditions there.
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

### Spell the sealed hierarchy as a union

PHP has no `sealed` keyword, and an interface with three implementations today can have a fourth
tomorrow, so the rule will not guess. Name the alternatives in the signature and the hierarchy
becomes closed at every call site, in plain PHP:

```php
-public function area(Shape $shape): float
+public function area(Circle|Square|Triangle $shape): float
```

The union can also be given a name once with a PHPDoc type alias, so it does not have to be
repeated:

```php
/**
 * @phpstan-type Shapes Circle|Square|Triangle
 */
```

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
