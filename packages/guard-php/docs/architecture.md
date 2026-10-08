# Architecture

Each directory immediately under `src/` owns one responsibility and corresponds to one Deptrac layer. A layer uses one `src/<directory>/.*` collector. Class names, OR expressions, and exclusions do not determine ownership. The ruleset is acyclic and lists direct dependencies explicitly.

| Directory | Responsibility |
|-----------|----------------|
| `Diagnostic` | Findings and failures, independent of evaluation and presentation. |
| `Structure` | Shared parsed representations, the `Structurer` contract, and document readers and editors. |
| `Input` | File selections, collection scope, metadata, and collected inputs shared with policies. |
| `Collect` | Filesystem access, discovery, snapshots, and collecting requested structures. |
| `Repair` | Conflicting change detection and atomic writes. |
| `Policy` | The evaluation contract, context, plans, proposed file changes, policy definitions, and built-in evaluators. |
| `Config` | Reading and validating configuration, constructing configured components, and generating or migrating project configuration. |
| `Execution` | Composing registrations and coordinating collection and evaluation. |
| `Reporting` | Rendering findings, rule descriptions, changes, and applying baselines or output filters. |
| `Cli` | Command arguments, invoking the pipeline, and coordinating output and writes. |

`Structure` depends on `Diagnostic`. `Input` describes selected and structured files, and `Collect` produces those inputs. `Policy` depends on `Input`, `Structure`, and `Diagnostic`; it cannot depend on filesystem collection or writing implementations. `Repair` consumes the file changes proposed by `Policy`. `Config` builds policy definitions and registrations; `Execution` consumes them. `Reporting` formats results and `Cli` coordinates the invocation. The exact permitted edges are in `deptrac.yaml`.

Policies receive a context with the root, selected configuration path, repair mode, and collection scope. The context does not contain `Configuration` or other policy registrations. A policy evaluates collected inputs and returns a plan; it does not read files, register components, or perform writes.

Policy-specific definitions and diagnostic factories belong to `Policy`, where their meaning is known. Parsing those definitions from YAML belongs to `Config`. Initial configuration generation and legacy configuration migration are under `Config/Project`.

External code implements the same `Policy` and `Structurer` interfaces as built-in code. The `extensions` configuration section constructs those classes and the pipeline registers them through the ordinary registry API. There is no extension layer, wrapper interface, or special built-in extension. See [extensions](extensions.md) for configuration and PHP examples.

Run `composer deptrac` to check dependency directions and `vendor/bin/deptrac debug:unassigned --config-file=deptrac.yaml` to check that every source class belongs to a layer.
