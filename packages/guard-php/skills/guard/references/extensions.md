# Extensions

The `extensions` section loads policies and structures that a project or a package adds to the same run as the built-in ones. Their rule IDs, levels and repairs are chosen by the extension. The interfaces are in [the extensions reference](../../../docs/extensions.md).

## Resolving findings

Treat an extension finding like a built-in one: the extension records a decision of the project, and the message names the file, what is expected and how to comply.

- Follow the message, then find the extension class under `extensions` in `guard.yaml` and read its policy when the message leaves the expectation unclear.
- When the extension proposes repairs, `guard fix --dry-run` lists them like configuration repairs; when it reports blocking findings, fix those first, because no file is written while one remains.
- Do not edit the extension's code or options to accept the change. Changing either is a policy change for a human, the same as editing `guard.yaml`.
- For exit code `2` naming an extension, the class is missing, does not implement the interface, or rejected its options. Run `composer dump-autoload` and check the class name and options; do not remove the declaration.

## When to write an extension

Write one only when the built-in sections cannot express the decision:

| Decision | Use |
|----------|-----|
| A single value in a configuration file | A `configuration` rule. |
| Names, counts or presence of files and directories | A `structure` rule. |
| Sections of a Markdown file | A `documentation` declaration. |
| A property of file contents, such as `declare(strict_types=1);` in every PHP file, or an XML file valid against a schema | An extension policy. |
| A file format no built-in structure parses | An extension structure, shared by the policies that read it. |

## Writing a policy

- Declare every file in `inputs()` and read only what `evaluate()` receives. A policy must not touch the file system; Guard reads each file once, intersects every selection with the collection scope, and caches structures for all policies.
- Request the structure you need, such as `text`, `xml` or `php.tokens`, instead of parsing bytes yourself. Request no structure when metadata is enough, so the file is not read.
- Select by `files` when the policy must report a missing file; a `files` selection lists a missing path so the policy can report it, while `patterns` and `descendants` return only what exists.
- Prefix finding rule IDs with the project or package name, such as `app.strict-types`. Configured components use their class names as registration IDs; request custom structures with `CustomStructurer::class`.
- Write messages the way the built-in ones are written: the file and the offending symbol, what is expected, how to fix it, and that changing the rule needs a human to update `guard.yaml`.
- Choose the level per finding: `required` for decisions that hold the quality bar, `recommended` for preferences.
- Propose `FileChange` values only when `$context->repair` is `true`, and only for a change with exactly one correct result. Return the required findings that the policy could not repair as blocking, so `fix` does not write a half-repaired project.
- Treat structures as read-only. Call `copy()` on a parsed document before changing it.

## Registering an extension

- Implement `Guard\Policy\Policy` or `Guard\Structure\Structurer` directly. Options are named constructor arguments; validate their meaning in the constructor and throw `Guard\Diagnostic\PolicyException` for invalid values. Unknown names and invalid argument types fail with exit code `2`.
- Give the options the narrowest defaults that still check the project, such as `src/**/*.php`.
- Make the class autoloadable, for example through `autoload-dev`, and name it in `guard.yaml`. Guard loads only the classes named there, in order, after the built-in policies.
- Test the policy through `Guard\Execution\Pipeline::run($configuration, $configPath, $repair)` or `Guard\Cli\Application` against fixture projects: one that passes, one per finding, and one that `fix` repairs. Use `Guard\Execution\Registry::defaults($configuration->policies)` when a supplied registry must include the built-in policies.

```yaml
extensions:
  App\Guard\StrictTypesPolicy:
    files: ['src/**/*.php']
```
