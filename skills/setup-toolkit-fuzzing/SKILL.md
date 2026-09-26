---
name: setup-toolkit-fuzzing
description: >-
  Set up contract-driven fuzzing for a PHP project, including domain-aware input
  generation, meaningful oracles, reproducible corpora and crashes, Composer
  commands, and a separate scheduled GitHub Actions workflow that runs on the
  default branch and reports findings as issues. Use for parsers, protocols, SQL,
  HTTP endpoints, file formats, adapters, or other surfaces where coverage-guided
  input exploration is useful. Use setup-toolkit-pbt instead when structured
  values and shrinkable properties are the better fit.
---

# Set Up Contract-Driven Fuzzing

Fuzzing is useful only after the contract under test is explicit. Do not add a
loop that feeds arbitrary strings to an arbitrary method and calls a lack of
crashes success.

The design in `k-kinzal/ztd-query-php` is the reference shape: every fuzz target
is one entry point and one target class, SQL Faker compiles fuzzer bytes into
generation plans, native databases and independent models serve as oracles, and
each package's scheduled workflow opens an issue for a finding. Its exact SQL
generators, database versions, dependencies, run counts, and workflow topology are
examples, not defaults for another project.

## Inspect the Target Project

Read before choosing a tool or editing files:

- `composer.json`, every maintained lock file, PHP constraints, autoload roots,
  test scripts, and development dependencies;
- public API and protocol documentation for the requested surface;
- existing unit, integration, property, fixture, parser, schema, and end-to-end
  tests that reveal accepted and rejected inputs;
- existing fuzz targets, corpora, crash files, dictionaries, generators, service
  containers, and `.gitignore` rules; and
- all GitHub Actions workflows, including their PHP runtime, extensions, service
  versions, lock policy, action pins, permissions, and timeouts.

Preserve unrelated changes. Never point a fuzzer at production or at a shared
environment. Starting a local disposable service is in scope for setup; fuzzing an
external service requires the user's explicit authorization for that exact target.

Keep campaign instructions with an existing development-owned `fuzz/` surface when
needed. Do not add fuzz setup, coverage reports, or reproduction tooling to the
product README or `docs/` unless that documentation is requested.

## Define the Contract First

For each target, identify all of the following before implementing it:

| Question | Required decision |
|----------|-------------------|
| Surface | The exact parser, public method, HTTP route, adapter, query pipeline, file reader, or state transition exercised |
| Kind | A contract target or a property target, as defined below |
| Input domain | Which values are valid, invalid-but-expected, and outside scope; include dialect, protocol, schema, and version |
| Property | What must remain true for every generated case |
| Oracle | How a violation is distinguished from an allowed rejection |
| State | What must be reset before every input and what state transitions are intentionally explored |
| Environment | Runtime, extensions, database/server versions, fixtures, authentication, and resource limits |
| Reproduction | Which bytes, seed, generated request, environment versions, and command are needed to rerun a failure |

Prefer a narrow target with a strong oracle over a broad target whose only check is
"did not crash." Create separate targets when surfaces have different contracts,
generators, state, or oracles. Name Composer scripts and CI matrix entries after
the contract, such as `fuzz:parser:json`, `fuzz:http:orders`, or
`fuzz:sql:select-correctness`.

### Two Kinds of Target

The fuzzer bytes play a different role in each kind, and the target's shape
follows from the kind. Decide it first and name it in the target's docblock.

| Kind | The fuzzer bytes are | The target checks | Allowed exception |
|------|----------------------|-------------------|-------------------|
| Contract | The input itself, or a selector byte plus the input | The product does not misbehave: no exception outside the documented rejection, no fatal error, no timeout, and every invariant the contract still promises for arbitrary input, such as idempotence or a coherent result | Exactly the documented rejection type of invalid input, and nothing else |
| Property | Choices that drive a generator, a plan compiler, or a seed | The product produces the expected result or behavior for the generated case: acceptance by a native parser, byte-for-byte round trip, agreement with a native execution or a reference model | None: the generated input is valid by construction, so every exception is a finding |

A contract target formats arbitrary bytes and reports anything other than the
parser's own rejection, including output that changes when it is formatted again.
A property target compiles the bytes into a grammar derivation and reports a
statement the parser rejects, a tree that does not write back the statement, or a
database that answers the rewritten statement differently from the original. Both
kinds are legitimate; a contract target is not a weaker property target, it
asserts a different contract.

### Choose Fuzzing or PBT Deliberately

Use coverage-guided fuzzing when mutated byte sequences or corpus evolution can
discover new control flow: parsers, decoders, query languages, wire protocols,
file formats, request routing, escaping, and deep state machines are common fits.

Use `/setup-toolkit-pbt` when inputs are naturally structured PHP values, the
important statement is an algebraic, model, round-trip, or state-transition
property, and automatic shrinking will produce a better counterexample. Both may
be appropriate for one component, but they must assert different contracts rather
than duplicate an expensive random loop in two runners.

## Select the Input Mechanism and Oracle Together

Choose the generator from the language the target actually accepts:

| Contract surface | Input mechanism | Strong oracle examples |
|------------------|-----------------|------------------------|
| Raw bytes or a tolerant parser | Coverage-guided byte mutation, a small valid/invalid corpus, and a token dictionary | No engine-level error, bounded resource use, parse/print/parse equivalence |
| SQL or another grammar | Versioned grammar- or AST-based generation; make fuzzer input select productions, complexity, and seed | Native parser/database acceptance, differential results, rewrite equivalence, query-plan invariants |
| OpenAPI HTTP service | An OpenAPI-aware generator when the specification is authoritative; custom schema/state generators otherwise | Status/schema contract, reference model, idempotency, authorization and resource-lifecycle invariants |
| Serializer or codec | Structured value generator plus valid and corrupted encoded corpus | Decode(encode(x)), canonicalization, alternate implementation comparison |
| Database or protocol adapter | Schema-aware operations and disposable real services at supported versions | Native client versus adapter results, errors, transactions, and resulting state |
| Stateful domain workflow | Command generator constrained by the current model state | Model state versus system state after every command; reset per sequence |

For HTTP, generate method, path, headers, authentication state, content type, and
body coherently. Randomizing only the body does not test the HTTP contract. Cover
both specification-valid requests and intentionally invalid classes, and assert
their different expected outcomes. Do not treat every 4xx or 5xx response as
equivalent; classify allowed rejection codes and fail on contract-breaking or
server-error responses.

For SQL, start from the supported dialect and version rather than concatenating
keywords. Reuse an appropriate grammar generator such as SQL Faker when it covers
the target dialect, or build a schema-aware AST generator when semantic validity
matters. Validate grammar-generated SQL against the real database parser before
claiming syntax correctness. For adapters and rewriters, run the same schema,
fixtures, query, and transaction through the native and wrapped paths and compare
normalized results and final state.

A syntax target should reach the server's parser/prepare operation without
executing arbitrary generated statements. Verify the driver does not emulate or
rewrite the input before the server sees it. Use execution and fixture state only
for contracts that actually require them, such as adapter correctness.

### Make Fuzzer Bytes Useful

The target must be deterministic for the same input and environment. Raw fuzzer
bytes may be consumed directly, used to select generator branches and complexity,
or converted to a reproducible seed. Prefer consuming bytes across structural
choices because nearby mutations can then produce nearby cases. When the product
has a plan/compiler API, use it to freeze production choices, values, and budgets;
later random state must not change replay. A whole-input hash or CRC seed is only
a fallback for a generator without structural controls. Verify useful corpus and
coverage growth before treating that bridge as sufficient.

Bound recursion, collection sizes, request sequences, payload length, and per-input
work. Keep boundary values, empty values, malformed encodings, duplicate fields,
ordering changes, and version-specific constructs reachable. Do not make only
happy-path inputs reachable.

## Build a Real Oracle

Use one or more of these oracle forms:

- differential: compare with a native implementation, previous compatible
  version, alternate parser, or reference service;
- round-trip: parse/render/parse or encode/decode while accounting for documented
  normalization;
- metamorphic: apply a transformation that should preserve or predictably change
  behavior;
- model-based: compare every operation and state transition with a small reference
  model;
- invariant: determinism, idempotence, classification/rewrite agreement, row-count
  rules, authorization boundaries, transaction behavior, or bounded resources.

"No crash" is sufficient only for a deliberately named contract target whose
documented rejection is the only allowed exception. It is not a correctness
oracle for a property target.

PHP-Fuzzer treats `Exception` and its subclasses as allowed by default and only
`Error` as a finding. Do not rely on that default: call
`$config->setAllowedExceptions([])` in every entry point so that every exception
escaping the target is a finding, and let the target catch exactly the documented
rejection and return. Convert explicit contract mismatches to an `Error` whose
message names the target, the environment version, the fuzzer input as hex, the
generated domain input, and the expected and actual outcomes. Do not catch
`Throwable`, all database errors, or all 4xx responses and return.

Keep allowed rejections in a readable target-owned table with the reason beside
each code. When one code represents both syntax and semantic failures, use a
source-verified predicate for the allowed case. A dead connection, missing service,
or broken instrumentation is a campaign failure: write the reason to `STDERR` and
`exit(2)` so the process stops with a non-zero status instead of recording the
failure as a product crash or ignoring it as an ordinary exception.

Reset mutable state in `finally` after every input. For stateful sequences, reset
between sequences, not between operations within the sequence. A crash must not be
caused by state leaked from an unrelated previous input.

## Keep the Harness Small

Let the fuzz engine own the campaign loop, input mutation, corpus evolution, crash
files, and minimization. A fuzz target is two files and nothing else:

- the entry point, which composes the environment once and registers the
  per-input closure; and
- the target class, which converts one input into one product operation and
  applies the oracle.

Do not build a second campaign runner, verdict registry, witness database,
acceptance dashboard, generator library, or native extension merely to apply this
skill. Keep reusable planning, generation, and domain coverage in the product that
owns those concepts; the target can enable a product-owned recorder, but it should
not build a parallel coverage model or feed oracle verdicts back into production.
Add custom engine feedback only for demonstrated coverage gaps the normal engine
cannot see.

The entry point holds everything that happens once per campaign: reading the
environment versions, validating them against the supported list, starting a
disposable container, constructing the parser, provider, or connection, and
configuring PHP-Fuzzer. Its per-input closure does one conversion and one
`verify()` call:

```php
<?php

/**
 * PHP-Fuzzer entry point: every statement the grammar generates must parse.
 *
 * Usage:
 *   REPLACE_WITH_VERSION_VARIABLE=<version> vendor/bin/php-fuzzer fuzz fuzz/fuzz_REPLACE_WITH_CONTRACT.php fuzz/corpus/REPLACE_WITH_CONTRACT/
 */

declare(strict_types=1);

use Fuzz\Target\REPLACE_WITH_TARGET_CLASS;

$version = getenv('REPLACE_WITH_VERSION_VARIABLE') !== false ? getenv('REPLACE_WITH_VERSION_VARIABLE') : 'REPLACE_WITH_DEFAULT_VERSION';
if (!in_array($version, REPLACE_WITH_SUPPORTED_VERSIONS, true)) {
    fwrite(STDERR, "Unknown version: {$version}\n");
    exit(1);
}

$generator = REPLACE_WITH_GENERATOR_CONSTRUCTION;
$target = new REPLACE_WITH_TARGET_CLASS(REPLACE_WITH_PRODUCT_CONSTRUCTION, $version);

/** @var PhpFuzzer\Config $config */
$config->setAllowedExceptions([]);
$config->setMaxLen(REPLACE_WITH_MAX_INPUT_LENGTH);
$config->setTarget(static function (string $input) use ($generator, $target): void {
    $target->verify($generator->generate($input), $input);
});
```

The target class exposes one public verification method. A property target takes
the generated domain input and the fuzzer bytes that produced it, so the finding
can be replayed; a contract target takes the bytes alone through `__invoke()`.
The class docblock states the kind, the contract, and what counts as a finding:

```php
<?php

declare(strict_types=1);

namespace Fuzz\Target;

use Error;

/**
 * Parses generated SQL and reports a statement the grammar should have accepted.
 *
 * Property target: every statement is derived from the grammar the parser was
 * built from, so a rejection is a finding, and so is a tree that does not write
 * the statement back byte for byte.
 */
final class REPLACE_WITH_TARGET_CLASS
{
    public function __construct(
        private readonly REPLACE_WITH_PRODUCT_TYPE $product,
        private readonly string $version,
    ) {
    }

    /**
     * @throws Error When the product rejects the input or answers it wrongly
     */
    public function verify(string $generated, string $input): void
    {
        try {
            $actual = $this->product->REPLACE_WITH_OPERATION($generated);
        } catch (REPLACE_WITH_REJECTION_EXCEPTION $rejection) {
            throw new Error(
                "The product rejected generated input\n" .
                "Version: {$this->version}\n" .
                'Input (hex): ' . bin2hex($input) . "\n" .
                "Generated: {$generated}\n" .
                "Error: {$rejection->getMessage()}",
                0,
                $rejection,
            );
        }
        if ($actual !== REPLACE_WITH_EXPECTED) {
            throw new Error("REPLACE_WITH_CONTRACT_STATEMENT\nVersion: {$this->version}\nInput (hex): " . bin2hex($input) . "\nGenerated: {$generated}\nExpected: " . var_export(REPLACE_WITH_EXPECTED, true) . "\nActual: " . var_export($actual, true));
        }
    }
}
```

Replace every symbol from the target project. Keep the target free of a second
random source, a hidden retry, and a broad catch. When the oracle is a real
database, the target owns the connection and recreates the database, user, or
schema before every input; a lost connection is a campaign failure that exits
with status 2, and a rejection is compared by a narrow, documented classification
rather than accepted because both sides threw something.

Verify a thin harness through bounded execution and crash replay rather than
creating a unit/integration suite for its wiring. Test substantive product
algorithms and nontrivial oracle decisions where needed, using visible fixtures
and the test framework's assertions instead of a bespoke testing framework.

## PHP-Fuzzer Setup

PHP-Fuzzer is the normal starting point for coverage-guided PHP targets, not an
universal requirement. Inspect its current release, PHP and `nikic/php-parser`
constraints, target API, CLI, and required extensions at application time. Test
resolution against every lock/runtime that will install development dependencies:

```bash
composer require --dev "nikic/php-fuzzer:<target-derived-constraint>" --dry-run
composer why-not nikic/php-fuzzer <newest-compatible-version>
```

Run the confirmed requirement without `--dry-run`. Do not copy the version from
this toolkit or from `ztd-query-php`. Do not widen the project's parser constraint
or drop supported PHP versions merely to install the fuzzer. When the dependency
graph conflicts, choose a compatible engine or a genuinely isolated fuzz-tool
environment that still loads the target against its real dependencies.

A PHP-Fuzzer entry point must register a `callable(string): void` with
`$config->setTarget()`. Use `setMaxLen()` when larger inputs add cost without adding
useful cases, and raise it when the byte-to-plan mapping consumes more bytes than
the engine's default length; add a dictionary for syntax handled outside
instrumented PHP code. Keep setup outside the per-input callable; keep mutable
target state inside the reset boundary. Pass `--timeout=<seconds>` for targets
whose single input can block on a server, and keep the per-input timeout well
below the job timeout.

When the oracle needs a database, prefer starting a disposable container from the
entry point through Testcontainers over a workflow `services:` block: the same
command then runs locally and in CI, the container version is chosen by the same
environment variable as the grammar version, and every target of the package
shares one workflow shape. Register a shutdown function that clears the
PHP-Fuzzer alarm before the container library registers its own, so the alarm
cannot interrupt teardown:

```php
register_shutdown_function(static function (): void {
    if (function_exists('pcntl_alarm')) {
        pcntl_alarm(0);
    }
});
```

When several packages of one repository need the same containers, keep the
definitions in one shared development-only package instead of repeating them.

## Project Layout and Commands

Use a dedicated top-level `fuzz/` tree unless the project has an established
equivalent:

```text
fuzz/
|-- fuzz_<contract>.php        one entry point per contract
|-- Target/<Name>.php          one target class per contract, namespace Fuzz\Target
|-- corpus/<contract>/         engine-generated, ignored
`-- coverage/<contract>/       optional product-owned coverage snapshots, ignored
```

Map `Fuzz\\` to `fuzz/` in `autoload-dev` and run `composer dump-autoload`. Add
`fuzz` to the PHPStan `paths` and `Fuzz` to the toolkit's
`visibilityExemptNamespacePrefixes` so the harness is analysed on every pull
request with the same rules as the tests; PHP-CS-Fixer's finder already covers it
when it starts at the project root. Add `/fuzz/ export-ignore` to
`.gitattributes` with the other development files.

Do not manufacture fixed binary seeds or add a corpus-preparation program just to
populate a directory: an engine-generated corpus is sufficient when the
byte-to-plan mapping already reaches useful cases, and a tiny structured target
may need no corpus directory at all. When a product ships reviewed seeds as an
asset of its own, the workflow copies them into the corpus before the run; they
are not tracked under `fuzz/corpus/`. Ignore everything the engine writes:

```gitignore
/fuzz/corpus/
/fuzz/coverage/
/crash-*
/minimized-*
/timeout-*
/oom-*
```

Never commit credentials, production data, access tokens, or sensitive HTTP
responses in a corpus or crash artifact.

Add one Composer script per contract, a bounded `fuzz:smoke` that runs each of
them, and make `fuzz` the smoke run so that the plain command is always safe to
type. Disable Composer's process timeout on every script, create the corpus
directory before the engine reads it, and describe each script:

```json
{
    "scripts": {
        "fuzz": "@fuzz:smoke",
        "fuzz:REPLACE_WITH_CONTRACT": [
            "Composer\\Config::disableProcessTimeout",
            "@php -r \"is_dir('fuzz/corpus/REPLACE_WITH_CONTRACT') || mkdir('fuzz/corpus/REPLACE_WITH_CONTRACT', 0777, true);\" --",
            "php-fuzzer fuzz fuzz/fuzz_REPLACE_WITH_CONTRACT.php fuzz/corpus/REPLACE_WITH_CONTRACT/"
        ],
        "fuzz:smoke": [
            "Composer\\Config::disableProcessTimeout",
            "@fuzz:REPLACE_WITH_CONTRACT --max-runs=100"
        ]
    },
    "scripts-descriptions": {
        "fuzz": "Run every fuzz target with the bounded smoke budget.",
        "fuzz:smoke": "Run each fuzz target for up to 100 executions; individual fuzz:* commands run continuously."
    }
}
```

Leave a target out of `fuzz:smoke` only when it needs a server the entry point
cannot start itself, and say so in the description. Keep fuzzing out of `test`,
`test:unit`, `lint`, and ParaTest scripts. The smoke command verifies the harness
locally; it is not a substitute for the scheduled campaign.

## GitHub Actions

Read `fuzz.yml` from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-fuzzing/` and apply it as a
separate `.github/workflows/fuzz.yml`. In a monorepo, install one workflow per
package, named `Fuzz (<package>)`, with `defaults.run.working-directory` set to
the package. Do not merge a fuzz campaign into ordinary pull-request tests.

The campaign runs on the default branch only: the workflow has a schedule and
`workflow_dispatch`, and the job carries `if: github.ref == 'refs/heads/<default>'`
so a dispatch from another branch does nothing. Pull requests check the harness
through the lint job, which analyses `fuzz/` with PHPStan and PHP-CS-Fixer, and
through the local `fuzz:smoke` command; they do not run the campaign.

The run's colour has one meaning, and the workflow must keep it:

| Outcome | Run status | Where the result goes |
|---------|------------|-----------------------|
| The campaign ran and found nothing | Green | The corpus cache and the log artifact |
| The campaign ran and found a crash | Green | An issue opened by the workflow, plus the crash file, log, and corpus as artifacts |
| The campaign could not run: a dead database, an unknown version, a usage error, a `FuzzerException`, broken instrumentation, or a killed process | Red | The job log |

PHP-Fuzzer separates the two at the process level: `php-fuzzer fuzz` records a
finding as a `crash-*` file and exits zero, while a non-zero exit means the
campaign itself was impeded. Do not wrap the fuzzer in exit-code handling that
turns a finding into a step failure or a step failure into success, and do not
make a finding red: a red fuzz run must always mean "fix the campaign", so that a
red run is never ignored as "just another crash".

Findings are reported as issues. The issue step reads the fuzzer log, finds the
`CRASH in <file>!` line, hashes the crash input, and creates an issue carrying a
marker comment with that hash, the commit, the run, the artifact name, the exact
`run-single` command, and the tail of the fuzzer output. A second run that finds
the same input finds the marker in an open issue and creates nothing. The job
needs `issues: write` for that step alone; keep the workflow at
`contents: read`. Ask before installing the workflow only when the repository
does not want issues opened by automation; otherwise the issue step is part of the
setup, not an optional extra.

Replace every `REPLACE_WITH_*` sentinel from the target project. Expand the target
matrix so each entry has one contract, corpus, and required extension set. Put
supported server versions in the matrix only when the contract differs by
version, and expose them as `workflow_dispatch` inputs when a manual run should be
able to select one.

The workflow must:

- run on a deliberate schedule and `workflow_dispatch`, normally on one selected
  fuzz-tool PHP runtime rather than the compatibility matrix; when that runtime
  lies above the locked development graph's declared range, check platform
  requirements with `--no-dev` and name the step accordingly;
- validate the manual run budget before passing it to a shell command;
- restore and save each target's corpus under a key that ends in the run id and
  attempt, with a `restore-keys` prefix that picks up the newest previous run;
  bump the version segment of the prefix when the byte-to-input mapping changes,
  because the old corpus then no longer means anything;
- write the fuzzer output through `tee` to a log the issue step can read, under
  `set -euo pipefail` so an impeded campaign still fails the step;
- upload crash files, the log, and the corpus whether the fuzzer step succeeds or
  fails, with a retention that outlives the next scheduled run;
- use job timeouts as a second bound in addition to `--max-runs`, a per-input
  `--timeout` for targets that can block, and `cancel-in-progress: false` so a
  dispatch never kills the scheduled campaign; and
- pin external actions to full commit SHAs and use disposable local services and
  non-secret synthetic fixtures.

Scheduled workflows run from the default branch, so the schedule becomes active
only after that workflow exists there. Choose a minute away from the start of the
hour to reduce scheduler congestion, and stagger the packages of a monorepo. Keep
`workflow_dispatch` so a crash fix, new corpus, or changed generator can be
exercised immediately.

## Failure Triage and Regression

The issue is the entry point of triage. Download the artifact it names, then
reproduce the finding with the same environment and minimize it:

```bash
vendor/bin/php-fuzzer run-single fuzz/fuzz_REPLACE_WITH_CONTRACT.php crash-REPLACE_WITH_HASH.txt
vendor/bin/php-fuzzer minimize-crash fuzz/fuzz_REPLACE_WITH_CONTRACT.php crash-REPLACE_WITH_HASH.txt
```

Confirm the minimized input fails repeatedly. Determine whether it is a product
bug, generator bug, invalid oracle, resource-limit breach, or intentionally allowed
rejection. Fix the cause; do not broaden a catch block or allowed-error list merely
to make the finding disappear. Promote the minimized case to a deterministic
regression test, close the issue with the fix, and keep the fuzz target so related
cases remain discoverable. Leave the marker in the closed issue: the next run
compares against open issues only, so a regression of the same input opens a new
one.

## Verification

Before completing setup:

1. Run each entry point once with a tiny bounded budget and confirm it executes the
   intended production code.
2. Give the target a disposable known-bad oracle or input and confirm the run
   prints `CRASH in <file>!` and writes a reproducible `crash-*` file while the
   process still exits zero. Remove the probe afterward.
3. Run `run-single` on that crash and verify the diagnostic contains the contract,
   the input as hex, the generated domain input, and the environment version.
4. Stop the disposable service, or point the entry point at a closed port, and
   confirm the run exits non-zero with the reason on `STDERR` rather than writing
   a crash file. Restore the service afterward.
5. Confirm state is reset by running the same corpus twice in one process.
6. Check that the generated corpus, coverage snapshots, and crash files are
   ignored, `fuzz/` is export-ignored, and no sensitive data is present.
7. Run `composer validate --strict --no-check-publish`, `composer phpstan`,
   `composer format:check`, the normal unit suite, and `composer fuzz`.
8. Run `git diff --check` and validate `.github/workflows/fuzz.yml` with actionlint.
   The issue step cannot be exercised locally; read it against the log format
   the probe printed in step 2.
9. Search for remaining sentinels; any `REPLACE_WITH` in installed project files is
   a failed setup.

## References

- [ztd-query-php fuzz targets and workflows](https://github.com/k-kinzal/ztd-query-php) — Entry point and target pairs, grammar-aware SQL generation, native and model oracles, corpus caching, and issue reporting.
- [PHP-Fuzzer](https://github.com/nikic/PHP-Fuzzer) — Target API, corpus, dictionary, crash minimization, and coverage reports.
- [GitHub Actions workflow syntax](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax) — Schedules, manual inputs, permissions, concurrency, and job timeouts.
- [GitHub Actions security hardening](https://docs.github.com/en/actions/how-tos/security-for-github-actions/security-guides/security-hardening-for-github-actions) — Action pinning and least privilege.
