# Guard in GitHub Actions

Add Guard to the existing lint job after installing Composer dependencies,
including the dev dependency `k-kinzal/guard-php`. Use a PHP version and extensions
compatible with the project's locked dependencies. A job that already runs
`composer lint` with `@guard` does not need another Guard step.

```yaml
- name: Check project policies
  run: vendor/bin/guard check --format=text
```

CI checks the committed `guard.yaml` and baseline. Run `init`, `fix`, and baseline
generation locally when requested, review their changes, and commit the result.
Keep Guard's nonzero exit status so violations and invalid inputs fail the job.

## Standalone workflow

This example assumes a root Composer project with a committed lock file, Guard
installed as a dev dependency, and a reviewed `guard.yaml`. Adapt `main`, PHP
`8.5`, and extensions to the target project. Keep action pins consistent with
the repository's update policy.

```yaml
name: Guard

on:
  pull_request:
  push:
    branches: [main]
  workflow_dispatch:

permissions:
  contents: read

concurrency:
  group: ${{ github.workflow }}-${{ github.event_name }}-${{ github.event.pull_request.number || github.ref }}
  cancel-in-progress: true

defaults:
  run:
    shell: bash

jobs:
  guard:
    name: Check project policies
    runs-on: ubuntu-latest
    timeout-minutes: 10
    steps:
      - name: Checkout
        uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1
      - name: Set up PHP
        uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240
        with:
          php-version: '8.5'
          extensions: dom
          coverage: none
          tools: composer:v2
      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --no-progress
      - name: Check project policies
        run: vendor/bin/guard check --format=text
```

## Package policies and reports

For a package with its own dependencies, set `working-directory` on both the
install and check steps, or on the job's `defaults.run`. For a root installation
checking a package policy:

```yaml
- name: Check package policies
  run: vendor/bin/guard check --config=packages/example/guard.yaml --format=text
```

For a JSON report in an existing job, use a permitted output directory such as
`build/` and Bash `pipefail` to retain Guard's exit status through `tee`:

```yaml
- name: Check policies and save report
  shell: bash
  run: |
    mkdir -p build
    vendor/bin/guard check --format=json | tee build/guard-report.json
```

An existing artifact upload step can collect the report with `if: always()`.
Exit `2` writes the diagnostic to stderr, so the report may be empty. Check the
step status before consuming JSON. Display filters still fail on hidden errors;
use `--no-baseline` only for a deliberate audit of all existing violations.

Sources: [workflow syntax](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax),
[checkout](https://github.com/actions/checkout), and
[setup-php](https://github.com/shivammathur/setup-php).
