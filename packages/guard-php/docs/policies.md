# Policies

An AI agent that is asked to make a test pass will also lengthen a method, add a `Helper` class, open a new section in the README or lower the PHPStan level, if nothing stops it. Each of those changes looks reasonable alone. Guard declares what the project has decided about them in `guard.yaml`, reports every change that departs from it, and repairs the departures that have exactly one correct fix.

## Policies

| Section | What it checks | Rule IDs | Repaired by `fix` |
|---------|----------------|----------|---------------------|
| `metrics` | Lines and cyclomatic complexity of PHP files, classes, functions and methods. See [metrics](metrics.md). | `metrics.*` | no |
| `structure` | What each directory may contain, how entries are named, and how many there are. See [structure](structure.md). | `structure.*` | no |
| `documentation` | The headings, outline, badges or exact content of Markdown files, and Markdown files nobody declared. See [documents](documents.md). | `documentation.*` | no |
| `configuration` | Values in the configuration files of other tools, such as `phpstan.neon` or `phpunit.xml.dist`. See [configuration fields](fields.md). | the rule's `id` | yes, when the rule has a repair |
| `extensions` | Whatever a project or a package adds. See [extensions](extensions.md). | chosen by the extension | when the extension proposes a change |

A section that `guard.yaml` neither writes nor [imports](configuration.md#imports) is not checked.

## Required and recommended

Every finding is either required or recommended. A required finding is reported as `error` and fails the command with exit code `1`. A recommendation is reported as `warning` and leaves the exit code at `0`. Configuration rules choose their level with `level`; metric, structure and document findings are always required.

## What is repaired

`guard fix` only writes configuration fields. A field has one value that satisfies its rule, or a declared fallback, so writing it is a deterministic edit. Splitting a method, renaming a class or rewriting a section is not, so those findings remain until someone edits the code or the document.

Each message names the offending file and symbol, what the policy expects, and how to comply. It also says that changing the declaration itself needs a human to update `guard.yaml`, so an agent fixes the project instead of the policy.

## Workflow

1. Run `guard init` to write a `guard.yaml` that imports the presets matching the project. See [init](cli.md#init).
2. Review the file, adjust thresholds and declarations in the [configuration](configuration.md), and commit it.
3. Run `guard fix --dry-run`, then `guard fix`, to bring the tool configuration in line.
4. Fix the remaining findings in code and documents until `guard check` passes.
5. Run `guard check` in CI. When a finding reflects a deliberate decision, a human changes `guard.yaml` in the same change.

## What it does not prove

Guard checks shape: sizes, names, headings and values. A short method can still be wrong, and a README with the declared sections can still be out of date. Passing `guard check` means the project has not drifted from what was decided, not that the decisions are good.
