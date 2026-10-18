# decisions.yaml

```yaml
version: 1
provider: {name: jev, model: jev-latest, api_key_env: TYPESAFE_API_KEY, timeout: 60, concurrency: 8}
paths: [src]
exclude: ['src/Generated/**']
fail_on: error
imports:
  - vendor/k-kinzal/decisions-lint/rules/cohesion.yaml
  - rules/team.yaml
rules:
  cohesion.method-belongs-to-class: {severity: error, decision: {threshold: 0.8}}
  cohesion.property-belongs-to-class: {enabled: false}
```

| Key | Default | Meaning |
|-----|---------|---------|
| `version` | required | Always `1`. |
| `provider.name` | `jev` | `jev` (TypeSafe System One), `openai` (OpenAI Decisions API), `ollama` (local `/api/chat`), `localai` or `openai-compatible` (`/v1/chat/completions` with JSON schema output). |
| `provider.model` | `jev-latest` / `gpt-6-luna` / `llama3.1` / `gpt-4` / `default` | Model identifier; set it to the model the local server loads. |
| `provider.api_key_env` | `TYPESAFE_API_KEY` / `OPENAI_API_KEY` / `OLLAMA_API_KEY` / `LOCALAI_API_KEY` / `OPENAI_COMPATIBLE_API_KEY` | Environment variable holding the key; required for `jev` and `openai` only. |
| `provider.base_url` | provider host, `http://localhost:11434` / `:8080` / `:8000` for local servers | For a proxy, compatible endpoint or local server. |
| `provider.timeout`, `provider.concurrency` | `60`, `8`; `300`, `2` for chat models | Seconds per request; requests in flight (1–64). |
| `paths`, `exclude` | `[src]`, `[]` | Globs relative to the configuration directory; `**` crosses directories. |
| `fail_on` | `error` | `error`, `warning`, `info` or `never`. |
| `imports` | `[]` | Rule files, relative to the configuration; later files override earlier ones by rule id. |
| `rules` | `{}` | Definitions and overrides by id; applied after the imports. |

## Schemas

`schema/decisions.schema.json` validates `decisions.yaml` (partial rule entries allowed); `schema/rules.schema.json` validates rule files (complete rules required). Every command validates the documents it reads and reports the first problem as `Decisions error: FILE: PATH PROBLEM. FIX.` with exit `2`. Add `# yaml-language-server: $schema=vendor/k-kinzal/decisions-lint/schema/decisions.schema.json` at the top of the file for editor validation.

## CI

```yaml
- run: vendor/bin/decisions-lint check --format=github
  env:
    TYPESAFE_API_KEY: ${{ secrets.TYPESAFE_API_KEY }}
```

`--format=github` emits workflow commands that annotate the changed lines. Use `--format=checkstyle` for tools that read Checkstyle XML, and `plan` in a pull request to show what a rule change would judge without spending requests.
