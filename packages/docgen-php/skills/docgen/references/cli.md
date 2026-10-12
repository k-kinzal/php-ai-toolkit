# CLI options

Run `vendor/bin/docgen --help` for the installed version. Values accept either
`--option=value` or `--option value`. Glob options accept comma-separated lists
and repeated occurrences; quote them to prevent shell expansion.

## Scope and output

| Option | Default | Meaning |
| --- | --- | --- |
| `--packages=GLOBS` | `.,packages/*` | Directory globs, relative to the working directory; each matching `composer.json` defines a package. Explicit values replace the defaults. |
| `--exclude=GLOBS` | none | Paths relative to the project root to omit from source and Markdown discovery. |
| `--vendor[=GLOBS]` | none | Installed runtime dependencies selected by Composer package name; bare `--vendor` selects all. |
| `--vendor-dev[=GLOBS]` | none | Installed dev dependencies selected by Composer package name; bare `--vendor-dev` selects all. |
| `--public-api` | off | Select top-level declarations tagged `@visibility public`. |
| `--output=DIR`, `-o DIR` | `build/docs` | Generated site directory. |
| `--title=TEXT` | root package name, else project directory name | Site title. |
| `--deptrac=FILE` | `deptrac.yaml` when present | Layer definitions and ruleset for architecture graphs and badges. |
| `--coverage=DIR` | none | PHPUnit `--coverage-xml` directory for method-to-test references. |
| `--base-url=URL` | none | Published absolute HTTP(S) URL for canonical links and social metadata. |
| `--repository=URL` | manifest `support.source`, then `homepage` | Absolute HTTP(S) URL overriding repository links for project packages. |

Package PSR-4 maps determine the source scope. Dependencies remain excluded unless
selected, even when their types occur in public signatures. Vendor globs match
names such as `'acme/*'`, not directory paths such as `vendor/acme`.

## Comparison and execution

| Option | Default | Meaning |
| --- | --- | --- |
| `--diff=BASE` | off | Compare BASE with the working tree, including uncommitted changes. |
| `--diff=BASE..HEAD` | off | Compare two Git revisions. |
| `--base=REVISION` | none | Set or override the base revision. |
| `--head=REVISION` | working tree | Set or override the head; requires a base from `--base` or `--diff`. |
| `--serve[=HOST:PORT]` | off; bare flag uses `127.0.0.1:8090` | Generate, then serve until interrupted. A bare port also works. |
| `--cache-dir=DIR` | `build/docgen-cache` | Parse and page cache directory; keep outside output. |
| `--no-cache` | off | Parse and render everything without reading or writing the cache. |
| `--clear-cache` | off | Remove the cache before generating. |
| `--jobs=N`, `-j N` | CPU cores minus one, at least 1 and at most 16 | Worker count; `1` runs sequentially. |
| `--memory-limit=VALUE` | environment limit, raised to at least `512M` | Override with a byte count, `512M`, `1G`, or `-1` for unlimited. |

`--quiet` keeps errors and warnings, `--no-ansi` disables color, and `--version`
prints the version. Progress goes to stdout; warnings and errors go to stderr.

## Exit codes and troubleshooting

| Code | Meaning | Next step |
| --- | --- | --- |
| `0` | Generation succeeded. | Inspect output and any warnings. |
| `1` | Bootstrap failed: unsupported PHP or missing Composer autoloader. | Use PHP 8.0 or later and the project's installed dependencies. |
| `2` | Invalid command, input, or runtime failure. | Follow the error message; use `--help` for option syntax. |

- Missing packages: check the working directory, `--packages`, and PSR-4 maps.
  An unmatched vendor glob or a PHAR-only dependency emits a warning.
- Missing methods' test references: pass a PHPUnit XML coverage directory, not
  Clover XML or an HTML report.
- Sequential execution: workers require `pcntl` and CLI OPcache/JIT to be off.
  Sequential fallback produces the same site.
- Stale pages after editing DocGen itself: use `--clear-cache` or `--no-cache`;
  local generator edits do not change its installed-version cache fingerprint.
- Missing social image: generation needs `--base-url` and GD with FreeType.
  Without GD, generation continues without `assets/og-image.png`.
