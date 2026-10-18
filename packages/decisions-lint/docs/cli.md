# Command line

Invoke `vendor/bin/decisions-lint COMMAND` from the project root. No command means `check`. Every command reads `decisions.yaml` in the working directory unless `--config` names another file; paths in the configuration and on the command line are relative to the directory of that file.

## check

```console
$ vendor/bin/decisions-lint check
$ vendor/bin/decisions-lint check src/UseCase --rule=ddd.usecase-domain-leak --format=json
```

| Option | Meaning |
|--------|---------|
| `paths...` | Files, directories or globs to lint instead of the configured `paths`. |
| `-c`, `--config=FILE` | Configuration file, relative to the working directory; default `decisions.yaml`. |
| `--format=FORMAT` | `text` (alias `human`), `ai`, `json`, `checkstyle` or `github`. Defaults to `ai` in a detected agent session, otherwise `text`. |
| `-r`, `--rule=ID` | Run only this rule; repeat for several. An unknown id exits `2`. |
| `--fail-on=SEVERITY` | The weakest severity that exits `1`: `error`, `warning`, `info` or `never`. Defaults to `fail_on` in the configuration, which defaults to `error`. |

The API key is read from the environment variable `provider.api_key_env` names, `TYPESAFE_API_KEY` for Jev and `OPENAI_API_KEY` for OpenAI by default. A missing key for those two exits `2` before anything is sent; the local providers (`ollama`, `localai`, `openai-compatible`) run without a key and send one only when their variable is set.

### Output formats

Every format lists the same fields per finding: path, line, column, rule id, severity, the judged symbol, the message, the fix and the model's confidence.

| Format | Shape |
|--------|-------|
| `text` | Grouped by file, coloured when the terminal supports it, followed by the counts and the verdict. |
| `ai` | One line per finding: `severity: path:line:column [rule] symbol confidence=0.91 message Fix: fix`, then the counts and the verdict. |
| `json` | `findings[]`, `summary{error,warning,info}`, `files`, `targets`, `requests`, `models[]`, `fail_on`, `success`. |
| `checkstyle` | Checkstyle XML with one `file` element per path; the `message` attribute holds the message and the fix. |
| `github` | GitHub Actions workflow commands (`::error file=…,line=…,col=…,title=rule::message Fix: fix`), so findings appear as annotations. |

`requests` counts the calls made to the provider: one per judged declaration and context, however many rules asked about it.

## plan

```console
$ vendor/bin/decisions-lint plan
src/Analysis/Analyzer.php:11:5 method App\Analysis\Analyzer::sendReport (class) <- cohesion.method-belongs-to-class, naming.method-says-what-it-does
2 files, 3 targets, 3 requests, 5 questions. Nothing was sent.
```

`plan` takes the same `paths`, `--config`, `--rule` options as `check` and `--format=text|json`. Use it to see which declarations a new rule selects, and how many requests a run costs, before spending any.

## rules

Lists every rule after imports and overrides: id, severity, target kind, decision type, whether it is enabled, and its path globs. `--format=json` returns `rules[]` with the full definition of each rule and `count`.

## validate

```console
$ vendor/bin/decisions-lint validate rules/team.yaml
decisions.yaml is valid: 5 rules, 4 enabled, provider jev (jev-latest).
rules/team.yaml is valid: 2 rules.
```

Loads the configuration with its imports, which validates every document against [its schema](configuration.md#schemas) and every merged rule for completeness. Each rule file given as an argument is validated on its own as well. The first problem is reported with its path and how to fix it, and the command exits `2`.

## init

Writes `decisions.yaml` with the chosen provider, the paths to lint, and an import of every shipped rule file. It refuses to overwrite an existing file.

| Option | Meaning |
|--------|---------|
| `--provider=NAME` | `jev` (default), `openai`, `ollama`, `localai` or `openai-compatible`. |
| `-p`, `--path=DIR` | A directory to lint; repeat for several. Defaults to `src`. |
| `-c`, `--config=FILE` | Where to write the file; default `decisions.yaml`. |

## Exit codes and errors

| Code | Meaning |
|------|---------|
| `0` | The command completed; for `check`, no finding reaches `fail_on`. |
| `1` | `check` found a violation at or above `fail_on`. |
| `2` | The command line, the configuration, a rule file, an input file or the provider response is invalid, or the provider could not be reached. |

Every exit `2` prints one `Decisions error:` line on standard error that names the problem and what to change, for example:

```text
Decisions error: decisions.yaml: rules.cohesion.method-belongs-to-class.decision.threshold must be at most 1, not 1.5. Lower the value.
Decisions error: jev returned HTTP 401 for POST https://api.typesafe.ai/v1/systemone: invalid api key. Check that the TYPESAFE_API_KEY environment variable holds a valid jev API key.
```

Standard output stays parseable: when the exit code is `2`, nothing was written to it.
