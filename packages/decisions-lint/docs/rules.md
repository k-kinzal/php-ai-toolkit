# Rules

A rule names the declarations it judges, the question it asks about each, and which answers are violations. Rules live in YAML files that `decisions.yaml` imports, so a team can ship its conventions as a package and every project can adjust severities and thresholds.

## Example

```yaml
# yaml-language-server: $schema=vendor/k-kinzal/decisions-lint/schema/rules.schema.json
version: 1
rules:
  cohesion.method-belongs-to-class:
    description: A public method does the kind of work its class name promises.
    severity: warning
    target:
      kind: method
      visibility: [public]
      exclude_names: ['__*']
    context: class
    decision:
      type: predicate
      instructions: >-
        The class {class} is named for one responsibility. Read the whole
        class, then judge whether the public method {name} is an operation a
        reader would expect {class} to own. Yes when the method is part of
        that responsibility. No when it serves another concept.
      threshold: 0.7
    violations:
      - answer: false
        message: 'Method {name}() does not belong to {class}: it is not part of the responsibility the class name promises.'
        fix: 'Move {name}() to the class that owns that responsibility and have {class} call it, or rename {class} so its name covers {name}().'
```

## Keys

| Key | Required | Default | Description |
|-----|----------|---------|-------------|
| `description` | no | `''` | What the rule protects, shown by `rules`. |
| `severity` | no | `error` | `error`, `warning` or `info`. |
| `enabled` | no | `true` | Whether the rule runs. |
| `paths` | no | the configured paths | File globs, relative to `decisions.yaml`, the rule applies to. |
| `exclude` | no | `[]` | File globs the rule skips. |
| `target` | yes | | Which declarations are judged. See [targets](#targets). |
| `context` | no | `class` | How much source the model reads with the declaration. See [context](#context). |
| `decision` | yes | | The question. See [decisions](#decisions). |
| `violations` | yes | | The answers that violate the rule. See [violations](#violations). |

Rule ids are lower-case letters, digits, dots and hyphens, such as `ddd.usecase-domain-leak`. The part before the first dot groups related rules.

## Targets

| Key | Description |
|-----|-------------|
| `kind` | `file`, `class`, `interface`, `trait`, `enum`, `method`, `function` or `property`. |
| `visibility` | Keep only `public`, `protected` or `private` members. |
| `names` | Keep only declarations whose short name matches one of these globs. |
| `exclude_names` | Drop declarations whose short name matches one of these globs, such as `__*` for magic methods. |
| `static` | `true` keeps static members only, `false` non-static only. |

Declarations are found by tokenizing the file, without executing it. Methods and functions inside other bodies, closures and anonymous classes are not targets. The reported line and column are those of the declaration's first keyword or modifier, so a method is reported at its `public`.

`kind: file` judges each matching file once, as a whole, and reports at line 1. It also works for files that are not PHP, such as Markdown, so a rule can judge documentation.

## Context

The model reads the declaration itself, including its docblock, and one of:

| `context` | Source sent with the declaration | Use when |
|-----------|--------------------------------|----------|
| `declaration` | Nothing more. | The judgement depends only on the declaration, such as a name against its body. |
| `class` | The whole enclosing class, or the whole file for a function. | The judgement needs the siblings, such as whether a method fits its class. |
| `file` | The whole file. | The judgement needs imports or other declarations in the file. |

Rules that judge the same declaration with the same context share one request. A rule with a different context costs one more request per declaration, so prefer `class` unless a rule needs more or less.

## Decisions

| Key | Description |
|-----|-------------|
| `type` | `predicate`, `choice` or `score`. |
| `instructions` | The question, with [placeholders](#placeholders). State the boundary between the answers in words the model can apply. |
| `choices` | For `choice`: option name to description. Give every option a description and add an `other` option when the list may not cover every input. |
| `levels` | For `score`: 2 to 10 level descriptions, from the lowest (level 0) to the highest. |
| `threshold` | From 0 to 1; default `0.5`. The probability a predicate answer needs, or the confidence a choice or score needs, before it counts as a violation. |

Phrase a predicate so that *yes* is the unremarkable case and *no* is the violation, as the shipped rules do, and state in the instructions what counts as each. A choice should have options that are exclusive and together cover every input. A score should have levels whose descriptions a reader could apply without seeing the others.

## Violations

Each entry of `violations` describes one answer and what to report for it. The first entry the answer satisfies is reported.

| Key | Required | Description |
|-----|----------|-------------|
| `answer` | predicate, choice | `true` or `false` for a predicate; an option name for a choice. |
| `min_score` | score | The lowest score that violates, inclusive. |
| `max_score` | score | The highest score that violates, inclusive. At least one bound is required. |
| `message` | yes | What is wrong, as a template. |
| `fix` | yes | What to change so the rule passes, as a template. |

A predicate violation with `answer: false` fires when `1 - probability` reaches `threshold`; with `answer: true` when `probability` does. A choice violation fires when the model picked that option with at least `threshold` confidence. A score violation fires when the probability-weighted score is within the bounds and the confidence reaches `threshold`.

Write `fix` as an instruction a reader or an agent can carry out without further context: name the method, say where it should go or what it should be called. A finding without a fix only says that something is wrong; the fix is what makes it actionable.

## Placeholders

`instructions`, `message` and `fix` may use these placeholders. An unknown placeholder is reported when the rule is read.

| Placeholder | Value |
|-------------|-------|
| `{file}` | The path relative to `decisions.yaml`. |
| `{line}`, `{column}` | The position of the declaration. |
| `{kind}` | `method`, `class`, `file` and so on. |
| `{name}` | The short name: `analyze`, `Analyzer`, `README.md`. |
| `{class}` | The short name of the class the declaration is or belongs to; empty for a function or a file. |
| `{namespace}` | The namespace without a leading backslash. |
| `{qualified_name}` | `App\Analysis\Analyzer::analyze`. |
| `{signature}` | The declaration without its body, on one line. |
| `{visibility}` | `public`, `protected` or `private`. |
| `{rule}` | The rule id. |
| `{answer}` | In messages: `yes` or `no`, the chosen option, or the score. |
| `{probability}`, `{confidence}`, `{choice}`, `{score}` | In messages: the answer's numbers, two decimals. |
| `{threshold}` | The rule's threshold. |

## Shipped rule files

| File | Rules |
|------|-------|
| `rules/cohesion.yaml` | `cohesion.method-belongs-to-class` (predicate, warning), `cohesion.property-belongs-to-class` (predicate, info): members do the kind of work their class name promises. |
| `rules/ddd.yaml` | `ddd.usecase-domain-leak` (choice, error): a use case orchestrates and does not encode business rules or infrastructure. `ddd.repository-business-rule` (predicate, warning): a repository persists and does not decide. Both are scoped to conventional paths; override `paths` to match the project. |
| `rules/naming.yaml` | `naming.method-says-what-it-does` (score, warning): a method name states what the method does. |

Import them from `vendor/k-kinzal/decisions-lint/rules/` and override what the project does differently.

## Writing a rule

1. Write the question as you would ask a reviewer, then cut it to the decision: one predicate, one choice among exclusive options, or one scale.
2. Choose the narrowest `target` and `context` that let the model answer.
3. Write `message` and `fix` so that a reader who sees only the finding knows what to do.
4. Run `decisions-lint validate rules/team.yaml`, then `decisions-lint plan` to see what the rule selects.
5. Run `decisions-lint check --rule=team.rule --format=json` on code you know, compare the findings with your own judgement, and raise `threshold` until the findings are ones you would act on.
