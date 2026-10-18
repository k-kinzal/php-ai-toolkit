# decisions-lint CLI

Invoke `vendor/bin/decisions-lint COMMAND` from the project root. No command means `check`.

## Commands

| Command | Behavior |
|---------|----------|
| `check [paths...]` | Judge the configured files, or the given paths, and report violations. |
| `plan [paths...]` | List each judged declaration with its rules and the request count; nothing is sent. |
| `rules` | List the effective rules after imports and overrides. |
| `validate [files...]` | Validate `decisions.yaml` with its imports, and any rule file given, against the schemas. |
| `init` | Write a `decisions.yaml` importing the shipped rule files; refuse to overwrite. |

## Options

| Applies to | Option | Meaning |
|------------|--------|---------|
| All | `-c`, `--config=FILE` | Configuration relative to the working directory; default `decisions.yaml`. Paths resolve relative to its directory. |
| `check` | `--format=text\|human\|ai\|json\|checkstyle\|github` | Default `ai` in agent sessions, otherwise `text`. |
| `plan`, `rules` | `--format=text\|json` | |
| `check`, `plan` | `-r`, `--rule=ID` | Run one rule; repeat for several. |
| `check` | `--fail-on=error\|warning\|info\|never` | Weakest severity that exits `1`; default `fail_on` in the configuration, then `error`. |
| `init` | `--provider=jev\|openai\|ollama\|localai\|openai-compatible`, `-p`, `--path=DIR` | Provider and directories for the new file. |

## Output

`ai` prints one line per finding:

```text
error: src/UseCase/PlaceOrder.php:6:5 [ddd.usecase-domain-leak] App\UseCase\PlaceOrder::handle confidence=0.88 Use case PlaceOrder::handle encodes a business rule that belongs in the domain layer. Fix: Move the rule into the entity, value object or domain service it describes, and have handle() call that method so the use case only orchestrates.
1 error, 0 warnings, 0 infos, 1 file with findings, 3 targets, 3 requests to jev-1.13.0.
Decisions check failed: a finding is at least error and fail_on is error.
```

`json` returns `findings[]` with `path`, `line`, `column`, `rule`, `severity`, `symbol`, `message`, `fix`, `confidence`, plus `summary`, `files`, `targets`, `requests`, `models`, `fail_on` and `success`. `success: false` corresponds to exit `1`.

## Exit codes

| Code | Meaning |
|------|---------|
| `0` | Completed; for `check`, no finding reaches `fail_on`. |
| `1` | `check` found a finding at or above `fail_on`. |
| `2` | Invalid command line, configuration, rule file, input file or provider response, or the provider is unreachable. Read `Decisions error:` on stderr; stdout is empty. |

For exit `2`, correct what the message names and rerun. A missing API key, an HTTP 401 and an HTTP 429 are each reported with the setting or variable to check.
