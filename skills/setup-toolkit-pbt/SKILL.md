---
name: setup-toolkit-pbt
description: >-
  Set up property-based testing with Eris in a PHP project, including
  domain-specific generators, shrinkable properties, reproducible seeds, PHPUnit
  group isolation, dedicated Composer commands, and a separate GitHub Actions
  workflow. Use when structured inputs and invariants are a better fit than
  coverage-guided byte fuzzing.
---

# Set Up Property-Based Testing with Eris

Property-based testing (PBT) verifies a contract over many generated structured
values and shrinks a failure to a smaller counterexample. It is not a replacement
for examples, and an iteration loop around random Faker values is not PBT unless
the generator, property, shrinking, and reproduction behavior are intentional.

## Inspect and Choose the Contract

Read `composer.json`, PHP and PHPUnit constraints, every lock and PHPUnit
configuration, current unit/integration scripts, coverage metadata policy, source
contracts, and existing generators or random tests.

Use PBT when the important claim can be expressed over structured values, for
example:

- encode/decode or parse/render round trips;
- idempotence and canonicalization;
- algebraic behavior that the product actually promises;
- equivalence with a small reference model or alternate implementation;
- invariants before and after a command sequence; or
- boundary-rich validation where Eris can shrink to a useful value.

Use `/setup-toolkit-fuzzing` instead when corpus mutation and code-coverage feedback
are needed to explore a parser, protocol, query grammar, file format, HTTP request
surface, or other high-dimensional byte-oriented input. Both may be used on one
component when they verify distinct contracts.

Before writing the property, state:

1. the exact system boundary;
2. the input domain and invalid subdomains;
3. the invariant or reference model;
4. documented normalization or nondeterminism that affects comparison;
5. state reset and side-effect isolation; and
6. the cost per evaluation and resulting iteration budget.

Do not derive the oracle from the same implementation path being tested. A
round-trip whose encoder and decoder share the same faulty normalization may need
an independent semantic check as well.

## Install a Compatible Eris Release

Inspect Eris release metadata at application time. Select the newest stable release
compatible with the PHP runtime and PHPUnit generation that executes PBT. Current
Eris release lines have different PHP/PHPUnit support; do not copy this repository's
constraint or assume one release spans a legacy PHPUnit 9 leg and a modern PHPUnit
10+ leg.

Test the target-derived constraint first:

```bash
composer require --dev "giorgiosironi/eris:<target-derived-constraint>" --dry-run
composer why-not giorgiosironi/eris <newest-compatible-version>
```

Run the confirmed requirement without `--dry-run`. For a real multi-runtime library,
verify every maintained lock. Use a minimal union only when Composer must select a
legacy Eris line on an older PHP/PHPUnit leg and a modern line elsewhere. In that
case, write property tests against APIs present in both resolved lines, or keep an
explicit version-specific PBT runner. Do not drop a supported PHP version or weaken
the PHPUnit constraint just to install Eris.

## Design Generators for the Domain

Start with Eris generators and compose them with maps, tuples, vectors, sets, and
dependent generation. Create a named custom generator when a domain type has
non-trivial validity rules. Generate valid structures directly instead of
generating arbitrary values and discarding most of them with `when()` or
`suchThat()`; excessive discards reduce useful evaluations and can violate Eris's
minimum evaluation ratio.

Keep these values reachable where the contract allows them:

- empty, singleton, minimum, maximum, and just-outside-boundary values;
- duplicate and reordered collections;
- Unicode, invalid encodings, reserved tokens, and escaped values;
- nullability and omitted versus explicit default fields; and
- sequences that revisit a state or repeat an operation.

Preserve shrinking. Mapping a generated primitive into a domain value is useful
only when failures can still shrink toward a simpler valid value. Avoid opaque
Faker calls, time-dependent data, global randomness, or generators that throw away
the relationship between a value and its shrink path.

For stateful properties, generate commands allowed by the current model state and
compare the model with the system after every command. Reset the system before each
generated sequence. Eris may re-evaluate the property during shrinking, so the
callback must be deterministic and safe to repeat.

## Write an Actionable Property Test

Modern Eris integrates through `Eris\TestTrait`. Use the generator API supplied by
the installed release. A cross-version shape is:

```php
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @group pbt
 */
#[Group('pbt')]
final class CodecPropertyTest extends TestCase
{
    use TestTrait;

    public function testDecodeInvertsEncode(): void
    {
        $this
            ->limitTo(500)
            ->forAll(Generators::string())
            ->then(function (string $value): void {
                self::assertSame($value, Codec::decode(Codec::encode($value)));
            });
    }
}
```

Adapt names, generator, property, and budget to the target. `limitTo()` is useful
for source compatibility across Eris lines. On a modern-only project, the
installed release's `ErisRepeat` attribute may express the same iteration limit.
Do not use an annotation or attribute that the resolved legacy line does not
provide.

Use `#[Group('pbt')]` with modern PHPUnit. Use `@group pbt` with PHPUnit 9. A suite
that genuinely executes the same source under both generations needs both forms,
consistent with the project's existing cross-version metadata policy. Add the
required `#[CoversClass]`, `#[CoversFunction]`, `#[CoversNothing]`, and legacy
coverage tags when the strict PHPUnit configuration requires them.

Failure messages must identify the property and any context not already printed by
Eris. Do not dump hundreds of generated cases. Eris prints an `ERIS_SEED` command
for reproduction and shrinks assertion failures; preserve that output in CI logs.

## Set an Explicit Budget

Eris defaults to 100 evaluations per `forAll()` call, but the right budget depends
on generator breadth and evaluation cost. Set an explicit repeat/limit so runtime
does not drift with framework defaults. Pure in-memory properties can normally
afford more evaluations than database, filesystem, or process properties. Measure
locally and leave CI headroom; also bound shrinking time when the installed Eris
release supports it and shrinking can invoke expensive side effects.

Do not pin a permanent seed in the normal CI run. A fresh seed explores new values;
a failing run prints the seed needed to reproduce it. Pin `ERIS_SEED` only while
reproducing or verifying a known failure. If replay is inconsistent, fix leaked
state, time, locale, external data, or other nondeterminism rather than keeping a
single green seed forever.

## Isolate PBT from Ordinary Tests

PBT must have its own `pbt` group and Composer command. Exclude that group from
every ordinary test entry point, including version-specific PHPUnit scripts and
ParaTest. Add or merge scripts in the target project, for example:

```json
{
    "scripts": {
        "test": "paratest --processes=auto --exclude-group=pbt",
        "test:unit": "phpunit --testsuite unit --exclude-group=pbt",
        "test:pbt": "@php -d memory_limit=512M vendor/bin/phpunit --group=pbt"
    }
}
```

Preserve existing configuration paths, memory limits, test suites, and
version-specific wrappers. The invariant is:

- normal commands contain `--exclude-group=pbt` and run zero PBT tests; and
- `test:pbt` contains `--group=pbt` and runs at least one PBT test.

Do not put a global XML `<exclude><group>pbt</group></exclude>` into a configuration
also used by `test:pbt`; combined include/exclude selection can make the dedicated
command run zero tests. A separate `tests/Property/` directory or testsuite is
useful for ownership, but the group remains required because command and CI
isolation must not depend on a directory convention alone.

Do not add PBT to `lint`, the normal test matrix step, or the ParaTest aggregate.
PBT runs independently so ordinary feedback stays fast and a property failure is a
distinct CI signal.

## GitHub Actions

Read `pbt.yml` from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-pbt/` and apply it as
`.github/workflows/pbt.yml`. Replace every `REPLACE_WITH_*` sentinel from the
target's PHP support, extensions, lock policy, working directory, default branch,
and measured PBT runtime.

Run the PBT workflow on pull requests and pushes to the default branch so it is a
real quality gate, plus `workflow_dispatch` for seed replay. One selected PHP
runtime is normally enough because the job checks domain properties, while the
ordinary matrix checks runtime compatibility. Use a PBT matrix only when the
property itself covers version-dependent behavior or different dependency graphs
implement meaningfully different contracts.

Keep the manual seed as an environment variable and validate it before execution.
An empty seed must leave Eris free to choose a new one. Pin actions to full commit
SHAs, grant only `contents: read`, and give the job a measured timeout. Add service
containers only for properties that genuinely require them, with fresh synthetic
state for every property evaluation or sequence.

## Triage a Failure

1. Copy the `ERIS_SEED=...` command printed by Eris and replay only the failing
   property with the same PHP, dependency lock, locale, and service versions.
2. Confirm the displayed shrunk value still violates the stated contract.
3. Decide whether the product, property, generator, normalization rule, or state
   reset is wrong.
4. Fix the cause. Do not filter the counterexample out or lower the evaluation
   count merely to make CI green.
5. Add the minimized counterexample as a deterministic example test when it
   documents a valuable boundary, while retaining the broader property.
6. Rerun with the failing seed and again without a seed to resume exploration.

## Verification

Run the exact commands the target and CI use:

```bash
composer validate --strict --no-check-publish
composer test:unit
composer test:pbt
```

Also verify:

- the normal test command reports zero tests from the `pbt` group;
- `composer test:pbt` reports at least one property test and more than a token
  assertion count from generated evaluations;
- a disposable failing property prints a replayable `ERIS_SEED`, shrinks, and
  fails the dedicated command; remove the probe afterward;
- replaying that seed reproduces the same counterexample;
- the ordinary suite, dedicated PBT suite, and every maintained legacy/modern
  PHPUnit configuration remain valid;
- `git diff --check` passes and actionlint validates `.github/workflows/pbt.yml`;
  and
- no installed file retains a `REPLACE_WITH` sentinel.

## References

- [Eris repository](https://github.com/giorgiosironi/eris) — Current compatibility, `TestTrait`, generators, attributes, and examples.
- [Eris reproducibility](https://eris.readthedocs.io/en/latest/reproducibility.html) — `ERIS_SEED` failure replay.
- [Eris shrinking](https://eris.readthedocs.io/en/latest/shrinking.html) — Counterexample reduction.
- [Eris runtime limits](https://eris.readthedocs.io/en/latest/limits.html) — Iteration, duration, evaluation ratio, and generated-size limits.
- [PHPUnit test groups](https://docs.phpunit.de/en/13.0/textui.html#test-selection-options) — `--group` and `--exclude-group` selection.
