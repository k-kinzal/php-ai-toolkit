# Configuration

`guard.yaml` declares the policies of a project. Commands read `guard.yaml` in the working directory; pass `--config FILE` to use another. Every path in it is relative to the directory of the main `guard.yaml`, whatever the working directory and wherever an import lives.

## Example

```yaml
version: 1
imports:
  - vendor/k-kinzal/guard-php/rules/metrics.yaml
  - vendor/k-kinzal/guard-php/rules/structure.yaml
  - vendor/k-kinzal/guard-php/rules/phpstan.yaml
  - vendor/k-kinzal/guard-php/rules/readme-md.yaml
metrics:
  source: [src]
  profiles:
    standard:
      limits:
        file: {lines: 800}
documentation:
  scan: ['*.md']
configuration:
  - id: phpstan.level
    level: recommended
```

The imports supply the shipped rules; the rest of the file changes only what this project does differently. Here the file length limit is raised, every Markdown file in the root must be declared, and the PHPStan level becomes a recommendation instead of a requirement.

## Fields

| Key | Required | Description |
|-----|----------|-------------|
| `version` | yes | Always `1`. An imported file may omit it. |
| `imports` | no | Other policy files to load first. See [Imports](#imports). |
| `collect` | no | The files Guard may read at all. See [Collection scope](#collection-scope). |
| `metrics` | no | PHP size and complexity limits. See [metrics](metrics.md). |
| `structure` | no | Directory rules. See [structure](structure.md). |
| `documentation` | no | Markdown document declarations. See [documents](documents.md). |
| `configuration` | no | Rules for values in tool configuration files. See [configuration fields](fields.md). |
| `extensions` | no | Policy or structurer classes and their named constructor arguments, as `Class: {options}`. See [extensions](extensions.md). |

Unknown keys, invalid types, contradictory bounds and two `configuration` rules with the same `id` in one file are errors with exit code `2`. Error messages name a section by the file it came from historically, such as `tree.yaml` for `structure` or `doc-guard.yaml` for `documentation`.

## Imports

Each entry of `imports` is a path relative to the file that lists it, or an absolute path. An imported file may import more files. Guard loads the imports in order and then applies the file itself, so a later import overrides an earlier one and the project file overrides all of them. A circular import is an error.

An override keeps every imported value it does not mention:

| Key | Merged by |
|-----|-----------|
| `collect.include`, `collect.exclude` | Each list replaces the imported one when set. |
| `metrics.profiles` | Profile name. A profile replaces only the keys it sets, and inside `limits` only the metrics it sets. |
| `metrics.source`, `metrics.exclude`, `metrics.default`, `metrics.assignments` | Each replaces the imported value when set. |
| `structure.directories` | `path`. A rule replaces only the keys it sets; a new path is added. |
| `structure.paths`, `structure.exclude` | Each replaces the imported list when set. |
| `documentation.files` | Document path. A declaration replaces only the keys it sets, so a project can add `headings` to a document whose `outlines` come from a preset. |
| `documentation.scan`, `documentation.exclude` | Each replaces the imported list when set. |
| `configuration` | `id`. A rule replaces only the keys it sets; a new `id` is added. |
| `extensions` | Class name. A later declaration replaces that class's whole option mapping and keeps its position. |

To drop an imported rule, remove the import and write the rules you want in the project file instead. A policy without `imports` is a complete custom policy.

## Presets

The package ships presets in `vendor/k-kinzal/guard-php/rules/`. Import only those you want; [`guard init`](cli.md#init) selects the ones that match the project.

| Preset | Checks |
|--------|--------|
| `metrics.yaml` | The standard [metric limits](metrics.md#limits) for `src`. |
| `structure.yaml` | The standard [directory rules](structure.md) for `src`, `tests/Unit` and `skills`, scanning from the project root. |
| `disable-doc.yaml` | No `docs/` directory in the project root, even an empty one. |
| `readme-md.yaml` | The outline and badges of `README.md`. See [documents](documents.md#shipped-declarations). |
| `agents-md.yaml` | The outline of `AGENTS.md`. |
| `claude-md.yaml` | `CLAUDE.md` contains exactly `@AGENTS.md`. |
| `phpstan.yaml`, `phpstan-guard-rules.yaml` | PHPStan level, includes and toolkit rules. |
| `phpunit9.yaml` … `phpunit13.yaml` | The PHPUnit configuration of each major. |
| `doctest9.yaml` … `doctest13.yaml` | The doctest suite in the PHPUnit configuration of each major. |
| `php-cs-fixer.yaml`, `phpcs.yaml`, `deptrac.yaml`, `infection.yaml`, `composer.yaml` | The configuration of each tool. |
| `github-actions.yaml`, `mutation.yaml` | The CI workflow and the scheduled mutation workflow. |
| `pbt.yaml`, `fuzz.yaml`, `phpbench.yaml`, `docgen.yaml` | The configuration of each tool, for projects that use it. |

## Collection scope

`collect` limits which files Guard reads. Every policy, built-in or from an extension, sees only what lies inside it:

```text
files a policy sees = (collect.include − collect.exclude) ∩ files the policy asks for
```

```yaml
version: 1
collect:
  include: [src, docs, README.md, schemas/catalog.xsd]
  exclude: ['**/generated/**', 'docs/archive/**']
```

| Key | Default | Description |
|-----|---------|-------------|
| `include` | `['**']` | Paths and globs to read. A directory includes everything below it. `[]` selects nothing. |
| `exclude` | `[]` | Paths and globs removed from `include`. An excluded directory is skipped with everything below it. |

`*` matches within one path segment and `**` matches any number of segments. Absolute paths, `..` and backslashes are rejected. Symbolic links are not followed.

When `collect` is set, it replaces the per-section reading scopes `metrics.source` and `metrics.exclude`, `structure.paths` and `structure.exclude`, and `documentation.exclude`, including values from presets. Metrics then read every `.php` file in the scope. Assignments, directory rule paths, declared documents, scan patterns and configuration rule files still choose what each policy checks.

A file outside the scope is treated as if a policy had not asked for it: a declared document or configuration file outside the scope is skipped, not reported as missing. Without `collect`, each section keeps its own scope settings.

Guard walks the file system once for all policies, reads each file at most once and parses it at most once per format, however many policies ask for it.
