---
name: docgen
description: >-
  Generate and preview PHP documentation with the DocGen CLI (k-kinzal/docgen-php).
  Use for docgen commands, package selection, public API sites, revision diffs,
  coverage and architecture inputs, caching, or documentation jobs in GitHub Actions.
---

# DocGen

Run the installed CLI from the target project's root. Read its `composer.json`
autoload maps and existing documentation command to preserve the intended scope.
Use the project's `composer docgen` script when present; otherwise invoke the CLI:

```bash
vendor/bin/docgen --help
vendor/bin/docgen
vendor/bin/docgen --serve
```

The default run documents `.` and `packages/*` into `build/docs`. Preview serves
the generated site at `127.0.0.1:8090` until interrupted.

## Command rules

- DocGen has no configuration file; pass settings as CLI options.
- `--packages` selects directories with a `composer.json`. Production PSR-4
  sources get API pages; `autoload-dev` sources get linkable source pages only.
- Quote globs. Explicit `--packages` replaces the defaults; repeated glob options
  append values. `--vendor` and `--vendor-dev` select Composer package names,
  such as `'acme/*'`, and are opt-in.
- Keep the cache (`build/docgen-cache` by default) outside the published output.
- `--public-api` selects declarations tagged `@visibility public`; it still
  includes whole source files and Markdown documents, so it does not redact them.

After generation, check the reported package count, `index.html`, and a relevant
symbol or source page. Exit `2` means an invalid command, input, or runtime error;
read the `DocGen error:` message before retrying.

## References

Read only the material needed for the current task:

- [CLI options](references/cli.md): option values, defaults, exit codes, and troubleshooting.
- [Use cases](references/use-cases.md): monorepos, dependencies, public API, coverage,
  architecture, revision comparisons, caching, and reusable Composer commands.
- [GitHub Actions](references/github-actions.md): generation, cache reuse, Pages
  publishing, and pull-request previews using [docs.yml](assets/docs.yml) and
  [docs-preview.yml](assets/docs-preview.yml).
