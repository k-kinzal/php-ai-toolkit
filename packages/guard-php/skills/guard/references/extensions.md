# Extension findings

`extensions` in `guard.yaml` names autoloadable classes and their options.
Extensions supply their own rule IDs, severity, and repair support. `guard rules`
identifies configured extensions with an `extension:` prefix; use `guard check`
for their actual findings.

- Follow the diagnostic, then inspect the configured class and options if the
  expected behavior is unclear.
- Use `guard fix --dry-run` to inspect proposed extension repairs. Extensions can
  repair more than configuration fields and can report findings that block writes.
- For exit `2`, check the class name, Composer autoloading, required interface,
  and options named in `Guard error:`. After changing autoload mappings, run
  `composer dump-autoload` before retrying.
- Check `collect` if inputs appear absent: it also limits what extensions receive.

```yaml
version: 1
extensions:
  App\Guard\StrictTypesExtension:
    files: ['src/**/*.php']
```

This example requires that project extension to exist. For a requested custom
policy or format implementation, consult the [extension API](../../../docs/extensions.md).
