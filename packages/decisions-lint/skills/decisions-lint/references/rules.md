# Rule authoring

A rule file is YAML with `version: 1` and `rules`, keyed by id (`group.name`, lower case, dots and hyphens). Validate it with `decisions-lint validate FILE`.

```yaml
version: 1
rules:
  ddd.usecase-domain-leak:
    description: A use case coordinates the domain; it does not encode business rules.
    severity: error
    paths: ['src/**/UseCase/**/*.php']
    target: {kind: method, visibility: [public], exclude_names: ['__*']}
    context: class
    decision:
      type: choice
      instructions: >-
        {class}::{name} is a use case method. Classify the knowledge its body
        encodes: delegating to repositories and domain objects is orchestration;
        a conditional or calculation expressing a business policy is a domain
        rule; SQL, HTTP or framework details are infrastructure.
      choices:
        orchestration: Loads domain objects, calls their behaviour, persists results.
        domain_rule: Encodes a rule, invariant or calculation that belongs in the domain.
        infrastructure: Contains database, HTTP, file or framework details.
      threshold: 0.6
    violations:
      - answer: domain_rule
        message: 'Use case {class}::{name} encodes a business rule that belongs in the domain layer.'
        fix: 'Move the rule into the entity, value object or domain service it describes, and have {name}() call it.'
```

## Parts

| Key | Notes |
|-----|-------|
| `target.kind` | `file`, `class`, `interface`, `trait`, `enum`, `method`, `function`, `property`. `visibility`, `names`, `exclude_names`, `static` narrow it. |
| `context` | `declaration` (the declaration only), `class` (default; the enclosing class or the file for a function), `file`. Same declaration and context across rules share one request. |
| `decision.type` | `predicate` (yes/no with probability), `choice` (`choices` name: description), `score` (`levels` 2–10, lowest first, level 0 upward). |
| `decision.threshold` | 0–1, default 0.5: the probability a predicate needs, or the confidence a choice or score needs, to count as a violation. |
| `violations[]` | `answer: true\|false` (predicate), `answer: option` (choice), `min_score`/`max_score` (score); `message` and `fix` are required templates. The first matching entry is reported. |

Placeholders for `instructions`, `message`, `fix`: `{file}`, `{line}`, `{column}`, `{kind}`, `{name}`, `{class}`, `{namespace}`, `{qualified_name}`, `{signature}`, `{visibility}`, `{rule}`, `{threshold}`; in messages also `{answer}`, `{probability}`, `{confidence}`, `{choice}`, `{score}`. An unknown placeholder is rejected when the rule is read.

## Writing instructions

- Ask one judgement. Split a question with two conditions into two rules.
- State the boundary: what counts as yes and what counts as no, with the kind of code that falls on each side.
- Phrase a predicate so that yes is the acceptable case and `answer: false` is the violation, as the shipped rules do.
- Give every choice option a description, make the options exclusive, and add an `other` option when inputs may fall outside the list.
- Describe score levels so each can be applied on its own; level 0 is the lowest.

## Writing message and fix

- `message` says what is wrong about this declaration: name it with `{class}` and `{name}`.
- `fix` says what to change, as an instruction that can be carried out without reading the rule: where to move the method, what to rename it to, which interface to introduce.

## Tuning

1. `decisions-lint plan --rule=ID` to confirm what the rule selects and how many requests it costs.
2. `decisions-lint check --rule=ID --format=json` on code whose verdict you know.
3. Compare the findings and their `confidence` with your judgement. Raise `threshold` to drop hesitant answers; sharpen `instructions` when confident answers are wrong; narrow `target` or `paths` when the rule judges declarations it should not.
4. Start with `severity: warning`; make the rule an `error` once its findings are ones the team acts on.

## Overriding from decisions.yaml

Under `rules` in `decisions.yaml`, an entry for an imported id overrides only the keys it sets: mappings merge key by key, lists and scalars replace. `enabled: false` switches a rule off. A rule defined only in `decisions.yaml` must be complete.
