# Command Line

```console
vendor/bin/guard <command> [options]
```

`guard` without a command runs `check`. Run `guard --help` for the command list and `guard <command> --help` for the options of one command. A command name may be shortened while only one command starts with it, such as `guard ch`.

## Commands

| Command | What it does |
|---------|--------------|
| `rules` | Lists the effective rules after imports, overrides and profile inheritance, with diagnostic messages and automatic fix support. |
| `check` | Reports every violation without changing a file. |
| `baseline` | Records all current violations for subsequent checks, without changing project files. |
| `fix` | Writes the repairs of [configuration fields](fields.md#repairs), then reports what remains. |
| `init` | Writes a first `guard.yaml` with the presets that fit the project. See [Init](#init). |

```console
guard init
guard rules --query=phpstan
guard check
guard fix --dry-run
guard fix
```

`fix` replaces the former `apply` command. Update scripts to use `guard fix` and `guard fix --dry-run`.

## Options

| Command | Option | Description |
|---------|--------|-------------|
| all | `-c`, `--config=FILE` | Policy file, relative to the working directory. Default `guard.yaml`. Paths inside it are relative to its own directory. |
| `rules`, `check`, `fix`, `baseline` | `--format=FORMAT` | `text` (also `human`), `ai`, or `json`. Defaults to `ai` in detected agent sessions, otherwise `text`. Explicit selection takes precedence. |
| `rules` | `--query=TEXT` | Case-insensitive substring search across rule IDs, target paths, severity levels, messages and `fixable` / `manual`. An unmatched query returns an empty list and exits 0. |
| `check` | `--query=TEXT` | Case-insensitive literal search in paths, rule IDs, messages, severity and fixability. |
| `check` | `--fixable` | Show only violations with automatic repair support. |
| `check` | `--level=error\|warning` | Show only errors or only warnings. |
| `check` | `--baseline=FILE` | Use this baseline, relative to the policy directory. Defaults to `guard-baseline.json` beside the policy, if present. |
| `check` | `--no-baseline` | Check without suppressing existing violations. Cannot be combined with `--baseline`. |
| `baseline` | `-o`, `--output=FILE` | Create or regenerate a baseline, relative to the policy directory. Default `guard-baseline.json`. |
| `fix` | `--dry-run` | Show the proposed file diffs without writing any files. |
| `init` | `--import=NAMES` | Import only these presets, comma-separated or repeated, instead of detecting them. |

The Symfony Console options `-q`, `--silent`, `-v`, `--ansi`, `--no-ansi` and `-n` behave as in other Symfony Console tools, and `guard completion` prints a shell completion script. JSON and AI reports remain free of ANSI escapes even with `--ansi`. Command help shows descriptions and options without repeated explanatory paragraphs.

## Exit codes

| Code | Meaning |
|------|---------|
| `0` | No required violation; recommendations may remain. For `init`, the policy file was created. `rules` successfully listed or searched the configuration. `baseline` successfully wrote the baseline, even when violations exist. |
| `1` | An active required rule is violated (after baseline suppression for `check`). For `fix`, after the repairs it could make. |
| `2` | Invalid command line, policy file, input document or extension; for `init`, an existing policy file or an unknown preset. |

Exit code `2` prints a diagnostic to standard error starting with `Guard error:`. It is printed even with `--quiet`.

## Rule messages

`rules` reads the policy without checking or opening the target files. It shows configured rules even when those files are missing or currently compliant. Metric profiles that are not selected by the default or an assignment, and disabled limits, are omitted. Scope, exclusions and assignments appear in the constraints; the list does not expand globs into current file matches. Configured extensions are identified separately by an `extension:` prefix; their internal rules and repair capabilities are defined by the extension itself.

```console
guard rules --query=phpstan.level --format=text
guard rules --query=workers --format=json
```

Rule messages describe **what is wrong and how to fix it**, like the toolkit PHPStan rules. A field rule may supply a `message` template. Without one, Guard generates a diagnostic from its assertions and repair value; the output still names the file, field, expected value and concrete edit. All shipped field rules supply messages. Manual-only rules explain the edit instead of telling the caller to run a fixer that cannot change the file.

Templates expand `{file}`, `{select}`, `{expectation}` and `{fix}` using the effective configuration after imports and overrides. `{expectation}` describes every assertion in words; `{fix}` describes the configured repair or the manual edits needed. This keeps the message correct when a project changes a preset's target file or threshold.

```yaml
version: 1
configuration:
  - id: workers
    file: app.json
    select: /workers
    message: 'Worker count at {select} in {file} must {expectation}. {fix}'
    assert: {min: 1, max: 4}
    repair: 2
```

The rendered message is: `Worker count at /workers in app.json must be at least 1 and be at most 4. Set /workers in app.json to 2. Run guard fix to apply the configured repair.`

A custom message should identify the problem and give an actionable edit. Use the placeholders for values that imports can override. For a missing Composer command, for example, provide the script name and an example command. Built-in source diagnostics name the measured value and limit, then explain how to split responsibilities or simplify branches. Structure and documentation diagnostics name the offending entry and the required rename, move, removal or restoration. All built-in metric, structure and documentation diagnostics also explain the consequence the rule prevents, such as untestable branching, unclear architectural boundaries or lost project guidance. The same messages appear in human, AI and JSON output. Extension authors must provide the same problem, consequence and concrete repair guidance in every diagnostic they emit.

`rules --format=json` returns `{"rules": [...], "count": N}`. Each entry has `id`, `target`, `level`, `fixable`, `message` and `details`. The details include resolved constraints and, where supported, the repair value. A rule can appear once per scope or metric profile. `fixable` here describes the rule capability; `check` also considers whether the target file exists.

## Reports

Human and AI reports use paths relative to the policy directory for file changes. Human reports group violations by file with severity, rule ID, `fixable` or `manual`, a diagnostic describing the problem and its remedy. The summary counts errors, warnings and fixable violations. Colours follow terminal support and `--ansi` / `--no-ansi`.

```text
Guard check

app.json
  error: workers  fixable
    /workers in app.json must equal 2. Set /workers in app.json to 2. Run guard fix to apply the configured repair.

1 error · 0 warnings · 1 fixable · 1 file with findings
Preview repairs: guard fix --dry-run
Guard found required violations.
```

`fix --dry-run` shows unified diffs, including removed and added lines and final-newline changes. It explicitly confirms that no files were written. `fix` shows the changes it wrote; an unchanged run has no diffs. Planned changes remain visible when a required configuration violation blocks the write.

`--format=ai` gives compact plain text with stable rule IDs, `fixable=yes/no`, actionable messages, diffs and a summary. Agent detection recognizes the toolkit reporter markers (`AI_AGENT`, `CLAUDE_CODE`, `CLAUDECODE`, `CURSOR_TRACE_ID`, `CURSOR_AGENT`, `GEMINI_CLI`, `CODEX_SANDBOX`, `AUGMENT_AGENT`, `OPENCODE`, `DEVIN`, `WINDSURF_SESSION_ID`, `AIDER`, `CLINE`, `CONTINUE_GLOBAL_DIR`, `/opt/.devin`) and `CODEX_THREAD_ID`. An empty `AI_AGENT` alone does not select AI output.

`--format=json` returns one object:

```json
{
    "findings": [
        {
            "path": "app.json",
            "rule": "workers",
            "level": "error",
            "message": "/workers in app.json must equal 2. Set /workers in app.json to 2. Run guard fix to apply the configured repair.",
            "fixable": true
        }
    ],
    "changes": [],
    "diffs": [],
    "action": "checked",
    "success": false,
    "summary": {"errors": 1, "warnings": 0, "fixable": 1}
}
```

`changes` lists absolute paths. `diffs` contains a `{path, diff}` object for each proposed change. `action` is `checked`, `changed`, `would change`, or `blocked`; it applies to every change. `success` is false exactly when the exit code is 1. Fix reports contain remaining findings, so repaired findings are absent from their counts. JSON preserves the same information regardless of terminal colour settings.

## Filtering checks

```console
guard check --fixable
guard check --level=error
guard check --level=warning --query=phpunit
guard check --fixable --query=phpstan --format=json
```

Filters combine with AND and only change the displayed findings. Guard still checks every policy; hidden errors still produce exit code 1 and `success: false`. The report states how many active findings were hidden. An empty search result is not a passing check when required violations exist elsewhere. Use `--fixable` rather than a text query to select automatic repairs precisely.

When a filter hides findings or a baseline is active, JSON also includes `selection` (`shown`, `total`, `hidden`), `totalSummary` (all active error, warning and fixable counts), and `baseline` (`path`, `suppressed`, `unmatched`, or `null`). `summary` always counts displayed findings. Baseline suppression happens before display filtering.

## Baselines

```console
guard baseline
guard check
guard check --no-baseline
```

`baseline` records all current violations from configuration, metrics, structure, documentation and extensions. It never repairs project files or consumes an existing baseline while generating the new one. Review and commit `guard-baseline.json` alongside the policy. Subsequent `check` runs automatically use that file if present. For a custom location, use `guard baseline --output=baselines/guard.json` and `guard check --baseline=baselines/guard.json`; the parent directory must exist. Paths are relative to the selected policy file, including when `--config` points into another directory.

Each JSON entry contains the exact project-relative `path`, `rule`, `level`, `message`, and a positive occurrence `count`. Matching is literal, without regular expressions or wildcards. Additional occurrences, new paths, changed severity, or changed diagnostics remain active. Metric values and limits are part of the message, so changed measurements (including improvements still above the limit) require review. A diagnostic wording change after upgrading Guard may also require baseline regeneration.

Resolved or changed entries are counted as `unmatched` and reported without failing the check. Run `guard baseline` again after reviewing the remaining findings to remove stale entries. Regeneration records **all current violations**, including new ones; review the diff before committing. Generating with no violations writes a valid empty baseline. Malformed, unsupported or explicitly missing baseline files fail with exit code 2. The automatic baseline file being absent is normal.

`fix` operates on all rules, including baselined violations. A baseline cannot bypass repair conflicts or permission checks. `rules` always lists the complete active policy, irrespective of baselines. The baseline is a normal project file: if directory rules restrict its location or count, choose a permitted location or an appropriate existing exclusion.

## Repairs

`fix` plans every configuration repair before writing. While a required configuration rule cannot be satisfied, nothing is written and the report says `blocked`. Its diffs describe proposals, not completed changes. Metric, structure and document findings do not block configuration repairs; they remain required failures after `fix`.

`check` never writes. A required configuration violation that `fix` can repair still fails `check`. Missing target files, check-only formats, rules without repair values and conflicting repairs require manual changes. `fixable` indicates automatic repair support, not permission to ignore a blocking violation elsewhere.

## Init

`guard init` writes `guard.yaml` and refuses to overwrite an existing file. Without `--import` it selects presets from what the project has:

| Preset | Selected when |
|--------|---------------|
| `metrics`, `structure` | Composer `autoload.psr-4` names an existing directory, or `src/` exists. |
| `disable-doc` | The project has no `docs/` directory. |
| `phpstan` | `phpstan.neon` or `phpstan.neon.dist` exists. |
| `phpstan-guard-rules` | As `phpstan`, and `k-kinzal/phpstan-guard-rules` is required or locked. |
| `phpunit9` … `phpunit13` | One per PHPUnit configuration file. `phpunit10.xml.dist` selects `phpunit10`; `phpunit.xml.dist` is PHPUnit 13 next to versioned files, and otherwise the major in `composer.lock` or a single-major constraint. |
| `doctest9` … `doctest13` | The matching PHPUnit file already has a doctest suite, or `phpstan.neon` requires PHPDoc examples. |
| `php-cs-fixer` | `.php-cs-fixer.dist.php` or `.php-cs-fixer.php` exists. |
| `phpcs` | `phpcs.xml.dist` or `phpcs.xml` exists. |
| `deptrac` | `deptrac/deptrac` is installed and `deptrac.yaml` or `deptrac.yml` exists. |
| `infection` | `infection.json5` exists. |
| `composer` | `composer.json` exists. |
| `github-actions`, `mutation` | `.github/workflows/ci.yml` or `.github/workflows/mutation.yml` exists. |
| `pbt`, `fuzz`, `phpbench`, `docgen` | `giorgiosironi/eris`, `nikic/php-fuzzer`, `phpbench/phpbench` or `k-kinzal/docgen-php` is installed. |
| `agents-md` | `AGENTS.md` or `CLAUDE.md` exists. |
| `claude-md` | `CLAUDE.md` exists. |
| `readme-md` | `README.md` exists. |

`--import` replaces detection: only the named presets are imported. Besides the imports, `init` writes:

- `metrics.source` with the detected source roots, and `structure.directories` for source roots other than `src`.
- The current README headings under `documentation.files`, with `scan: [README.md]`.
- An `id` and `file` override for each tool rule whose configuration file is not the preset's default, such as `phpstan.neon.dist`.
- The settings of legacy `loc.yaml`, `tree.yaml` and `doc-guard.yaml` files. A legacy `loc.yaml` or `tree.yaml` replaces the `metrics` or `structure` import, so its thresholds stay as they were.

The written file records the project as it is today. Review it before committing: a recorded heading list or a detected preset is a starting point, not a decision.
