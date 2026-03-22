# Test Reporter

| Property | Value |
|----------|-------|
| Extension class | `PhpStanAiRules\TestReporter\AiTestReporterExtension` |
| Modes | Human (default), AI (auto-detected) |
| Scope | Test failures, errors, risky tests |

## Setup

Add the extension to your `phpunit.xml.dist`:

```xml
<extensions>
    <bootstrap class="PhpStanAiRules\TestReporter\AiTestReporterExtension"/>
</extensions>
```

The extension replaces PHPUnit's default result output section. Progress output (dots/F/E) is left unchanged.

## Overview

`AiTestReporterExtension` is a dual-mode PHPUnit extension that automatically switches output format based on who is reading it — a human developer or an AI agent.

When run inside an AI agent (Claude Code, Cursor, Devin, etc.), the extension outputs structured plain text optimized for LLM context windows. When run by a human in a terminal, it outputs rich, grouped output with code context and color.

## AI Agent Auto-Detection

The extension uses the same `AgentDetector` as the [error formatter](error-formatter.md). See that page for the full list of detected agents and environment variables.

## Human-Readable Output

Groups issues by file, shows source code with caret pointers, and displays type labels with color.

```
 tests/Unit/Service/UserServiceTest.php

  42 |     self::assertSame('John', $service->getUser()->name());
     |     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
  FAILED: UserServiceTest::testGetUserReturnsCorrectName
  Failed asserting that two strings are identical.
  --- Expected
  +++ Actual
  -'John'
  +'Jane'
  Source: src/Service/UserService.php:28

 tests/Unit/Repository/ItemRepoTest.php

  18 |     $repo->findById($input);
     |     ^^^^^^^^^^^^^^^^^^^^^^^^
  ERROR: ItemRepoTest::testFindByIdThrowsOnMissing
  TypeError: Argument 1 must be of type int, string given
  Source: src/Repository/ItemRepo.php:45

 [FAILURES] Found 1 failure and 1 error in 2 test files
```

Design choices:

- **Code context with carets** — Shows the actual assertion line and highlights it. 28% higher repair scores vs line-number-only output (Rust/Clang studies).
- **Header-first** — File path appears before the code snippet.
- **Color by severity** — FAILED/ERROR in red, RISKY in yellow, SKIPPED in cyan.
- **Source location** — `Source: src/...` shows where the actual bug is, not just where the test failed.
- **Grouped by file** — All issues in one file appear under a single header.
- **Error count summary** — `Found N failures and M errors in K test files` at the end.

## AI-Readable Output

Structured plain text optimized for LLM context windows. No ANSI codes, no decorative characters.

```
--- PHPUnit: 1 failure, 1 error in 2 tests ---

tests/Unit/Service/UserServiceTest.php:42 [FAILED]
  UserServiceTest::testGetUserReturnsCorrectName
  Failed asserting that two strings are identical.
  > self::assertSame('John', $service->getUser()->name());
  --- Expected
  +++ Actual
  -'John'
  +'Jane'
  Source: src/Service/UserService.php:28

tests/Unit/Repository/ItemRepoTest.php:18 [ERROR]
  ItemRepoTest::testFindByIdThrowsOnMissing
  TypeError: Argument 1 must be of type int, string given
  > $repo->findById($input);
  Source: src/Repository/ItemRepo.php:45
```

Design choices:

- **Plain text, not JSON** — Markdown key-value format (60.7%) outperforms JSON (52.3%) for LLM data retrieval accuracy.
- **`path:line` leading** — The GCC/ESLint convention is the most recognized pattern in LLM training data.
- **Summary-first** — `--- PHPUnit: N failures ---` at the top leverages the primacy effect. LLMs recall information at the beginning of context ~20% better than the middle (Liu et al. 2023).
- **Self-contained blocks** — Each failure includes all info needed: test name, message, code context, diff, and source location. No cross-referencing file headers.
- **Code context line** — Includes the actual assertion line, eliminating the need for the agent to open the file.
- **Source location** — `Source: src/Service/UserService.php:28` tells the AI agent where to fix the bug, not just where the test failed. This is the key difference from static analysis — test failures point to the test, but the fix is in the source code.
- **Comparison diff** — Expected vs actual values are shown inline, making the gap immediately actionable.
- **Relative paths** — Saves 30-50 characters per entry vs absolute paths.

## PHPStan Error vs PHPUnit Failure: Key Difference

| | PHPStan Error | PHPUnit Failure |
|---|---|---|
| Error location | = Fix location | ≠ Fix location |
| What to show | Rule violation at source line | Test failure + source bug location |
| Identifier | Rule name (`customRules.xxx`) | Test method name |
| Fix guide | Tip (explicit fix instruction) | Expected/actual diff |

For PHPStan errors, the error location IS where the fix goes. For PHPUnit failures, the test file shows WHERE the failure was detected, but the `Source:` line shows WHERE to fix the bug. The reporter extracts this from the stack trace, skipping vendor and test file frames.
