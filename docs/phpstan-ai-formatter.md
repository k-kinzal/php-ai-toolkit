# PHPStan AI Formatter

php-ai-toolkit provides a dual-mode PHPStan error formatter under the formatter name `ai`. It presents the same
analysis result differently depending on whether an AI coding agent or a person is running PHPStan.

## Mode Detection

AI mode is selected when the process contains a known agent environment marker. The detector currently recognizes
`AI_AGENT`, Claude Code, Cursor, Gemini CLI, Codex, Augment, OpenCode, Devin, Windsurf, Aider, Cline, and Continue
markers, as well as `/opt/.devin`. `AI_AGENT` must have a non-empty value; the other environment variables are
presence markers.

When no marker is found, the formatter uses human mode. Detection changes presentation only: it does not change the
analyzed paths, enabled rules, identifiers, or exit status.

## AI Output

AI mode writes compact plain text with project-relative locations, rule identifiers, the diagnostic message, the
source line when it can be read, and PHPStan's tip when one is available:

```text
--- 2 errors in 1 file ---

tests/FooTest.php:18 [customRules.testClassProperty]
  Test class FooTest has a property declaration.
  > private string $name;
  Tip: Remove the property and use local variables in each test method.
```

When any identifier occurs at least three times, all file-specific findings are grouped by identifier. The shared
message and tip are printed once, while every location and source line remains visible:

```text
[customRules.testClassProperty] 3 occurrences:
  tests/FooTest.php:18 -- private string $name;
  tests/BarTest.php:21 -- private Client $client;
  tests/BazTest.php:15 -- private array $values;
  Message: Test class has a property declaration.
  Tip: Remove the property and use local variables in each test method.
```

Errors without a file are emitted under `[general]`; warnings are emitted under `[warning]`. A clean analysis writes
`No errors`.

## Human Output

Human mode groups findings by file and renders terminal-oriented source context, line-number gutters, carets, colors,
identifiers, messages, and tips. It ends with a count summary. This is a different renderer over the same PHPStan
result, so switching modes never hides a finding.

## Exit Status

Both modes preserve PHPStan's error contract:

- `0` when the analysis contains no errors, including warning-only results.
- `1` when one or more errors exist.

The formatter is registered by the package's public `extension.neon` entry point.
