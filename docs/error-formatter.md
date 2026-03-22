# Error Formatter

| Property | Value |
|----------|-------|
| Format name | `aiRules` |
| Modes | Human (default), AI (auto-detected) |
| Scope | All PHPStan errors |

## Setup

The error formatter is not included automatically. Add `error-formatter.neon` to your `phpstan.neon`:

```neon
includes:
    - vendor/k-kinzal/php-ai-toolkit/error-formatter.neon
```

## Overview

`aiRules` is a dual-mode error formatter that automatically switches output format based on who is reading it — a human developer or an AI agent.

```bash
vendor/bin/phpstan analyse --error-format aiRules
```

When run inside an AI agent (Claude Code, Cursor, Devin, etc.), the formatter outputs structured plain text optimized for LLM context windows. When run by a human in a terminal, it outputs rich, grouped output with code context and color.

## AI Agent Auto-Detection

The formatter checks the following environment variables to detect AI agents:

| Agent | Environment Variable |
|-------|---------------------|
| Claude Code | `CLAUDE_CODE`, `CLAUDECODE` |
| Cursor | `CURSOR_TRACE_ID`, `CURSOR_AGENT` |
| Gemini CLI | `GEMINI_CLI` |
| Codex | `CODEX_SANDBOX` |
| Windsurf | `WINDSURF_SESSION_ID` |
| Devin | `DEVIN` |
| Augment | `AUGMENT_AGENT` |
| OpenCode | `OPENCODE` |
| Aider | `AIDER` |
| Cline | `CLINE` |
| Continue | `CONTINUE_GLOBAL_DIR` |
| Generic | `AI_AGENT` (any non-empty value) |

Filesystem markers are also checked:

| Agent | Path |
|-------|------|
| Devin | `/opt/.devin` |

If none of the above are detected, the formatter defaults to human-readable output.

### Using with unlisted agents

Set `AI_AGENT` to any non-empty value:

```bash
AI_AGENT=my-agent vendor/bin/phpstan analyse --error-format aiRules
```

## Human-Readable Output

Groups errors by file, shows source code with caret pointers, and displays identifiers and tips with color.

```
 src/Service/UserService.php

  12 |     private string $name;
     |     ^^^^^^^^^^^^^^^^^^^^^^
  customRules.testClassProperty: Property $name is prohibited in Tests\Unit
  and Tests\Integration classes.
  Tip: Remove the property and use local variables in each test method.

  45 |     // @phpstan-ignore argument.type
     |     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
  customRules.phpstanIgnoreComment: phpstan-ignore comments are prohibited.
  Tip: Remove this comment and re-run PHPStan to reveal the actual error.

 tests/Unit/FooTest.php

  8  |     use SomeTrait;
     |     ^^^^^^^^^^^^^^
  customRules.testClassTraitUse: Test class should not use traits.
  Tip: Use dedicated helper classes instead.

 [ERROR] Found 3 errors in 2 files
```

Design choices:

- **Code context with carets** — Shows the actual source line and highlights the relevant code. 28% higher repair scores vs line-number-only output (Rust/Clang studies).
- **Header-first, code-second** — File path appears before the code snippet so you know which file before seeing the code.
- **Error identifier inline** — e.g. `customRules.testClassProperty`. Enables quick lookup or `ignoreErrors` entry without extra steps.
- **Grouped by file** — All errors in one file appear under a single header. Reduces visual scanning.
- **Error count summary** — `Found N errors in M files` at the end for progress tracking during refactoring.
- **Color with NO_COLOR support** — Uses Symfony Console color tags. Respects `NO_COLOR`, `TERM=dumb`, and non-TTY output.

## AI-Readable Output

Structured plain text optimized for LLM context windows. No ANSI codes, no decorative characters.

### Flat format (few errors)

When no identifier appears 3 or more times, each error is listed individually:

```
--- 3 errors in 2 files ---

src/Service/UserService.php:12 [customRules.testClassProperty]
  Property $name is prohibited in Tests\Unit and Tests\Integration classes.
  > private string $name;
  Tip: Remove the property and use local variables in each test method.

src/Service/UserService.php:45 [customRules.phpstanIgnoreComment]
  phpstan-ignore comments are prohibited.
  > // @phpstan-ignore argument.type
  Tip: Remove this comment and re-run PHPStan to reveal the actual error.

tests/Unit/FooTest.php:8 [customRules.testClassTraitUse]
  Test class should not use traits.
  > use SomeTrait;
  Tip: Use dedicated helper classes instead.
```

### Grouped format (repeated errors)

When the same identifier appears 3+ times, errors are grouped to reduce token usage:

```
--- 15 errors in 8 files ---

[customRules.testClassProperty] 7 occurrences:
  src/Tests/ATest.php:12 -- private string $name;
  src/Tests/BTest.php:8 -- private int $count;
  src/Tests/BTest.php:9 -- private array $items;
  src/Tests/CTest.php:15 -- private Logger $logger;
  src/Tests/DTest.php:6 -- private MockObject $mock;
  src/Tests/ETest.php:22 -- private string $expected;
  src/Tests/FTest.php:11 -- private Connection $db;
  Message: Property $name is prohibited in Tests\Unit and Tests\Integration classes.
  Tip: Remove the property and use local variables in each test method.

[customRules.phpstanIgnoreComment] 5 occurrences:
  src/Service/FooService.php:42 -- // @phpstan-ignore argument.type
  src/Service/BarService.php:18 -- // @phpstan-ignore-next-line
  src/Service/BazService.php:91 -- // @phpstan-ignore return.type
  src/Repository/UserRepo.php:33 -- // @phpstan-ignore argument.type
  src/Repository/ItemRepo.php:67 -- // @phpstan-ignore-next-line
  Message: phpstan-ignore comments are prohibited.
  Tip: Remove this comment and re-run PHPStan to reveal the actual error.
```

Design choices:

- **Plain text, not JSON** — Markdown key-value format (60.7%) outperforms JSON (52.3%) for LLM data retrieval accuracy. PHPStan already has `--error-format json` for CI pipelines.
- **`path:line` leading** — The GCC/ESLint convention is the most recognized pattern in LLM training data, enabling instant file identification.
- **Summary-first** — `--- N errors in M files ---` at the top leverages the primacy effect. LLMs recall information at the beginning of context ~20% better than the middle (Liu et al. 2023).
- **Self-contained blocks** — Each error contains all information needed to act on it. No cross-referencing file headers from elsewhere in the output.
- **Deduplication** — Grouping identical errors achieves 3-5x token reduction. Repetitive content degrades LLM reasoning accuracy by 20-30% (Shi et al. ICML 2023).
- **Code context line** — Includes the actual source line, eliminating the need for the agent to open the file just to understand the error.
- **Relative paths** — Saves 30-50 characters per error vs absolute paths.
