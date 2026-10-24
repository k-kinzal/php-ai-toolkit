# PHPBench benchmarks

Import `phpbench.yaml` to check benchmarks in the project-root `bench/` directory:

```yaml
version: 1
imports:
  - vendor/k-kinzal/guard-php/rules/phpbench.yaml
```

The preset enables `benchmarks: true`. You can also declare that setting directly. `guard init` selects the preset when `phpbench/phpbench` is installed, or you can select it with `guard init --import=phpbench`. The directory must exist and contain at least one PHP benchmark. An explicit `benchmarks: false` overrides an imported setting.

## Directory and source rules

Only `.php` files may appear directly inside `bench/`. Subdirectories, data files and hidden files such as `.gitkeep` are violations. The number of direct files determines their names:

| Files in `bench/` | Required names | Composer commands |
| --- | --- | --- |
| One | `Bench.php` | `bench` |
| Multiple | `XxxBench.php`, with a non-empty PascalCase prefix | `bench` and one `bench:xxx` per file |

For individual commands, Guard converts the prefix to kebab-case: `ParserBench.php` becomes `bench:parser`, `HttpClientBench.php` becomes `bench:http-client`, and `XMLBench.php` becomes `bench:xml`. Names that produce the same command are reported as collisions; Guard does not choose one of the files.

Every benchmark PHP file must reference the `PhpBench` namespace in PHP syntax. Imports, including aliased and grouped imports, and qualified references count. Comments, string literals, and a declaration such as `namespace PhpBench;` do not count. Guard tokenizes the file without executing it. This detects custom runners that have no PHPBench reference; it does not prove that an imported symbol is used or that a benchmark measures meaningful work.

For example, `bench/Bench.php` can contain:

```php
<?php

declare(strict_types=1);

namespace Bench;

use PhpBench\Attributes as Benchmark;

final class Bench
{
    #[Benchmark\Revs(1000)]
    public function benchHash(): void
    {
        hash('sha256', 'example');
    }
}
```

## Composer repairs

`guard fix` sets the following fields in the existing `composer.json`:

```json
{
    "autoload-dev": {
        "psr-4": {
            "Bench\\": "bench/"
        }
    },
    "scripts": {
        "bench": "phpbench run bench --bootstrap=vendor/autoload.php"
    }
}
```

For multiple files it also sets each individual command to the exact file path, for example:

```json
{
    "scripts": {
        "bench:parser": "phpbench run bench/ParserBench.php --bootstrap=vendor/autoload.php",
        "bench:http-client": "phpbench run bench/HttpClientBench.php --bootstrap=vendor/autoload.php"
    }
}
```

These are exact command constraints. Existing values, including custom options, are replaced with the canonical commands. Unrelated Composer fields and scripts are preserved. The directory is inspected on every run, so adding a benchmark introduces a required command. Commands left over after a file is removed are not deleted automatically.

PHPBench accepts a directory or file path and an explicit bootstrap through its [benchmark runner](https://phpbench.readthedocs.io/en/latest/guides/benchmark-runner.html). The preset therefore no longer requires `phpbench.json.dist` or its `runner.path` field; existing PHPBench configuration files are left intact. Install `phpbench/phpbench` as a development dependency and run `composer dump-autoload` after repairing the mapping.

Use `guard check` to report violations, `guard fix --dry-run` to preview Composer changes, and `guard fix` to apply them. Filename and source violations include manual remediation and remain errors after configuration repair. Guard does not rename classes, delete files or fabricate benchmarks. A missing `composer.json` must be created manually. The policy can be combined with `test_dependencies` and ordinary configuration constraints; incompatible field constraints block writes.

## Diagnostics

| Rule | Meaning | Automatic repair |
| --- | --- | --- |
| `phpbench.autoload` | Incorrect or missing `Bench\` development mapping | yes |
| `phpbench.script` | Incorrect or missing main command | yes |
| `phpbench.script.bench:xxx` | Incorrect or missing command for a named file | yes |
| `phpbench.empty` | No benchmark files | no |
| `phpbench.directory` | Subdirectory inside `bench/` | no |
| `phpbench.extension` | Non-PHP file | no |
| `phpbench.filename` | Filename does not match the file count | no |
| `phpbench.namespace` | No PHPBench reference in PHP syntax | no |
| `phpbench.command_collision` | Multiple files map to the same individual command | no |

`guard rules --query=phpbench` lists these constraints without requiring the benchmark files to exist. As with other policies, an explicit `collect` scope limits what Guard can inspect. Include `composer.json` and the whole `bench/` directory to check and repair the complete convention.
