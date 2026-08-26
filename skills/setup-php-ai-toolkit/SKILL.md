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

## Apply Component Skills

Read and follow each applicable component skill in this order:

1. `/setup-toolkit-phpstan`
2. `/setup-toolkit-phpunit` and `/setup-toolkit-doctest`
3. Assess performance-sensitive public operations and use
   `/setup-toolkit-phpbench` only when a stable representative workload can
   support a useful local benchmark or pull-request comparison
4. Assess the project's contracts and use `/setup-toolkit-pbt` and/or
   `/setup-toolkit-fuzzing` only where structured properties or coverage-guided
   exploration provide a meaningful oracle
5. `/setup-toolkit-php-cs-fixer` and `/setup-toolkit-php-compatibility`
6. `/setup-toolkit-loc-guard` and `/setup-toolkit-tree-guard`
7. `/setup-toolkit-deptrac`
8. `/setup-toolkit-infection`
9. `/setup-toolkit-docgen`
10. `/setup-toolkit-github-actions`

Use `/setup-toolkit-agents-md` only when the user explicitly asks to create or
change `AGENTS.md`.

When multiple component skills update the same target file, combine their
requirements into that file. GitHub Actions is applied last so its jobs invoke the
commands and configuration selected by the other component skills.

PHPBench, fuzzing, and PBT are not checkbox gates. Skip PHPBench when no stable
representative workload exists. Skip either fuzzing or PBT when no contract has a
generator and oracle strong enough to justify it, and report those decisions.

If a component does not apply to the target project, leave it out and report why.
