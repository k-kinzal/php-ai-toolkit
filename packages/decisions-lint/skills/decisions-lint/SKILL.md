---
name: decisions-lint
description: >-
  Lint PHP code against nuanced, natural-language rules with the decisions-lint
  CLI (k-kinzal/decisions-lint) and a decision model such as Jev, the OpenAI
  Decisions API, or a local model served by Ollama or LocalAI. Use for
  decisions-lint commands, findings and their fixes, decisions.yaml changes,
  writing or tuning rule files, provider setup, or running the check in CI.
---

# Decisions Lint

Run the installed CLI from the target project's root. Read its `decisions.yaml`
to learn the provider, the linted paths and the imported rule files. The API key
comes from the environment variable `provider.api_key_env` names; never write a
key into a file. `ollama`, `localai` and `openai-compatible` need a running
server at `provider.base_url` instead of a key.

```bash
vendor/bin/decisions-lint --help
vendor/bin/decisions-lint plan
vendor/bin/decisions-lint check --format=ai
```

## Command rules

- Run `plan` before `check` when rules or paths changed: it lists every judged
  declaration and the number of requests without calling the provider.
- Use `--format=ai` for compact findings or `--format=json` for parsing. Each
  finding carries `path:line:column`, the rule id, the judged symbol, the
  message, the fix and the model's confidence.
- Narrow a run with `--rule=ID` and path arguments while tuning a rule; the
  default run judges every enabled rule over the configured paths.
- Fix the code the way the finding's `Fix:` text says. Change `severity`,
  `threshold`, `paths` or `enabled` in `decisions.yaml` only when the task is
  to change the policy, and say so.
- Run `validate` after editing `decisions.yaml` or a rule file; the first
  problem is reported with its path and how to fix it.

Exit `0` means no finding reaches `fail_on`, `1` means a finding does, and `2`
means an invalid command, configuration, rule file or provider response; read
the `Decisions error:` line on standard error.

## References

Read only the reference needed for the current task:

- [CLI](references/cli.md): commands, options, output formats, exit codes and errors.
- [Rule authoring](references/rules.md): the rule format, decision types, violations, placeholders and how to tune a rule.
- [Configuration](references/configuration.md): decisions.yaml keys, imports, overrides and schemas.
