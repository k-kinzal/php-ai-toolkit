---
name: setup-php-ai-toolkit
description: >-
  Adopt or audit php-ai-toolkit end to end in an existing PHP project. Use when
  asked to apply, install, set up, or fully integrate php-ai-toolkit rather than
  when the request names only one toolkit component.
---

# Adopt php-ai-toolkit

Adoption changes the project until it satisfies the toolkit's design and quality
baseline. It does not tune every gate to describe the code as it already is.

## Ownership Boundaries

Unless the user explicitly puts one of these in scope, do not modify:

- `AGENTS.md`, `CLAUDE.md`, agent settings, or other human-owned instructions;
- product documentation under `docs/` or the product README;
- released public class names, namespaces, method signatures, or behavior; or
- unrelated tools and configuration.

The toolkit does not use Rector. Do not install it, create `rector.php`, or add a
Rector lint step. Remove Rector artifacts introduced by the adoption diff. Treat a
pre-existing Rector setup as unrelated unless the user separately asks to remove
or retain it.

Developer-tool explanations belong in the toolkit's vendor documentation, not in
the target product's `docs/`. Generated DocGen output belongs under `build/` or on
the publishing branch, never as hand-written tooling pages in product docs.

Introducing a gate is not permission to break compatibility. Preserve public
facades and move or redesign internal implementation behind them.

## Preflight

Before editing, inspect:

- Composer production and development autoload roots, package type, binaries, PHP
  constraint, and scripts;
- the existing source/test tree and the project's actual runtime entry points;
- supported-version claims and the CI matrix;
- existing quality configs and workflows;
- public API evidence from the README, product docs, exported namespaces, and
  release history; and
- the current diff, so unrelated or concurrent work is preserved.

Verify packaging and execution constraints instead of assuming them. Composer
autoloading is the default for class-backed PHP. Keep a nonstandard include-based
runtime only when an executable or distribution test proves that Composer's
autoloader is unavailable in the shipped environment.

Classify the project before installing a development dependency. An application
with one deployment runtime may resolve a constraint for that runtime. A library,
plugin, CLI, or other project with a supported PHP range must derive a Composer
constraint that resolves on every supported minor; a bare `composer require`
performed on the agent's current PHP version is not that derivation. Verify the
result against every supported dependency graph or maintained version lock.

Write the intended internal responsibility and dependency model before adding
TreeGuard or Deptrac configuration. Existing folders and dependency cycles are
findings, not architecture decisions.

## Apply the Toolkit

Read and use the component skills relevant to each step:

1. `/setup-toolkit-phpstan`
2. `/setup-toolkit-phpunit` and `/setup-toolkit-doctest`
3. `/setup-toolkit-php-cs-fixer` and `/setup-toolkit-php-compatibility`
4. `/setup-toolkit-loc-guard` and `/setup-toolkit-tree-guard`
5. `/setup-toolkit-deptrac` and `/setup-toolkit-scope-guard`
6. `/setup-toolkit-infection`
7. `/setup-toolkit-doc-gen`
8. `/setup-toolkit-github-actions`

Do not invoke `/setup-toolkit-agents-md` unless the user explicitly requests an
AGENTS.md change.

The normal adoption installs every applicable gate. A gate may be omitted only
when it cannot measure the shipped production code or cannot run on the supported
toolchain, and that conclusion must be evidenced and reported to the user. A
zero-target green run is an omission, not a successful setup.

## Non-Negotiable Defaults

- PHPStan: the target config includes strict-rules' `rules.neon` plus the
  toolkit's `extension.neon` and `rules.neon`, and sets `level: max`. The
  toolkit rules and formatter remain separate public responsibilities. Add only
  evidenced project-specific parameters, never copied or weaker policy.
- TreeGuard: every maintained production and unit-test directory has at most 15
  direct files and 20 direct subdirectories. Apply the limits to all real code
  roots, not to the repository root's heterogeneous manifests and tool configs.
  Do not raise the limits or exclude maintained code to fit the current tree.
- LocGuard: use the shipped limits. Split responsibilities rather than increasing
  a metric to the first measured value.
- Infection: whole-tree MSI and covered MSI are 80; changed-lines MSI and covered
  MSI are 85. A measured score verifies that the fixed policy is feasible; it does
  not silently create a stricter policy. Raising either value is an explicit human
  quality-policy decision.
- Deptrac: model intended responsibility boundaries, prefer directory collectors,
  assign every production token, and fix cycles. Do not enumerate today's flat
  class list in regex collectors merely to obtain green output.
- ScopeGuard: declare real ownership boundaries. A configuration that scans files
  but has no visibility contract is not a completed adoption.
- DocGen: configure generation, then ask whether publishing is local only, default
  branch, or default branch plus pull-request diff previews. Never silently omit
  that decision.
- CI: every configured gate runs as an observable named step or dedicated job on
  the default branch. Documentation publishing remains in separate workflows.

Individual project settings may make a gate stricter. A weaker value or exclusion
requires a concrete limitation of the tool or shipped environment and an explicit
human decision; “the first run was red” is not a limitation.

## Configuration Value Ledger

Every value copied into a target project must be assigned to exactly one of these
classes before it is written:

| Class | How it is chosen | Examples |
|-------|------------------|----------|
| Fixed toolkit policy | Copy the shipped value. A target may not weaken it. | TreeGuard 15 files/20 directories, shipped LocGuard limits, Infection 80/80 and 85/85, PHPUnit strictness flags, PHP-CS-Fixer rule policy |
| Project-derived fact | Inspect the project and calculate it; never copy the template's example value. | PHP matrix and highest supported PHP, dependency constraint, code/test roots, namespaces, CI extensions, lock-file mode, architecture layers, justified generated-code exclusions |
| Operational starting point | Start with the template, measure the real run, and adjust for reliability without changing what is measured. | Process and job timeouts, memory limits, worker/thread counts, cache paths |
| Explicit human policy | Stop and obtain the decision when it is not already recorded. | Stricter mutation thresholds, public API/ownership boundaries, exceptional exclusions, DocGen publication and preview mode |

An operational change must be supported by runtime, peak-memory, or concurrency
evidence. It must not compensate for a failing quality gate. A project-derived
value must cite its source in the adoption notes or be self-evident from the same
configuration (for example the maximum entry in the CI matrix). Template literals
such as PHP versions, extensions, paths, and memory limits are examples, not facts
about the target project.

## Fixing Adoption Failures

Use this order:

1. Confirm the tool scans every intended production and test root.
2. Confirm the shipped default was copied without weakening or new exclusions.
3. Fix tests, internal structure, dependency direction, and implementation design.
4. Preserve the public facade when moving internals.
5. Stop and ask only when a genuine public compatibility or ownership decision is
   required.

Never respond to a red gate by lowering a threshold, disabling a rule or mutator,
widening an exclude, permitting both directions between layers, adding suppression
comments, or editing product docs and AGENTS.md to rationalize the result.

## Completion Audit

Before reporting completion:

- run unit, doctest, parallel, lint, compatibility, guard, architecture, coverage,
  mutation, and DocGen commands in proportion to the project's supported setup;
- validate workflows and confirm every configured command appears in CI;
- confirm PHPStan actually loads a toolkit rule and uses the AI formatter;
- confirm Deptrac has no unassigned production tokens and ScopeGuard has meaningful
  declarations;
- audit every added configuration value against the ledger above; no copied value
  may remain uncategorized, and every difference from the vendor template needs a
  project fact, measured operational reason, or recorded human decision;
- confirm a locked Composer install has an existing lock file and fails when it is
  missing, the one-off parallel/mutation/documentation runtime is the highest PHP
  version in the matrix, and configured PHP extensions cover Composer platform
  requirements plus the commands each job actually runs;
- compare the target configuration with this package's own configuration and do
  not leave the package itself weaker than the defaults it publishes;
- count the targets selected by every scanner and suite; a successful command with
  zero production tokens, visibility declarations, mutants, tests, examples, or
  generated API pages is not verification;
- inspect the final diff for changed public API, product docs, AGENTS files,
  loosened defaults, broad exclusions, unrelated Rector artifacts, and generated
  build output; and
- state the user's DocGen publishing choice and any gate that remains genuinely
  inapplicable.
