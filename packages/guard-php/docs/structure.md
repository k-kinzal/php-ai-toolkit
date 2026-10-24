# Structure

The `structure` section constrains directories: which files and subdirectories each may contain, how they are named, and how many there are. It keeps an agent from adding a `Helper` class, a `scripts/` directory or a fifty-file namespace. Guard does not move or rename files; each finding stays until the tree is changed.

## Example

```yaml
version: 1
structure:
  paths: [.]
  exclude: [.git, vendor, build]
  directories:
    - path: '**'
      deny_dirs: [scripts, Scripts]
    - path: src
      allow: []
    - path: 'src/**'
      forbid_empty: true
      allow: ['*.php']
      deny: ['*Helper.php', '*Manager.php', '*Util.php']
      file_case: pascal
      dir_case: pascal
      max_files: 15
      max_dirs: 20
    - path: 'skills/*'
      require: [SKILL.md]
```

No directory anywhere may be called `scripts`. `src` holds only directories, and every directory below it holds at most 15 PascalCase PHP files. Every skill directory has a `SKILL.md`. The shipped `rules/structure.yaml` contains these rules and more.

## Fields

| Key | Default | Description |
|-----|---------|-------------|
| `paths` | `['.']` | Directories to scan, relative to `guard.yaml`. Each must exist. |
| `exclude` | `[]` | Files and directories to leave out. An excluded directory is skipped with everything below it. |
| `directories` | `[]` | Rules, each a `path` pattern and any of the [constraints](#constraints). |

`.` scans the project root itself, which is how a rule reaches the root, dotted directories such as `.github`, and everything outside the source roots. The root is reported as `.`, and every directory below keeps its path relative to the root, such as `src` or `.github/workflows`, whether the scan starts at `.` or at `src`. When [`collect`](configuration.md#collection-scope) is set, it replaces `paths` and `exclude`.

Every rule whose `path` matches a directory applies to it, subject to its optional `when_files` condition. Overlapping rules do not override or merge; each reports its own findings. Unknown keys in a rule are errors, so a typo such as `max_file` cannot switch a check off.

## Patterns

A rule `path` matches the whole directory path, one segment at a time:

| Pattern | Matches |
|---------|---------|
| `src` | `src` only. |
| `src/*` | The directories directly inside `src`. |
| `src/**` | `src` and every directory below it. Unlike `.gitignore`, `**` also matches zero segments. |
| `**/Rule` | A directory named `Rule` at any depth. |
| `.` | The project root only. |
| `**` | The project root and every directory below it. |

`*` never crosses a `/`, and patterns are anchored at both ends, so `PhpStan/Rule` does not match `src/PhpStan/Rule`.

`exclude` entries are matched differently: each is an `fnmatch` glob over the whole relative path, so `*` may cross `/`.

## Conditional rules

`when_files` is an optional list of filename globs. The rule applies only when at least one file directly in the matched directory matches at least one glob. Subdirectory names and files deeper in the tree do not activate it. Matching uses the collected entries after exclusions, including exclusions from `collect` when set.

Omitting `when_files` applies the rule unconditionally; `when_files: []` never activates it. The condition gates every constraint in that rule. Other rules matching the same directory still apply independently. `guard rules` includes the condition in each constraint's details; it is not a separate violation.

## Constraints

| Key | Value | Checks | Rule ID |
|-----|-------|--------|---------|
| `max_files` | integer ≥ 1 | Files directly in the directory. | `structure.max_files` |
| `max_dirs` | integer ≥ 1 | Subdirectories directly in the directory. | `structure.max_dirs` |
| `max_total_files` | integer ≥ 1 | Files in the whole subtree. | `structure.max_total_files` |
| `max_depth` | integer ≥ 1 | Nesting below the directory, which is depth 0. Each deeper directory is reported. | `structure.max_depth` |
| `allow` | globs | Every file name matches one of them. `[]` allows no file. | `structure.disallowed_file` |
| `deny` | globs | No file name matches any of them. | `structure.denied_file` |
| `allow_dirs` | globs | Every subdirectory name matches one of them. | `structure.disallowed_dir` |
| `deny_dirs` | globs | No subdirectory name matches any of them. | `structure.denied_dir` |
| `require` | names | Each named file exists directly in the directory. | `structure.missing_required_file` |
| `require_dirs` | names | Each named subdirectory exists directly in the directory. A same-named file does not satisfy this check. | `structure.missing_required_dir` |
| `forbid_empty` | `true` | The directory has at least one entry after exclusions. | `structure.empty_directory` |
| `file_case` | `pascal`, `camel`, `snake`, `kebab` | File names follow the convention. | `structure.file_case` |
| `dir_case` | `pascal`, `camel`, `snake`, `kebab` | Subdirectory names follow the convention. | `structure.dir_case` |

A constraint that a rule does not set is not checked. Leaving `allow` out allows every file; `allow: []` allows none. Limits are themselves allowed: `max_files: 15` accepts 15 files and reports the sixteenth.

To require a directory to hold exactly one manifest, combine `require` and `allow`:

```yaml
- path: 'modules/*'
  require: [module.yaml]
  allow: [module.yaml]
```

### Behat specifications

The shipped `rules/structure.yaml` includes these rules:

```yaml
- path: 'spec/**'
  when_files: ['*.feature']
  allow: ['*.feature']
  require_dirs: [Context]
- path: 'spec/**/Context/**'
  allow: ['?*Context.php']
  file_case: pascal
```

Feature files can live directly in `spec/` or in categories at any depth. Every directory containing a `.feature` file may contain only `.feature` files and must have its own direct `Context/` subdirectory. A context directory in a parent or sibling does not satisfy that requirement. Directories without feature files do not acquire these conditional constraints.

Every file in `Context/` and its descendants must have a PascalCase name ending in `Context.php`, with a non-empty prefix, such as `AccountContext.php`. These filename constraints apply even before a feature file exists. For example, both layouts pass:

```text
spec/
  login.feature
  Context/
    AccountContext.php

spec/
  Account/
    login.feature
    Context/
      AccountContext.php
  Billing/
    pay.feature
    Context/
      BillingContext.php
```

Import `rules/test-dependencies.yaml` as well to require the Composer development mapping `"Spec\\": "spec/"` and enforce the existing test dependency boundaries. For an initial configuration, use `guard init --import=structure,test-dependencies`. With that mapping, `spec/Account/Context/AccountContext.php` belongs to `Spec\Account\Context`. See [test dependencies](fields.md#test-dependencies) for mapping repairs and Deptrac rules.

### Case conventions

| Convention | Pattern |
|------------|---------|
| `pascal` | `^[A-Z][A-Za-z0-9]*$` |
| `camel` | `^[a-z][A-Za-z0-9]*$` |
| `snake` | `^[a-z0-9]+(_[a-z0-9]+)*$` |
| `kebab` | `^[a-z0-9]+(-[a-z0-9]+)*$` |

`file_case` checks the part of the name before the first dot, so `AiReporter.php` is checked as `AiReporter`. Names that start with a dot are skipped by both conventions.
