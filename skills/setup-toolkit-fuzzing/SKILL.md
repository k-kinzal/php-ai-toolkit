---
name: setup-toolkit-fuzzing
description: >-
  Set up contract-driven fuzzing for a PHP project, including domain-aware input
  generation, meaningful oracles, reproducible corpora and crashes, Composer
  commands, and a separate scheduled GitHub Actions workflow. Use for parsers,
  protocols, SQL, HTTP endpoints, file formats, adapters, or other surfaces where
  coverage-guided input exploration is useful. Use setup-toolkit-pbt instead when
  structured values and shrinkable properties are the better fit.
---

# Set Up Contract-Driven Fuzzing

Fuzzing is useful only after the contract under test is explicit. Do not add a
loop that feeds arbitrary strings to an arbitrary method and calls a lack of
crashes success.

The design in `k-kinzal/ztd-query-php` is the reference shape: fuzzer bytes select
and seed grammar-aware SQL generation, robustness targets check invariants, and
correctness targets compare an adapter with a native database. Its exact SQL
generators, database versions, dependencies, run counts, and workflow topology
are examples, not defaults for another project.

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

## Define the Contract First

For each target, identify all of the following before implementing it:

| Question | Required decision |
|----------|-------------------|
| Surface | The exact parser, public method, HTTP route, adapter, query pipeline, file reader, or state transition exercised |
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

### Make Fuzzer Bytes Useful

The target must be deterministic for the same input and environment. Raw fuzzer
bytes may be consumed directly, used to select generator branches and complexity,
or converted to a reproducible seed. Prefer consuming bytes across structural
choices because nearby mutations can then produce nearby cases. A whole-input hash
or CRC seed is an acceptable initial bridge to an existing seeded generator, as in
`ztd-query-php`, but verify that the corpus actually grows and coverage improves;
an avalanche hash can discard useful mutation locality.

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

"No crash" is sufficient only for a deliberately named robustness target. It is
not a correctness oracle.

Classify expected rejections by stable exception type, error code, response class,
or documented predicate. Do not catch `Throwable`, all database errors, or all 4xx
responses and return. In PHP-Fuzzer, ordinary exceptions are ignored while
`Error` represents a finding, so convert unexpected exceptions and explicit
contract mismatches to an `Error` that prints the target name, fuzzer input or
seed, generated domain input, and relevant environment versions. Keep the allowed
rejection list narrow and explain each entry.

Reset mutable state in `finally` after every input. For stateful sequences, reset
between sequences, not between operations within the sequence. A crash must not be
caused by state leaked from an unrelated previous input.

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
useful cases, and add a dictionary for syntax handled outside instrumented PHP
code. Keep setup outside the per-input callable; keep mutable target state inside
the reset boundary.

## Project Layout and Commands

Use a dedicated top-level `fuzz/` tree unless the project has an established
equivalent:

```text
fuzz/
|-- <target>.php
|-- <Contract>/...          target-specific generators, models, and comparators
`-- corpus/<target>/...     a small reviewed seed corpus plus generated entries
```

Add target support classes to `autoload-dev` with a project-derived namespace and
run `composer dump-autoload`. Commit a small corpus containing representative
valid, invalid, empty, boundary, and previously failing inputs. Let local and CI
runs evolve it. Ignore generated corpus entries and root `crash-*` files without
untracking the reviewed seeds; force-add a deliberately promoted seed when needed.
Never commit credentials, production data, access tokens, or sensitive HTTP
responses in a corpus or crash artifact.

Add one Composer script per contract and an aggregate only when running all targets
locally is useful:

```json
{
    "scripts": {
        "fuzz": [
            "@fuzz:REPLACE_WITH_CONTRACT"
        ],
        "fuzz:REPLACE_WITH_CONTRACT": "php-fuzzer fuzz fuzz/REPLACE_WITH_TARGET.php fuzz/corpus/REPLACE_WITH_CONTRACT/"
    }
}
```

Keep fuzzing out of `test`, `test:unit`, `lint`, and ParaTest scripts. A bounded
local smoke command may be added as `fuzz:smoke`, but it is not a substitute for
the scheduled campaign.

## GitHub Actions

Read `fuzz.yml` from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-fuzzing/` and apply it as a
separate `.github/workflows/fuzz.yml`. Do not merge an unbounded or scheduled fuzz
campaign into ordinary pull-request tests.

Replace every `REPLACE_WITH_*` sentinel from the target project. Expand the target
matrix so each entry has one contract, corpus, and required extension set. Add
service/container setup before the fuzzer when the oracle needs a real database or
HTTP service, and put supported server versions in the matrix only when the
contract differs by version.

The workflow must:

- run on a deliberate schedule and `workflow_dispatch`, normally on one selected
  fuzz-tool PHP runtime rather than the compatibility matrix;
- validate the manual run budget before passing it to a shell command;
- restore and save each target's corpus with separate keys;
- upload crash files even after the fuzzer step fails;
- use job timeouts as a second bound in addition to `--max-runs` or the selected
  engine's time limit;
- pin external actions to full commit SHAs, use `contents: read`, and avoid write
  permissions unless the user explicitly asks for issue creation or another
  mutation; and
- use disposable local services and non-secret synthetic fixtures.

Scheduled workflows run from the default branch, so the schedule becomes active
only after that workflow exists there. Choose a minute away from the start of the
hour to reduce scheduler congestion. Keep `workflow_dispatch` so a crash fix, new
corpus, or changed generator can be exercised immediately.

## Failure Triage and Regression

Reproduce a PHP-Fuzzer finding with the same environment, then minimize it:

```bash
vendor/bin/php-fuzzer run-single fuzz/REPLACE_WITH_TARGET.php crash-REPLACE_WITH_HASH
vendor/bin/php-fuzzer minimize-crash fuzz/REPLACE_WITH_TARGET.php crash-REPLACE_WITH_HASH
```

Confirm the minimized input fails repeatedly. Determine whether it is a product
bug, generator bug, invalid oracle, resource-limit breach, or intentionally allowed
rejection. Fix the cause; do not broaden a catch block or allowed-error list merely
to turn the campaign green. Promote the minimized case to a deterministic
regression test or reviewed seed corpus, then keep the fuzz target so related cases
remain discoverable.

## Verification

Before completing setup:

1. Run each entry point once with a tiny bounded budget and confirm it executes the
   intended production code.
2. Give the target a disposable known-bad oracle or input and confirm the process
   exits non-zero and writes a reproducible crash; remove the probe afterward.
3. Run `run-single` on that crash and verify the diagnostic contains the contract,
   domain input or seed, and environment version.
4. Confirm state is reset by running the same corpus twice in one process.
5. Check that reviewed seeds are tracked, generated corpus and crashes are ignored,
   and no sensitive data is present.
6. Run `composer validate --strict --no-check-publish`, the normal unit suite, and
   every bounded fuzz Composer command.
7. Run `git diff --check` and validate `.github/workflows/fuzz.yml` with actionlint.
8. Search for remaining sentinels; any `REPLACE_WITH` in installed project files is
   a failed setup.

## References

- [ztd-query-php fuzz targets and workflows](https://github.com/k-kinzal/ztd-query-php) — Grammar-aware SQL generation, invariants, differential database oracles, corpus caching, and crash artifacts.
- [PHP-Fuzzer](https://github.com/nikic/PHP-Fuzzer) — Target API, corpus, dictionary, crash minimization, and coverage reports.
- [GitHub Actions workflow syntax](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax) — Schedules, manual inputs, permissions, concurrency, and job timeouts.
- [GitHub Actions security hardening](https://docs.github.com/en/actions/how-tos/security-for-github-actions/security-guides/security-hardening-for-github-actions) — Action pinning and least privilege.
