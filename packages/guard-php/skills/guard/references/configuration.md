# Configuration fields

Configuration findings use the rule's own `id`, such as `phpstan.level`, rather
than a `configuration.*` prefix. Inspect the target field and expected value with:

```bash
vendor/bin/guard rules --query=phpstan.level
vendor/bin/guard check --fixable
vendor/bin/guard fix --dry-run
```

## Repairs

`equals` supplies its own repair value. Other assertions need an explicit
`repair`; a compliant field is left unchanged. Every supported format can be
checked and repaired using the same selector. Missing fields and their parents
can be created; list indexes may append the next entry. Missing files, ambiguous
selectors, or conflicting rules need manual changes.

While a required configuration rule remains unsatisfied in the planned result,
no repairs are written and the action is `blocked`. Resolve that rule and preview
again. Metric, structure, and document findings do not block these writes.

A changed JSON, JSON5, YAML, NEON, or TOML file is serialized again and may lose comments
or change indentation. XML preserves unrelated nodes and comments; PHP preserves
code outside the replaced literal. A rule for one attribute or field leaves
custom sibling settings intact. Severity and matching conditions remain separate:
`required` fails the run and `recommended` warns; neither implies whole-element
equality. Review the diff
and run the affected tool after `guard fix`.

## Rule fields

- Define `id`, `file`, `select`, and `assert`. Imports merge rules by `id`, so an
  override need only specify the fields being changed.
- Use JSON Pointer for JSON, YAML, NEON, and TOML, and a single-node XPath for XML.
  A slash inside a pointer key is escaped as `~1`.
- Set `format: xml` for filenames such as `phpunit.xml.dist`. XML values are
  strings; quote booleans and numbers in assertions.
- `required` is the default level; `recommended` produces warnings.
- Messages should name the file and field, the expected value, and the edit.
  Templates accept `{file}`, `{select}`, `{expectation}`, and `{fix}`.

```yaml
version: 1
configuration:
  - id: application.driver
    file: settings.yaml
    select: /driver
    assert: {one_of: [mysql, pgsql]}
    repair: mysql
```

See the [field schema](../../../docs/fields.md)
for assertions, formats, and write behavior.
