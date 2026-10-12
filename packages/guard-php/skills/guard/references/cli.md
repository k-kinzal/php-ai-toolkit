# Guard CLI

Invoke `vendor/bin/guard COMMAND` from the project root. No command means `check`.
Use `guard COMMAND --help` to confirm the installed version's options.

## Commands

| Command | Behavior |
|---------|----------|
| `rules` | List effective rules after imports and overrides, without reading target files. |
| `check` | Report violations without modifying files. |
| `fix --dry-run` | Show proposed configuration diffs without writing them. |
| `fix` | Apply supported repairs, then report remaining violations. |
| `baseline` | Write all current violations to a baseline; leave source and configuration files unchanged. |
| `init` | Create a policy from detected presets and legacy policies; refuse to overwrite an existing policy. |

## Options

| Applies to | Option | Meaning |
|------------|--------|---------|
| All policy commands | `-c`, `--config=FILE` | Policy path relative to the working directory; default `guard.yaml`. |
| `rules`, `check`, `fix`, `baseline` | `--format=text\|human\|ai\|json` | Default `ai` in detected agent sessions, otherwise `text`; `human` aliases `text`. |
| `rules`, `check` | `--query=TEXT` | Case-insensitive literal search across rule IDs, paths, messages, levels, and fixability. |
| `check` | `--fixable` | Display only findings with automatic repair support. |
| `check` | `--level=error\|warning` | Display one severity. |
| `check` | `--baseline=FILE` | Baseline relative to the policy directory; default `guard-baseline.json` there, if present. |
| `check` | `--no-baseline` | Disable suppression; incompatible with `--baseline`. |
| `baseline` | `-o`, `--output=FILE` | Baseline destination relative to the policy directory; default `guard-baseline.json`. Parent directory must exist. |
| `init` | `--import=NAMES` | Comma-separated or repeated preset names; replaces automatic detection. |

Check filters combine with AND and affect only displayed findings. Baseline
suppression happens first. Neither filters nor baselines limit `fix`.

## Reports and exit codes

| Code | Meaning |
|------|---------|
| `0` | No active required violation; warnings may remain. `rules`, `init`, and `baseline` completed successfully. |
| `1` | Active required violations remain, including ones hidden by display filters. |
| `2` | Invalid command, policy, input document, baseline, or extension. Read stderr's `Guard error:` message. |

`baseline` exits `0` after writing successfully even when it recorded violations.
`fix --dry-run` reports whether the planned result would pass; exit `0` does not
mean the files already comply.

`rules --format=json` returns `rules` and `count`. Each rule includes `id`,
`target`, `level`, `fixable`, `message`, and resolved `details`.

Check and fix JSON includes `findings`, `changes`, `diffs`, `action`, `success`,
and `summary`. Findings contain `path`, `rule`, `level`, `message`, and `fixable`.
`summary` counts displayed findings; when filtering or a baseline is active,
use `totalSummary` and `selection` to account for hidden active findings, and
`baseline` for suppression counts. `success: false` corresponds to exit `1`.
Errors with exit `2` are stderr diagnostics, so check the exit code before
parsing stdout as JSON.

`action` is `checked`, `changed`, `would change`, or `blocked`. Proposed diffs
with `blocked` are not applied. `changes` uses absolute paths; finding and diff
paths are relative to the policy directory.

For an unexpected missing finding, inspect the selected policy, automatic
baseline, display filters, and `collect` scope. For exit `2`, correct the named
input before rerunning; it is not a policy violation to suppress.
