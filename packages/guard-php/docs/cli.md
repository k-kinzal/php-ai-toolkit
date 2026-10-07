# Command Line

```console
vendor/bin/guard <command> [options]
```

`guard` without a command runs `check`. Run `guard --help` for the command list and `guard <command> --help` for the options of one command. A command name may be shortened while only one command starts with it, such as `guard ch`.

## Commands

| Command | What it does |
|---------|--------------|
| `check` | Reports every violation without changing a file. |
| `apply` | Writes the repairs of [configuration fields](fields.md#repairs), then reports what remains. |
| `init` | Writes a first `guard.yaml` with the presets that fit the project. See [Init](#init). |

Adopting Guard in a project usually takes these steps:

```console
guard init
guard apply --dry-run
guard apply
guard check
```

## Options

| Command | Option | Description |
|---------|--------|-------------|
| all | `-c`, `--config=FILE` | Policy file, relative to the working directory. Default `guard.yaml`. Paths inside it are relative to its own directory, not to the working directory. |
| `check`, `apply` | `--format=FORMAT` | `text` (default) or `json`. See [Reports](#reports). |
| `apply` | `--dry-run` | List the files that would change without writing them. |
| `init` | `--import=NAMES` | Import only these presets, comma-separated or repeated, instead of detecting them. |

The Symfony Console options `-q`, `--silent`, `-v`, `--ansi`, `--no-ansi` and `-n` behave as in other Symfony Console tools, and `guard completion` prints a shell completion script.

## Exit codes

| Code | Meaning |
|------|---------|
| `0` | No required violation; recommendations may remain. For `init`, the policy file was created. |
| `1` | A required rule is violated. For `apply`, after the repairs it could make. |
| `2` | Invalid command line, policy file, input document or extension; for `init`, an existing policy file or an unknown preset. |

Exit code `2` prints one line to standard error that starts with `Guard error:` and says how to fix the problem. It is printed even with `--quiet`.

## Reports

The text report has one line per finding, then one line per file change, then a summary. This one comes from `guard apply --dry-run`:

```text
error: src/Big.php [metrics.method_lines] method Big::m has 52 physical lines; maximum is 50.
would change: /project/phpstan.neon
Guard found required violations.
```

A finding line holds the level, the path, the rule ID in brackets and the message. `apply` does not report the findings its planned repairs resolve; `check` reports them, such as `error: phpstan.neon [phpstan.level] /parameters/level must satisfy {"equals":"max"}. Run guard apply to set the configured repair.` A change line starts with `changed`, `would change` for `--dry-run`, or `blocked` when nothing was written. The summary is `Guard passed.` or `Guard found required violations.`

`--format=json` writes the same information as one object:

```json
{
    "findings": [
        {
            "path": "src/Big.php",
            "rule": "metrics.method_lines",
            "level": "error",
            "message": "method Big::m has 52 physical lines; maximum is 50."
        }
    ],
    "changes": ["/project/phpstan.neon"],
    "action": "would change",
    "success": false
}
```

`changes` lists absolute paths, and `action` applies to all of them. `success` is `false` exactly when the exit code is `1`.

## Repairs

`apply` plans the repairs of every configuration file before writing any of them. While a required configuration rule cannot be satisfied, because it has no repair or its format is check-only, nothing is written and every planned file is reported as `blocked`. Metric, structure and document findings do not block repairs; they remain required failures after `apply`.

`check` never writes. A required configuration violation that `apply` could repair still fails `check`.

## Init

`guard init` writes `guard.yaml` and refuses to overwrite an existing file. Without `--import` it selects presets from what the project has:

| Preset | Selected when |
|--------|---------------|
| `metrics`, `structure` | Composer `autoload.psr-4` names an existing directory, or `src/` exists. |
| `disable-doc` | The project has no `docs/` directory. |
| `phpstan` | `phpstan.neon` or `phpstan.neon.dist` exists. |
| `phpstan-guard-rules` | As `phpstan`, and `k-kinzal/phpstan-guard-rules` is required or locked. |
| `phpunit9` … `phpunit13` | One per PHPUnit configuration file. `phpunit10.xml.dist` selects `phpunit10`; `phpunit.xml.dist` is PHPUnit 13 next to versioned files, and otherwise the major in `composer.lock` or a single-major constraint. |
| `doctest9` … `doctest13` | The matching PHPUnit file already has a doctest suite, or `phpstan.neon` requires PHPDoc examples. |
| `php-cs-fixer` | `.php-cs-fixer.dist.php` or `.php-cs-fixer.php` exists. |
| `phpcs` | `phpcs.xml.dist` or `phpcs.xml` exists. |
| `deptrac` | `deptrac/deptrac` is installed and `deptrac.yaml` or `deptrac.yml` exists. |
| `infection` | `infection.json5` exists. |
| `composer` | `composer.json` exists. |
| `github-actions`, `mutation` | `.github/workflows/ci.yml` or `.github/workflows/mutation.yml` exists. |
| `pbt`, `fuzz`, `phpbench`, `docgen` | `giorgiosironi/eris`, `nikic/php-fuzzer`, `phpbench/phpbench` or `k-kinzal/docgen-php` is installed. |
| `agents-md` | `AGENTS.md` or `CLAUDE.md` exists. |
| `claude-md` | `CLAUDE.md` exists. |
| `readme-md` | `README.md` exists. |

`--import` replaces detection: only the named presets are imported. Besides the imports, `init` writes:

- `metrics.source` with the detected source roots, and `structure.directories` for source roots other than `src`.
- The current README headings under `documentation.files`, with `scan: [README.md]`.
- An `id` and `file` override for each tool rule whose configuration file is not the preset's default, such as `phpstan.neon.dist`.
- The settings of legacy `loc.yaml`, `tree.yaml` and `doc-guard.yaml` files. A legacy `loc.yaml` or `tree.yaml` replaces the `metrics` or `structure` import, so its thresholds stay as they were.

The written file records the project as it is today. Review it before committing: a recorded heading list or a detected preset is a starting point, not a decision.
