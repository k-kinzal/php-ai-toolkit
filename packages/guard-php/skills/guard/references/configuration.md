# Configuration fields

The `configuration` section checks single values in the configuration files of other tools, such as the PHPStan level, a PHPUnit attribute or a Composer setting. An agent that cannot make a check pass can lower the check instead; a field rule turns that edit into a finding. Rules that know the correct value are repaired by `guard apply`. The semantics are in [the configuration fields reference](../../../docs/fields.md).

## Resolving findings

The finding's rule ID is the rule's `id`, such as `phpstan.level` or `phpunit13.executionOrder`. Its message names the file, the selected field and the assertion.

1. Run `vendor/bin/guard apply --dry-run` to see which files `apply` would change.
2. When files are reported as `blocked`, a required rule has no repair or targets a check-only format (`json5`, `php`). Edit that field by hand to a value that satisfies the assertion, then run the dry run again. While one required rule stays unsatisfied, `apply` writes no file at all.
3. Run `vendor/bin/guard apply`, and review the diff. A rewritten JSON, YAML, NEON or TOML file loses its comments and may change its indentation; restore a comment that documents a decision.
4. Run the tool whose configuration changed. A raised PHPStan level or a stricter PHPUnit attribute usually reports new problems; fix them in code and tests.

| Finding | Practice |
|---------|----------|
| The tool fails after the repaired value | Fix the code or the tests the tool now reports. Do not lower the value again. |
| A `min` or `max` bound fails, such as `infection.min-msi` | Improve what the number measures, such as the tests that leave mutants alive, until the bound holds. |
| A `not_contains` or `absent` rule fails, such as a PHPStan baseline in `includes` | Remove the entry and fix the errors it suppressed. |
| The target file does not exist | Create it with a compliant value. Removing the rule is a policy change. |
| The file uses another name, such as `phpstan.neon.dist` | That is a policy override: a human sets `file` (and `format` if needed) for the rule's `id` in `guard.yaml`. |

### Changes that hide a finding instead of fixing it

- Setting `level: recommended`, or overriding `assert` or `repair` for the rule's `id` in `guard.yaml`.
- Moving the setting to a place the rule does not read, such as a second configuration file, an environment variable, a command-line flag in a Composer script or CI step, or an inline suppression in the code.
- Adding the file to `collect.exclude`, which makes the rule skip it silently.

## Writing rules

- Name the `id` after the tool and the field, such as `composer.sort-packages`. Projects override an imported rule by `id`, setting only the keys that differ.
- Prefer `equals`; it is its own repair. Give `one_of`, `min`, `max` and `contains` rules an explicit `repair` when one value is the right default; a field that already satisfies the rule is kept.
- Set `format` when the extension does not say it, such as `format: xml` for `phpunit.xml.dist`. XML values are strings, so quote booleans and numbers.
- Use `not_contains` or `absent` for the settings that switch a check off, such as a baseline include or an ignore list.
- Use `level: required` for decisions that hold the quality bar, and `recommended` only for preferences whose violation does not weaken a check.
- Select exactly one node. A JSON Pointer writes `/` inside a key as `~1`; an XPath that matches several nodes is an error.
- Keep one rule per field. Two rules with the same `id` in one file are an error, and two rules on the same field with different `equals` values can never both pass.

```yaml
configuration:
  - id: phpstan.no-baseline
    file: phpstan.neon
    select: /includes
    assert: {not_contains: phpstan-baseline.neon}
  - id: application.driver
    file: settings.yaml
    select: /driver
    assert: {one_of: [mysql, pgsql]}
    repair: mysql
```
