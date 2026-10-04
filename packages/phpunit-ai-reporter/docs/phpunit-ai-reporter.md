# PHPUnit AI Reporter

php-ai-toolkit provides a dual-mode PHPUnit reporter for failures, unexpected errors, and risky tests. It turns test
results into location-first diagnostics for coding agents while preserving PHPUnit's normal terminal experience for
people.

## Supported Adapters

The reporter has two adapters over one shared issue model and renderer:

- `Toolkit\PhpUnit\TestReporter\AiTestReporterExtension` uses the PHPUnit 10.5-or-later event extension API.
- `Toolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener` uses the PHPUnit 9.6 listener API.

Both collect the same issue categories and produce the same report shape. PHPUnit warnings, skipped tests, and
incomplete tests are not duplicated by the reporter; PHPUnit continues to own those results.

## Mode Detection

The reporter shares its agent detector with the PHPStan AI formatter. AI mode is selected for recognized agent
environments, including a non-empty `AI_AGENT` value and markers for Claude Code, Cursor, Gemini CLI, Codex, Augment,
OpenCode, Devin, Windsurf, Aider, Cline, and Continue. Otherwise it uses human mode.

## AI Output

On PHPUnit 10.5 or later, AI mode replaces PHPUnit's progress and result output. Every issue contains the test
location and category, test name, message, source line when readable, comparison diff when available, and the
application source location inferred from the stack trace:

```text
--- PHPUnit: 1 failure, 1 error ---

tests/Unit/PriceTest.php:42 [FAILED]
  PriceTest::testTotals
  Failed asserting that 11 is identical to 10.
  > self::assertSame(10, $price->total());
  -10
  +11
  Source: src/Price.php:27
```

A successful run whose normal output was replaced writes `No test failures`.
The PHPUnit 9.6 listener API cannot replace the runner's output, so the legacy adapter adds the same AI report to
standard error instead.

## Human Output

Human mode leaves PHPUnit's standard output in place and adds a report on standard error when an issue exists. The
additional report groups issues by test file and renders source context, carets, colored labels, diffs, implicated
source locations, and a final count summary. It writes nothing extra for a clean run.

## ParaTest

The adapters disable themselves when the `PARATEST` environment marker is present. ParaTest manages worker output
and does not support PHPUnit extension output replacement, so emitting another report from every worker would
duplicate and interleave results.
