# guard-php

Source, structure, documentation and configuration policy checks and repairs.

Part of [php-ai-toolkit](https://github.com/k-kinzal/php-ai-toolkit).

```sh
composer require --dev k-kinzal/guard-php:dev-main
```

See the documentation:

- [Policies](docs/policies.md): What Guard checks, what it repairs, and the adoption workflow
- [Command Line](docs/cli.md): Commands, options, exit codes, reports and `guard init` preset detection
- [Configuration](docs/configuration.md): `guard.yaml`, imports and overrides, shipped presets and the collection scope
- [Metrics](docs/metrics.md): PHP line and complexity limits, profiles and assignments
- [Structure](docs/structure.md): Directory contents, naming and count constraints
- [Documents](docs/documents.md): Markdown headings, outlines, badges, exact content and undeclared documents
- [Configuration Fields](docs/fields.md): Assertions and repairs for values in tool configuration files
- [Extensions](docs/extensions.md): Custom policies and structures
- [Architecture](docs/architecture.md): Responsibilities and dependency directions

```sh
vendor/bin/guard init
vendor/bin/guard rules
vendor/bin/guard check
vendor/bin/guard check --fixable
vendor/bin/guard fix --dry-run
vendor/bin/guard fix
```

When the project has no `docs/` directory, `guard init` automatically imports
`disable-doc`. This structure preset reports a required `structure.denied_dir`
violation if a project-root `docs/` directory is created, even when it is empty.
Existing `docs/` directories prevent automatic selection; `README.md` and nested
documentation directories are unaffected.

To select the preset explicitly, run `vendor/bin/guard init --import=disable-doc`.
An explicit `--import` list selects only the named presets. To enable it in an
existing policy, add `vendor/k-kinzal/guard-php/rules/disable-doc.yaml` to `imports`.
