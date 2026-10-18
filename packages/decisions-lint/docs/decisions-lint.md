# Decisions Lint

A static analyser can prove that a method is too long or that a type does not match. It cannot tell whether `Analyzer::sendReport()` belongs in a class named `Analyzer`, whether `PlaceOrder::handle()` hides a pricing rule that belongs in the domain, or whether `process()` says what the method does. Those are judgements. decisions-lint asks a decision model for them, one typed question per declaration, and reports the answers like any other linter: file, line, column, rule, message, and the change that resolves the finding.

## How a run works

1. [`decisions.yaml`](configuration.md) names the files to lint, the rule files to import, and the decision API to use.
2. Each [rule](rules.md) selects declarations, such as the public methods of a class, and asks one question about each: a yes-or-no **predicate**, a **choice** between named options, or a **score** on a described scale.
3. The linter reads every file once, extracts the declarations, and groups every question about the same declaration into one request. The model reads the declaration together with its class or file and answers every question at once.
4. An answer that matches one of the rule's `violations` becomes a finding. The message and the fix are templates the rule author wrote, filled with the declaration's name, class and the answer, so the finding tells the reader what to change rather than only that something is wrong.

Decision APIs return a probability for a predicate, a probability over the options for a choice, and a probability-weighted level for a score. A rule sets the `threshold` the model must reach before an answer counts as a violation, so a hesitant answer is not reported. A local chat model served by Ollama, LocalAI or any OpenAI-compatible server can answer the same questions through a JSON schema; see [providers](providers.md).

## Why decision models

Earlier attempts at natural-language linting asked a chat model to write the findings, including the line, the column and the message. That was slow, expensive, and unstable: each call produced different text, and the model had to be trusted about positions. A decision model answers only the question it is asked, from a fixed set of answers, in a fraction of the time and cost. decisions-lint keeps everything deterministic in PHP: which declarations exist, where they are, what the message says. Only the judgement itself is delegated.

## Commands

| Command | What it does | Exit code |
|---------|--------------|-----------|
| `check [paths...]` | Judges the configured files, or the given paths, and reports violations. | `0` passed, `1` a finding reaches `fail_on`, `2` invalid input or provider failure |
| `plan [paths...]` | Lists each declaration a rule would judge and the rules that judge it, without calling the provider. | `0`, or `2` on invalid input |
| `rules` | Lists the effective rules after imports and overrides. | `0`, or `2` on invalid input |
| `validate [files...]` | Checks `decisions.yaml`, its imports, and any rule file given against their schemas. | `0`, or `2` on an invalid document |
| `init` | Writes a `decisions.yaml` that imports the shipped rule files. | `0`, or `2` when the file exists |

See [the command line](cli.md) for every option.

## What it does not prove

A finding is a judgement with a confidence, not a proof. A rule phrased loosely produces noise; a rule phrased precisely, with a clear boundary and examples in its instructions, produces findings a reviewer agrees with. Run `plan` to see what will be asked, start with `severity: warning`, raise `threshold` until the findings are ones you would act on, and only then make the rule an error.
