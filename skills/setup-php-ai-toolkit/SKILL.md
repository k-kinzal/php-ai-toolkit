---
name: setup-php-ai-toolkit
description: >-
  Set up php-ai-toolkit end to end in a PHP project by selecting and applying
  the relevant setup-toolkit component skills. Use for a complete toolkit setup;
  use the named component skill directly when the request concerns one tool only.
---

# Set Up php-ai-toolkit

This skill is the entry point for applying php-ai-toolkit to a user's project.
It coordinates the component setup skills; the component skills own the detailed
requirements, templates, and verification steps.

## Inspect the Project

Before selecting components, read:

- `composer.json`, including PHP constraints, autoload roots, dependencies, and
  scripts;
- the source and test directory layout;
- existing tool configuration and GitHub Actions workflows; and
- the project's current diff so unrelated changes are preserved.

Use values derived from the target project. Do not copy PHP versions, paths,
namespaces, dependency constraints, or CI settings from the php-ai-toolkit
repository.

In a monorepo, identify the requested package and its owning workflows before
editing. Keep package commands, autoload changes, generated output, and tool
dependencies within that scope; update shared callers only where the change
requires it.

Setup does not include rewriting the product README, adding architecture or
tooling pages under `docs/`, or creating `CONTRIBUTING.md`. Keep setup rationale in
the delivery explanation and operational settings in their tool files. If the user
requests a documentation edit, preserve content outside that request; removing a
section does not authorize replacing the rest of the README or moving the removed
material into a new guide.

Preserve established tool and workflow layouts. Standardizing a component's
configuration does not by itself call for a new workflow or a nested Composer
project under `tools/`.

## Apply Component Skills

Read and follow the component skills in this order:

1. `/setup-toolkit-phpstan`
2. `/setup-toolkit-phpunit` and `/setup-toolkit-doctest`
3. Assess performance-sensitive public operations and use
   `/setup-toolkit-phpbench` only when a stable representative workload can
   support a useful local benchmark or pull-request comparison
4. Assess the project's contracts and use `/setup-toolkit-pbt` and/or
   `/setup-toolkit-fuzzing` only where structured properties or coverage-guided
   exploration provide a meaningful oracle
5. `/setup-toolkit-php-cs-fixer` and `/setup-toolkit-php-compatibility`
6. `/setup-toolkit-loc-guard`, `/setup-toolkit-tree-guard`, and `/setup-toolkit-doc-guard`
7. `/setup-toolkit-deptrac`
8. `/setup-toolkit-infection`
9. `/setup-toolkit-docgen`
10. `/setup-toolkit-github-actions`

Use `/setup-toolkit-agents-md` only when the user explicitly asks to create or
change `AGENTS.md`, and `/setup-toolkit-readme` only when the user explicitly
asks to create or change `README.md`.

When multiple component skills update the same target file, combine their
requirements into that file. GitHub Actions is applied last so its jobs invoke the
commands and configuration selected by the other component skills.

PHPBench, fuzzing, and PBT are not checkbox gates. Skip PHPBench when no stable
representative workload exists. Skip either fuzzing or PBT when no contract has a
generator and oracle strong enough to justify it, and report those decisions.

The core adoption is incomplete until PHPStan, PHPUnit, PHP-CS-Fixer,
PHPCompatibility, LocGuard, TreeGuard, DocGuard, Deptrac, Infection, and GitHub
Actions are installed, configured, wired into Composer, and exercised. Doctest and DocGen are
also part of a complete adoption when the project has maintained PHPDoc examples or
published API documentation.

Do not silently classify a core component as inapplicable. LocGuard and TreeGuard
apply to every project with maintained production PHP source, and DocGuard applies
to every project with a README.md, AGENTS.md, or docs/. PHPCompatibility
applies whenever the project declares a PHP support range. Deptrac applies even to
a flat library: discover or create a meaningful responsibility boundary, and stop
for the project's architecture decision if one cannot be derived without guessing.
If a core component is genuinely blocked by the target's runtime or dependency
graph, report the exact blocker and leave the overall setup incomplete.

Before applying GitHub Actions, verify this completion table against files and
Composer scripts rather than against work already attempted:

| Component | Required evidence |
|-----------|-------------------|
| PHPStan | `phpstan.neon` or `.dist`, and `composer phpstan` |
| PHPUnit | version-correct configuration, and `composer test:unit` |
| PHP-CS-Fixer | `.php-cs-fixer.dist.php`, and `composer format:check` |
| PHPCompatibility | `phpcs.xml.dist`, and `composer compat` |
| LocGuard | `loc.yaml`, and `composer loc-guard` |
| TreeGuard | `tree.yaml`, and `composer tree-guard` |
| DocGuard | `doc-guard.yaml`, and `composer doc-guard` |
| Deptrac | `deptrac.yaml`, and `composer deptrac` |
| Infection | `infection.json5`, and a scheduled `mutation.yml` workflow separate from `ci.yml` |
| Composer autoload | `composer autoload:check`, first in `lint` |

Every configured fast gate must also appear in the aggregate `lint` script. Re-open
the generated workflow after GitHub Actions is applied and confirm that every row
is invoked. Missing evidence is a setup failure, not an optional follow-up.

Give every Composer script a `scripts-descriptions` entry that says what it runs
and when to use it. `composer list` and `composer run-script --list` show those
descriptions, and an agent reading them can pick `fuzz:smoke` over `fuzz:mysql`
or `test` over `test:unit` without opening `composer.json`.

## Fast Gates and Scheduled Campaigns

The gates in `lint` and `test` answer in seconds to minutes and run on every pull
request. Fuzzing and mutation testing are campaigns: they take minutes to an hour,
they explore rather than check, and their result is a finding to investigate, not
a pass or fail of the change under review. They therefore run on the default
branch only, on a schedule and on demand, and they report through issues:

| Campaign | Runs on | Green means | Red means | A finding becomes |
|----------|---------|-------------|-----------|-------------------|
| Fuzzing | The default branch, per package | The campaign ran | The campaign could not run: a dead service, a broken harness | An issue per crash input, with the artifact and the replay command |
| Mutation testing | The default branch, per package | Infection measured the whole tree | Infection could not measure: failed initial tests, skipped mutants | One issue per package while the score stays below the threshold |

Do not turn either into a pull-request gate, and do not make a finding red. A red
campaign must always mean "fix the campaign", so that it is never ignored as one
more crash. This is a frequent point of drift when an agent applies the toolkit:
the pull-request habit of "a check that can fail must fail the build" does not
apply to these two.
