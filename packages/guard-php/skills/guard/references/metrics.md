# Source metrics

The `metrics` section limits the physical lines, the code lines and the cyclomatic complexity of production PHP. Every finding is required and none is repaired; it stays until the code is split. The semantics are in [the metrics reference](../../../docs/metrics.md).

## Resolving findings

A metric finding says that one element carries more than one responsibility. Find the responsibilities first, then split along them. The result must read better than the original, not only measure smaller.

| Rule ID | Practice |
|---------|----------|
| `metrics.method_lines`, `metrics.function_lines` | Extract the steps of the method into private methods named after what they do, or move a step into the object that owns its data. Keep the original method as the readable sequence of those steps. |
| `metrics.cyclomatic_complexity` | Return early for guard conditions instead of nesting. Replace a chain of `if`/`elseif` or `switch` on a type or kind with polymorphism, or with a `match` or lookup table when the branches only choose a value. Move a compound condition into a named predicate method. |
| `metrics.class_lines`, `metrics.trait_lines`, `metrics.interface_lines`, `metrics.enum_lines` | Split the type by responsibility: extract a collaborator for a cohesive group of methods and the properties they use. Split a large interface into role interfaces. Do not move methods into a trait only to shorten the class. |
| `metrics.file_lines`, `metrics.file_ncloc` | Keep one type per file. When the file holds one type, resolve it as the class finding. |

The finding names the element, such as `method Big::m has 52 physical lines; maximum is 50.` Fix every element it names; splitting one method can push the class over its own limit, so run `guard check` again after each split.

### Changes that hide a finding instead of fixing it

- Joining statements onto one line, removing blank lines, or deleting comments and PHPDoc. Physical lines drop, the responsibility stays, and the code gets harder to read. `file.ncloc` does not count comments anyway.
- Moving the body into a closure or arrow function. The closure is checked as `function {closure}` on its own, and its lines still count toward the enclosing function.
- Replacing `&&` with nested `if`, or `?:` with `if`. Both add the same complexity.
- Moving code into a `*Helper`, `*Util` or `*Manager` class. The shipped structure rules deny those names; name the extracted class after its responsibility.
- Moving a file to an excluded path, generating it, or adding it to `metrics.exclude`.
- Adding an assignment or a profile, or raising a limit in `guard.yaml`. Those are policy changes for a human.

Tests and fixtures are not measured. Do not move production logic into them, and do not create test-only seams in production code to satisfy a limit.

## Writing rules

- Scan production code only. `source` lists the production autoload roots; tests and fixtures are long on purpose.
- Start from the shipped `rules/metrics.yaml` and keep its `standard` profile as the default. Change a limit only by a deliberate decision for the whole project, not from the largest element that exists today.
- Use `exclude` only for code that nobody writes by hand, such as generated code. Code that has a reason to be larger stays in scope with its own profile.
- Give a profile `extends: standard` and relax only the limits that its reason explains. A file that wraps a large native API needs a longer file and class, not longer or more complex methods.
- Name each assignment after the reason, such as `native-adapters`, and match exact files or a narrow glob. Assignments must not overlap, and one that matches no file is an error, so a stale exception surfaces when its files go away.
- Set a metric to `null` in a profile only to stop checking it for that profile; a root profile that omits a metric never checks it.

```yaml
metrics:
  profiles:
    adapter:
      extends: standard
      limits:
        file: {lines: 900, ncloc: 650}
        class: {lines: 800}
  assignments:
    - name: native-adapters
      match:
        paths: ['src/Adapter/Pdo*.php']
      policy: adapter
```
