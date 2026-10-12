# Configuration Fields

The `configuration` section checks single values in the configuration files of other tools: the PHPStan level, a PHPUnit attribute, a Composer setting. An agent that cannot make a check pass can lower that check instead; a field rule turns such an edit into a failure. Rules that know the correct value repair it with `guard fix`.

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

`guard fix` sets the PHPStan level to `max`, `sort-packages` to `true` and `failOnWarning` to `"true"`. The `minMsi` rule needs an explicit `repair: 80`, because `min` permits many values and does not choose one. Until that required rule is satisfied, `fix` writes none of the other repairs either. See [Repairs](#repairs).

## Fields

| Key | Required | Description |
|-----|----------|-------------|
| `id` | yes | Unique rule ID. Reported as the rule of each finding, and used to [override](configuration.md#imports) an imported rule. |
| `file` | yes | The configuration file, relative to `guard.yaml`. |
| `select` | yes | The field: a JSON Pointer, or an XPath for XML. See [Formats](#formats). |
| `assert` | yes | One or more [assertions](#assertions). All must hold. |
| `format` | no | `json`, `yaml`, `yml`, `neon`, `toml`, `xml`, `json5` or `php`. Default: the file extension. Set it for names such as `phpunit.xml.dist`. |
| `level` | no | `required` (default) fails the check; `recommended` warns. |
| `message` | no | Diagnostic describing the problem and the edit that resolves it. Supports `{file}`, `{select}`, `{expectation}` and `{fix}` placeholders. |
| `repair` | no | The value `fix` writes when the field is wrong. See [Repairs](#repairs). |

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
| `json5` | JSON Pointer | Quoted keys, comments and trailing commas are accepted. Repairs serialize valid JSON, which is also valid JSON5. |
| `php` | JSON Pointer | A returned literal array, or `/riskyAllowed` and `/rules/<rule>` for PHP-CS-Fixer fluent configuration. Repairs replace the selected literal argument or returned array and can add missing setter calls. The file is never executed; ambiguous calls and dynamic values are errors when their value cannot be determined. |

In a JSON Pointer, `/` inside a key is written `~1` and `~` is written `~0`. A pointer into a list addresses an existing index or its next index, so `/includes/0` can create the first entry. Missing parents are created as maps, or lists when the next segment is a numeric index. Guard does not create gaps in lists or replace scalar parents with containers. An XPath that matches several nodes is an error.

A rule may include a non-empty `message` describing the problem and the required edit. Guard displays the same diagnostic in `rules`, `check` and remaining `fix` findings, with placeholders resolved after imports and overrides. If omitted, Guard generates a concrete diagnostic from the assertions. See [Rule messages](cli.md#rule-messages).

## Repairs

`equals` is its own repair. Every other assertion needs an explicit `repair` before `fix` can change the field, and the repair must satisfy all assertions of the rule:

```yaml
- id: application.driver
  file: settings.yaml
  select: /driver
  assert: {one_of: [mysql, pgsql]}
  repair: mysql
```

A field that already satisfies the rule is kept, so `pgsql` is not replaced by `mysql`. Without a repair, the finding stays and names the required change. Every supported format uses the same check-and-repair contract: select a field, write its repair value only when the assertion fails, then recheck the same assertions. Both levels are repaired.

Guard plans the changes to every file, then checks rules that touch the same file against the planned result. While a required rule stays unsatisfied, `fix` writes no file at all and reports the planned files as `blocked`. Missing fields and their parent containers are created along the selector. XML creation starts at the existing root and supports named elements, attribute-equality predicates such as `testsuite[@name="doctest"]`, and a final attribute such as `/@value`. XPath can still select existing values using more complex expressions; an ambiguous or indeterminate creation path is an error.

### Partial requirements and severity

`required` and `recommended` control whether a remaining violation fails the command; they do not change the matching condition. `equals` requires equality of the selected value. `one_of`, `contains`, presence checks, and separate rules for individual fields keep their existing meanings. A rule selecting one XML attribute does not require the whole element to match, and adding that attribute preserves custom attributes, sibling elements, and their values. A compliant value is never replaced by the repair's default.

### Enabling PHPUnit features

Import the presets for the PHPUnit major the project uses, then run `guard fix --dry-run` and `guard fix`:

```yaml
version: 1
imports:
  - vendor/k-kinzal/guard-php/rules/phpunit13.yaml
  - vendor/k-kinzal/guard-php/rules/doctest13.yaml
```

Starting with an existing `phpunit.xml.dist`, these rules can add the reporter extension, Doctest suite and extension, source-directory parameter, and PHPUnit settings. Existing suites and extension parameters are retained. The Doctest suite repair points at `vendor/k-kinzal/doctest-php/`; the source-directory repair defaults to `src`. Override the corresponding rule's `repair` to choose another default; a directory value already present remains unchanged. Install the corresponding Composer packages before running PHPUnit. Guard configures their activation; it does not install dependencies.

### Writing

A file that does not change keeps every byte. A changed JSON, JSON5, YAML, NEON or TOML file is serialized again, so its indentation may change and its comments are lost; the result is parsed again and must hold the same data apart from the repaired fields. A changed XML file keeps unrelated nodes and comments. PHP repairs preserve statements outside the replaced literal; comments within that literal may be lost.

Guard writes each file to a temporary file in the same directory and replaces the original atomically, keeping its permissions, and refuses to write when the file changed since it was read. Each replacement is atomic, but a failure in the middle of several files can leave the earlier ones written; fix the cause and run `guard check` again.

Targets must be regular files inside the project. Paths that leave the project or pass through a symbolic link are rejected, and a rule cannot edit `guard.yaml` itself.
