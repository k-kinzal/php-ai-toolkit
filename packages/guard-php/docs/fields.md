# Configuration Fields

The `configuration` section checks single values in the configuration files of other tools: the PHPStan level, a PHPUnit attribute, a Composer setting. An agent that cannot make a check pass can lower that check instead; a field rule turns such an edit into a failure. Rules that know the correct value repair it with `guard apply`.

## Example

```yaml
version: 1
configuration:
  - id: phpstan.level
    file: phpstan.neon
    select: /parameters/level
    assert: {equals: max}
  - id: composer.sort-packages
    file: composer.json
    select: /config/sort-packages
    level: recommended
    assert: {equals: true}
  - id: phpunit.fail-on-warning
    file: phpunit.xml.dist
    format: xml
    select: /phpunit/@failOnWarning
    assert: {equals: 'true'}
  - id: infection.min-msi
    file: infection.json5
    select: /minMsi
    assert: {min: 80}
```

`guard apply` sets the PHPStan level to `max`, `sort-packages` to `true` and `failOnWarning` to `"true"`. Nothing can repair a `minMsi` below 80, because `infection.json5` is check-only and `min` names no single value. Until someone raises it, `apply` writes none of the other repairs either. See [Repairs](#repairs).

## Fields

| Key | Required | Description |
|-----|----------|-------------|
| `id` | yes | Unique rule ID. Reported as the rule of each finding, and used to [override](configuration.md#imports) an imported rule. |
| `file` | yes | The configuration file, relative to `guard.yaml`. |
| `select` | yes | The field: a JSON Pointer, or an XPath for XML. See [Formats](#formats). |
| `assert` | yes | One or more [assertions](#assertions). All must hold. |
| `format` | no | `json`, `yaml`, `yml`, `neon`, `toml`, `xml`, `json5` or `php`. Default: the file extension. Set it for names such as `phpunit.xml.dist`. |
| `level` | no | `required` (default) fails the check; `recommended` warns. |
| `repair` | no | The value `apply` writes when the field is wrong. See [Repairs](#repairs). |

A target file that does not exist is reported at the rule's level and names the field it should contain. A file outside the [collection scope](configuration.md#collection-scope) is skipped.

## Assertions

| Assertion | Holds when |
|-----------|------------|
| `equals: V` | The value equals `V`, including its type: `1`, `'1'` and `true` differ, and so do a list and a mapping. Mapping key order is ignored. |
| `one_of: [A, B]` | The value equals one of the listed values. |
| `min: N`, `max: N` | The value is a number within the bound, which is included. |
| `contains: S` | A string contains `S`, or a list or mapping contains such a string at any depth. `key=value` also matches a mapping entry with that key and string value. |
| `contains_any: [A, B]` | A string or a list entry at any depth equals one of the names. A name with `/` also matches as part of an entry, such as an include path. |
| `not_contains: S` | `contains: S` does not hold. |
| `present: true` | The field exists. |
| `absent: true` | The field does not exist. It cannot be combined with another assertion. |

A missing field fails every assertion except `absent`, even `equals: null`: an explicit `null` and a missing key are different.

## Formats

| Format | `select` | Notes |
|--------|----------|-------|
| `json` | JSON Pointer, such as `/config/sort-packages` | Lists and objects, and scalar types, are kept apart. |
| `yaml`, `yml` | JSON Pointer | Parsed with Symfony YAML. Custom tags are rejected. |
| `neon` | JSON Pointer | Parsed with Nette NEON. |
| `toml` | JSON Pointer | TOML 0.4 syntax. Newer syntax fails without writing. TOML has no `null`. |
| `xml` | XPath that selects one element or attribute | Values are strings, so quote booleans and numbers in YAML; `min` and `max` still read numeric text. Use `local-name()` and `namespace-uri()` for namespaced documents. DTDs and entity declarations are rejected. |
| `json5` | JSON Pointer | Comments and trailing commas are accepted. Check-only. |
| `php` | `/riskyAllowed`, or `/rules/<rule>` | For PHP-CS-Fixer configurations: the literal arguments of `setRiskyAllowed()` and `setRules()`. The file is read, never executed, and a non-literal argument reads as `null`. Check-only. |

In a JSON Pointer, `/` inside a key is written `~1` and `~` is written `~0`. A pointer into a list addresses an existing index; Guard does not extend lists or replace a scalar parent with a mapping. An XPath that matches several nodes is an error.

## Repairs

`equals` is its own repair. Every other assertion needs an explicit `repair` before `apply` can change the field, and the repair must satisfy all assertions of the rule:

```yaml
- id: application.driver
  file: settings.yaml
  select: /driver
  assert: {one_of: [mysql, pgsql]}
  repair: mysql
```

A field that already satisfies the rule is kept, so `pgsql` is not replaced by `mysql`. Without a repair, or in a `json5` or `php` file, the finding stays and names the required change. Both levels are repaired.

Guard plans the changes to every file, then checks rules that touch the same file against the planned result. While a required rule stays unsatisfied, `apply` writes no file at all and reports the planned files as `blocked`. A missing mapping key is created along its pointer, and a missing XML attribute on its existing element.

### Writing

A file that does not change keeps every byte. A changed JSON, YAML, NEON or TOML file is serialized again, so its indentation may change and its comments are lost; the result is parsed again and must hold the same data apart from the repaired fields. A changed XML file keeps its comments and every other node.

Guard writes each file to a temporary file in the same directory and replaces the original atomically, keeping its permissions, and refuses to write when the file changed since it was read. Each replacement is atomic, but a failure in the middle of several files can leave the earlier ones written; fix the cause and run `guard check` again.

Targets must be regular files inside the project. Paths that leave the project or pass through a symbolic link are rejected, and a rule cannot edit `guard.yaml` itself.
