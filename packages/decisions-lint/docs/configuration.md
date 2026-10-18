# Configuration

`decisions.yaml` declares what a run lints, which rules it applies, and which decision API answers. `decisions-lint init` writes a starting file; this page describes every key.

## Example

```yaml
# yaml-language-server: $schema=vendor/k-kinzal/decisions-lint/schema/decisions.schema.json
version: 1
provider:
  name: jev
  model: jev-latest
  api_key_env: TYPESAFE_API_KEY
  concurrency: 8
paths:
  - src
exclude:
  - 'src/Generated/**'
fail_on: error
imports:
  - vendor/k-kinzal/decisions-lint/rules/cohesion.yaml
  - vendor/k-kinzal/decisions-lint/rules/ddd.yaml
  - rules/team.yaml
rules:
  cohesion.method-belongs-to-class:
    severity: error
    decision:
      threshold: 0.8
  cohesion.property-belongs-to-class:
    enabled: false
  ddd.usecase-domain-leak:
    paths:
      - 'src/Application/**/*.php'
```

The imports supply the rules; `rules` changes only what this project does differently. Here one imported rule becomes an error with a stricter threshold, another is switched off, and a third is pointed at the directory where this project keeps its use cases.

## Keys

| Key | Required | Default | Description |
|-----|----------|---------|-------------|
| `version` | yes | | Always `1`. |
| `provider` | no | Jev | The decision API. See [providers](providers.md). |
| `provider.name` | no | `jev` | `jev`, `openai`, `ollama`, `localai` or `openai-compatible`. |
| `provider.model` | no | per provider | The model identifier, such as `jev-latest`, `gpt-6-luna` or `llama3.1`. |
| `provider.api_key_env` | no | per provider | The environment variable that holds the API key. The key itself never goes into the file. Local servers run without one. |
| `provider.base_url` | no | per provider | The scheme and host of the API, for a proxy, a compatible endpoint or a local server. |
| `provider.timeout` | no | `60`, `300` for chat models | Seconds to wait for one response. |
| `provider.concurrency` | no | `8`, `2` for chat models | How many requests are in flight at once, from 1 to 64. |
| `paths` | no | `[src]` | Directories, files or globs to lint, relative to `decisions.yaml`. |
| `exclude` | no | `[]` | Globs to leave out, relative to `decisions.yaml`. |
| `fail_on` | no | `error` | The weakest severity that makes `check` exit `1`: `error`, `warning`, `info` or `never`. |
| `imports` | no | `[]` | Rule files to load, relative to `decisions.yaml`. |
| `rules` | no | `{}` | Rule definitions and overrides, keyed by rule id. |

Globs use `*` within one path segment, `**` across segments and `?` for one character. A pattern without a wildcard names a file, or a directory with everything below it.

## Imports and overrides

Rule files are read in the order of `imports`. A rule id that appears in a later file overrides the earlier definition key by key. The entries of `rules` in `decisions.yaml` are applied last, in the same way.

An override keeps every value it does not mention:

| Value | Merged by |
|-------|-----------|
| Mappings such as `target`, `decision` and `decision.choices` | Key by key; `decision: {threshold: 0.8}` keeps the imported instructions and type. |
| Lists such as `paths`, `exclude`, `violations`, `decision.levels` and `target.visibility` | Replaced as a whole when set. |
| Scalars such as `severity`, `enabled`, `context` and `description` | Replaced when set. |

Set `enabled: false` to switch an imported rule off. A rule written only in `decisions.yaml` must be complete: `target`, `decision` and `violations` are required, and a partial entry for an id no import defines is reported as an error naming the missing part.

## Schemas

The package ships JSON schemas in `schema/`:

| Schema | Validates |
|--------|-----------|
| `schema/decisions.schema.json` | `decisions.yaml`. Rule entries may be partial. |
| `schema/rules.schema.json` | A rule file. Every rule must be complete. |

Every document is validated against its schema before it is read, by `check`, `plan`, `rules` and `validate` alike. An unknown key, a wrong type, a value outside its range, a misnamed rule id or a missing required key stops the run with exit `2` and a message that names the path and the fix:

```text
Decisions error: decisions.yaml: provider.name must be one of "jev", "openai", not "anthropic". Replace it with one of those values.
Decisions error: rules/team.yaml: rules.team.one is missing the key "violations". Add it: the answers that violate the rule, each with the message and the fix to report.
Decisions error: decisions.yaml: rules has the key "Team One", which is not a rule id made of lower-case letters, digits, dots and hyphens, such as cohesion.method-belongs-to-class. Rename the key.
```

Point an editor at the schema with the `# yaml-language-server: $schema=…` comment `init` writes, and the same checks run while typing. What the schema cannot express, such as a `choice` violation naming an option the decision does not list, is checked when the rules are read and reported in the same form.
