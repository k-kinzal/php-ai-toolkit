---
name: setup-toolkit-phpbench
description: >-
  Set up reproducible PHPBench benchmarks in a PHP project, including benchmark
  design, shared Composer commands, PHPBench configuration, and a pull-request
  workflow that compares the merge target with the candidate on one runner.
  Use when adding, standardizing, or gating PHP performance benchmarks.
---

# Set Up PHPBench

This skill makes benchmarks useful for both local investigation and pull-request
decisions. A benchmark is a performance contract over a stable workload, not a
fast test suite or a one-off timing script.

The PHPBench setups in `k-kinzal/ztd-query-php` and `k-kinzal/peq` show the two
layouts this skill commonly encounters. Treat their subjects and revolution
counts as project-specific evidence, not defaults to copy.

## Inspect the Target

Read before editing:

- `composer.json`, every maintained lock, PHP constraints, autoload roots, and
  existing benchmark or test scripts;
- `phpbench.json`, `phpbench.json.dist`, `bench/`, and `tests/Benchmark/` when
  present;
- the production path and realistic inputs whose performance matters;
- fixtures, random generators, clocks, caches, files, databases, containers, and
  network calls that can change the measured workload; and
- every GitHub Actions workflow, including runner image, PHP version, extensions,
  services, lock policy, action pins, permissions, and timeouts.

Preserve an established valid layout when standardizing an existing suite. For a
new suite, use the top-level `bench/` layout from this skill. Preserve unrelated
changes.

## Define the Performance Contract

For every subject, determine:

| Decision | Required evidence |
|----------|-------------------|
| Operation | The exact public operation or algorithm whose cost matters |
| Workload | Fixed, representative inputs and their size or complexity |
| Boundary | Which construction, parsing, I/O, cache warm-up, and cleanup are inside the measurement |
| State | What is rebuilt before each iteration and what is deliberately reused |
| Metric | Normally elapsed time; add memory or throughput only when it expresses the contract |
| Stability | Enough work per iteration and enough iterations to distinguish signal from noise |
| CI role | Local-only, PR comparison, or a required relative/absolute gate |

Keep different workload sizes as named parameter sets. Do not replace a realistic
workload with a tiny input solely to make CI fast. If the full workload is too
slow for pull requests, keep a representative PR group and run the complete group
manually or on a deliberate schedule.

Microbenchmarks over deterministic in-process code are suitable for the default
hosted-runner gate. Filesystem, network, database, process, and container-startup
benchmarks include host contention and service variance; make them informational
on hosted runners. Require them only on a controlled self-hosted runner with
versioned images, pre-pulled dependencies, synthetic fixtures, explicit health
checks, and cleanup outside the measured region.

## Install PHPBench

Inspect PHPBench's current release and PHP compatibility at application time, then
select the newest release compatible with every development-dependency runtime
that installs the benchmark suite:

```bash
composer require --dev "phpbench/phpbench:<target-derived-constraint>" --dry-run
composer why-not phpbench/phpbench <newest-compatible-version>
```

Run the confirmed requirement without `--dry-run`. Do not copy a version from this
toolkit or another repository, widen the target's PHP constraint, or remove a
supported runtime merely to install PHPBench. Preserve an intentional existing
constraint and update its lock within that constraint.

## Layout and Configuration

For a new setup, use:

```text
bench/
|-- <Capability>Bench.php
`-- <Capability>/...       benchmark-owned fixtures or support classes, if needed
phpbench.json.dist
```

Add a project-derived PSR-4 development mapping for benchmark support classes
when they need autoloading. `Bench\\` to `bench/` is the default for a new suite;
do not overwrite existing `autoload-dev` mappings. Run `composer dump-autoload`
after changing them.

Read the configuration template from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpbench/phpbench.json.dist`
and install it at the project or package root. If configuration already exists,
merge deliberately. Keep the local schema path so editor validation follows the
installed PHPBench version. Add `phpbench.json` and `build/phpbench/` to
`.gitignore`; the committed `.dist` file remains the shared configuration.
Keep `storage.xml_storage_path` at `build/phpbench/storage` when using the shipped
workflow, or update every corresponding transfer and Artifact path together.

The template intentionally sets stable full-run defaults but no global
revolutions. Choose revolutions per subject because a parser call and a container
startup cannot share a useful value. Adjust memory limits and PHP settings only
from the target workload; keep the same settings for the merge target and
candidate.

Add these Composer scripts, merging with existing scripts:

```json
{
    "scripts": {
        "bench": "phpbench run --report=aggregate",
        "bench:quick": "phpbench run --report=aggregate --warmup=1 --iterations=3 --retry-threshold=10"
    }
}
```

`composer bench` is the review-quality local run. `composer bench:quick` is fast
feedback while authoring a subject. Keep both outside `test`, `test:unit`,
`lint`, and ParaTest scripts.

## Write Benchmark Subjects

Use PHP 8 attributes rather than legacy annotations for new subjects:

```php
<?php

declare(strict_types=1);

namespace Bench;

use PhpBench\Attributes as Bench;

final class ParserBench
{
    private Parser $parser;

    private string $input = 'REPLACE_WITH_REPRESENTATIVE_INPUT';

    public function setUp(): void
    {
        $this->parser = new Parser();
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(100)]
    public function benchParse(): void
    {
        $this->parser->parse($this->input);
    }
}
```

Replace every example symbol and value. Then apply these rules:

- Put construction and fixture preparation outside the measured method unless
  their cost is the contract. Hooks are not PHPUnit hooks; declare them with the
  PHPBench attributes the installed version supports.
- Use fixed representative data and fixed seeds. Never call unseeded Faker,
  depend on wall-clock time, or discover a changing source tree inside the
  measured operation.
- Reset mutable state before each iteration. Do not let one revolution make later
  revolutions cheaper unless warmed state is the contract being named.
- Make the result observable when the optimizer or underlying extension could
  otherwise eliminate work. Do not add logging or broad correctness assertions
  to the timed body.
- Use `ParamProviders` for meaningful workload classes. Keep provider keys stable
  because PHPBench uses the subject and parameter set to pair baseline variants.
- Give cheap subjects enough revolutions for a useful timing interval. Use one
  revolution for expensive end-to-end operations. Increase iterations or improve
  isolation when relative standard deviation remains high; do not enlarge the
  regression tolerance merely to make an unstable benchmark pass.
- Verify correctness with deterministic tests. PHPBench should measure a known-
  correct path, not become the only test of that path.

Add an absolute `Bench\Assert` only for an actual performance budget such as an
SLA. The shared CI comparison supplies a relative assertion, so do not duplicate
that threshold on every subject.

## Pull-Request Comparison and Gate

Read `bench.yml` from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpbench/` and install it as
the separate `.github/workflows/bench.yml`. Do not put benchmarks in the normal
test matrix: compatibility tests answer a different question and concurrent jobs
make timing noisier.

Replace every `REPLACE_WITH_*` sentinel from target-project evidence. Select one
production-representative supported PHP version rather than a version matrix.
Match the target's Composer lock mode, extensions, working directory, and
benchmark path. Keep the fixed 10% relative-mode threshold unless the project has
a documented performance budget that justifies a stricter threshold. Choose
`PERFORMANCE_GATE=true` only for stable in-process benchmarks; otherwise retain
the comparison and Artifact in report-only mode.

The workflow deliberately remeasures both revisions in one job:

1. Check out `github.event.pull_request.base.sha`, the current merge-target tip.
2. Check out `github.sha`, GitHub's synthetic merge commit for the candidate.
3. Install each revision independently under the same PHP and runner.
4. Store the merge-target run as PHPBench tag `baseline`.
5. Copy that XML storage to the candidate and run with `--ref=baseline`.
6. Show PHPBench's aggregate diff in the job summary and upload raw XML, the full
   console report, storage, revisions, and tool versions as one Artifact.

Do not download the "latest main" Artifact as the numerical baseline. Artifacts
come from different machines and times, expire, and are awkward to identify after
reruns. They are valuable evidence for review and diagnosis; the same-run
remeasurement is the comparison input.

The relative gate uses:

```text
mode(variant.time.avg) <= mode(baseline.time.avg) +/- 10%
```

PHPBench applies it to each comparable variant and exits non-zero on regression.
The aggregate report still appears in the job summary, and the evidence upload
runs even when the assertion fails. A positive slowdown requires investigation;
a high `rstdev` means the benchmark needs stabilization before it can support a
merge decision.

Benchmark sources, benchmark-owned fixtures, and `phpbench.json.dist` must be
identical on both sides of a gated comparison. The template reports an initial
setup PR as candidate-only because no merge-target suite exists yet. Later harness
or PHPBench-version changes are non-comparable and fail when gate mode is enabled;
review them as a calibration change, then re-enable the required gate after that
change reaches the target branch. Protect workflow and benchmark changes with the
project's normal ownership review because a PR can otherwise weaken its own
measurement or threshold.

The default workflow uses only `contents: read`, writes the report to
`GITHUB_STEP_SUMMARY`, and works for forked PRs without secrets. Do not switch to
`pull_request_target` to run benchmark code or grant pull-request write permission
merely to post a comment. If a repository later requires a conversation comment,
publish the already-created Artifact from a separate trusted workflow without
executing PR code under a write token.

## Verification

Before completing setup:

1. Run `composer validate --strict --no-check-publish` and `composer dump-autoload`.
2. Run `composer bench:quick` and `composer bench`; confirm every intended subject
   and parameter set appears and no unrelated test is discovered.
3. Run the suite repeatedly and inspect `rstdev`. Stabilize noisy subjects before
   enabling the gate.
4. Make a disposable subject slower by more than 10%, confirm the baseline
   assertion exits non-zero, and remove the probe.
5. Confirm `build/phpbench/` and `phpbench.json` are ignored while
   `phpbench.json.dist` and benchmark sources are tracked.
6. Run `git diff --check` and validate `.github/workflows/bench.yml` with
   actionlint.
7. Search installed files for `REPLACE_WITH`; a remaining sentinel is a failed
   setup.

## References

- [PHPBench quick start](https://phpbench.readthedocs.io/en/latest/quick-start.html) — Configuration, subjects, stability, and reports.
- [PHPBench regression testing](https://phpbench.readthedocs.io/en/latest/guides/regression-testing.html) — Tagged baselines, aggregate diffs, and assertions.
- [PHPBench assertions](https://phpbench.readthedocs.io/en/latest/guides/assertions.html) — Metrics, aggregation functions, and expression syntax.
- [PHPBench storage](https://phpbench.readthedocs.io/en/latest/guides/storage.html) — XML storage, tags, and later reporting.
- [GitHub Actions job summaries](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-commands#adding-a-job-summary) — `GITHUB_STEP_SUMMARY` behavior.
- [GitHub Actions artifacts](https://docs.github.com/en/actions/how-tos/writing-workflows/choosing-what-your-workflow-does/storing-and-sharing-data-from-a-workflow) — Upload, retention, and access.
- [GitHub Actions security hardening](https://docs.github.com/en/actions/how-tos/security-for-github-actions/security-guides/security-hardening-for-github-actions) — Least privilege and full-SHA action pinning.
