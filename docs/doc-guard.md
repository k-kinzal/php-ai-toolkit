# DocGuard

## Purpose

DocGuard is a first-party CLI for fixing the section structure of Markdown documents such as `README.md`, `AGENTS.md`, and `docs/*.md`. A human declares the headings of each document in `doc-guard.yaml`: their levels, their text, and their order. DocGuard fails when a document deviates from that declaration, and it fails when a new Markdown document appears where documents are scanned.

AI agents tend to grow documentation by adding sections — a Development section in the product README, a tooling page under `docs/`, a Limitations section nobody asked for. Review catches this late and repeatedly. DocGuard turns the rule "edit the content of existing sections; change the structure only when a human updates the declaration" into a check that runs with the other lint gates.

The content inside sections is not checked. Rewriting a paragraph, fixing an example, or adding a list item passes.

## Command

Check the declared structure:

```bash
vendor/bin/doc-guard --config=doc-guard.yaml
```

Exit codes:

- `0`: no violations
- `1`: structure violations found
- `2`: configuration or runtime error

Generate a configuration that declares the current structure:

```bash
vendor/bin/doc-guard --generate > doc-guard.yaml
vendor/bin/doc-guard --generate README.md AGENTS.md docs > doc-guard.yaml
```

`--generate` prints a complete `doc-guard.yaml` to standard output and never writes a file. Without paths it declares the Markdown files directly in the working directory and every Markdown file below `docs/`, and scans the same two patterns. A file path declares that file. A directory path declares every Markdown file below it and adds `<directory>/**/*.md` to `scan`. Paths are relative to the working directory. `--generate` cannot be combined with `--config` or `--reporter`.

Generation is how a human adopts the existing structure as the reference, and how a human accepts an intended structure change: edit the documents, regenerate, and review the diff of `doc-guard.yaml`. An agent must not regenerate the configuration to make a violation disappear.

## Configuration

Example `doc-guard.yaml`:

```yaml
# NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you.
documents:
  'README.md':
    headings:
      - '# sql-faker'
      - '## Requirements'
      - '## Installation'
      - '## Usage'
      - '### MySQL'
      - '### PostgreSQL'
      - '## License'
  'docs/algorithm.md':
    headings:
      - '# Algorithm'
      - '## Grammar Sources'
      - '## Limitations'
  'docs/spec.md':
    headings:
      - '# Specification'
      - '## SELECT'
      - '## INSERT'
    max_level: 2

scan:
  - '*.md'
  - 'docs/**/*.md'

report:
  reporter: ai
  order_by:
    - path
    - line
    - rule
```

The file is maintained by a human, like `tree.yaml` and `loc.yaml`, and starts with the NOTE header that tells agents not to overwrite it.

`documents` (required, non-empty) maps each document path to its declared structure. Paths are relative to the directory of `doc-guard.yaml`; `./` prefixes and repeated slashes are normalized, and absolute paths are accepted. Each entry has:

- `headings` (required): the headings in document order, each written in ATX notation — one to six `#` characters, a space, and the text. Quote every entry: YAML reads an unquoted `#` as the start of a comment. Use `headings: []` for a document without headings, such as a `CLAUDE.md` that only includes `AGENTS.md`.
- `max_level` (optional, 1–6, default 6): headings deeper than this level are content, not structure. Declared headings may not be deeper than `max_level`.

`scan` (default `[]`) lists path patterns. Every file matching a pattern must be declared in `documents`. See [Undeclared Documents](#undeclared-documents).

Every top-level and nested key is validated. Unknown keys are configuration errors, so a typo such as `max_levels` cannot silently disable a check.

## Structure Semantics

Two headings are equal when their level and their text are equal. The text is compared after trimming and collapsing runs of spaces and tabs; inline Markdown such as code spans, emphasis, and links is part of the text. The comparison is case-sensitive.

The markup style of a heading is not structure. `Title` underlined with `===` equals `# Title`, a closing sequence such as `## Usage ##` equals `## Usage`, and extra spaces inside the text are ignored. Changing the style passes; changing the words, the level, or the position does not.

DocGuard aligns the declared and the actual headings along their longest common subsequence, and classifies what remains:

- A declared heading that appears at a different position is moved.
- Within one gap between aligned headings, a declared and an actual heading with the same text are a level change.
- When the remaining declared and actual headings of a gap pair up one to one at the same levels, each pair is a rename.
- Everything else is an added heading or a missing heading. When one declared section is replaced by several new ones, DocGuard reports the additions and the removal instead of guessing which new heading replaced the old one.

## Heading Recognition

DocGuard parses the block structure that decides whether a line is a heading, following CommonMark:

- ATX headings: up to three spaces of indentation, one to six `#`, then a space, a tab, or the end of the line. `#hashtag`, `\# text`, and seven `#` are not headings.
- Setext headings: one or more paragraph lines followed by an underline of `=` (level 1) or `-` (level 2). The paragraph lines are joined with a space. Without a preceding paragraph, `---` is a thematic break.
- Fenced code blocks opened with three or more backticks or tildes are skipped until a closing fence of the same character that is at least as long. An unclosed fence runs to the end of the document. A backtick fence whose info string contains a backtick is inline code, not a fence.
- Lines indented by four or more columns are never headings. Inside a list item, such a line may open a fenced code block, which is skipped as well.
- HTML blocks are skipped: comments, `<script>`, `<pre>`, `<style>`, and `<textarea>` blocks until their terminator, and block-level tags such as `<details>` or `<div>` until the next blank line.
- A paragraph of a list item or a block quote never becomes a setext heading, and a `#` line inside a block quote is not a document heading.
- A YAML front matter block between `---` lines at the start of the file is skipped.

Inline content is not parsed. The parser is deliberately small and owned by DocGuard; see [Design Decisions](#design-decisions).

## Undeclared Documents

A new Markdown file is the same failure as a new section: the structure grew without a human decision. If DocGuard ignored new files, an agent that is told not to add a section to the README would add `docs/development.md` instead.

DocGuard therefore reports `undeclared_document` for every file that matches a `scan` pattern and is not declared. Patterns are segment-aware and relative to the directory of `doc-guard.yaml`:

- `*` and `?` match within one path segment and never cross `/`.
- A `**` segment matches zero or more directories.
- Wildcards never match names that start with a dot, and `**` does not follow symbolic links. A literal segment such as `.github` still matches.

`*.md` catches a new file next to the README, such as `CONTRIBUTING.md` or `DEVELOPMENT.md`, and `docs/**/*.md` catches a new page at any depth under `docs/`. Avoid `**/*.md` at a repository root, which also scans `vendor/` and every package.

Detection belongs to DocGuard rather than to TreeGuard. TreeGuard could forbid unknown file names with an exact `allow` list, but that list would duplicate the document list of `doc-guard.yaml` in a second human-managed file that drifts from the first, and its remediation speaks about renaming and moving files. DocGuard already owns the set of documents, reports the addition with the same instruction as an added section, and regenerates both lists in one step.

## Rule Identifiers

| Rule id | Reported location | Fires when |
|---------|-------------------|-----------|
| `unexpected_heading` | `path:line` of the heading | A heading is not in the declared structure. |
| `missing_heading` | `path` | A declared heading is absent. The message names the heading declared before it. |
| `renamed_heading` | `path:line` of the new heading | A different heading at the same level took the place of a declared heading. |
| `changed_heading_level` | `path:line` of the heading | A declared heading appears with a different level. |
| `moved_heading` | `path:line` of the heading | A declared heading appears out of its declared order. |
| `missing_document` | `path` | A declared document does not exist. |
| `undeclared_document` | `path` | A file matching a `scan` pattern is not declared. |

Every violation carries the declared heading as `expected` and the found heading as `actual` when the rule has them.

## Relaxations

The default is strict: adding, removing, renaming, moving, and re-leveling a section are all structural changes and all fail. Two relaxations exist, each for a stated reason.

| Relaxation | Why |
|------------|-----|
| Markup style and whitespace are ignored | ATX versus setext, closing `#` sequences, and spacing change how a heading is written, not the structure a reader navigates. Failing on them would only teach agents to ask for configuration updates that carry no decision. |
| `max_level` per document | Some reference documents enumerate items as headings, such as one `###` per supported SQL statement in a specification. There a new heading documents a new feature rather than growing the structure, and requiring a configuration update for every item turns the gate into noise. Limit this to the document that needs it; product READMEs and `AGENTS.md` keep the default. |

Removing a section is not relaxed. Deleting a Limitations or Security section hides information as effectively as adding a stray section adds noise, and the declaration must stay in sync with the document either way.

## Monorepos

Paths are relative to the directory of the configuration file, not to the working directory, so each configuration describes the documents next to it:

- Put a `doc-guard.yaml` in each package that declares the package's `README.md` and `docs/`, scanning `*.md` and `docs/**/*.md`, and run it from the package's `lint` script next to `tree-guard`.
- Put a `doc-guard.yaml` at the repository root that declares the root `README.md`, `AGENTS.md`, and `CLAUDE.md`, scanning `*.md` only. Scanning `*.md` at the root does not descend into `packages/`.

Run the root configuration where the root is checked, for example from a root Composer script and a root CI job. Because paths follow the configuration file, the same configuration can also be run from a package with `doc-guard --config=../../doc-guard.yaml`.

## Reporting

Configure the reporter with `report.reporter`:

- `ai`: structured text with remediation guidance for coding agents. The guidance tells the agent to keep new information inside the existing section that covers it, and to ask a human to update the configuration when the structure really has to change.
- `text`: concise human-readable output.
- `json`: machine-readable JSON for CI and tooling.

Override the configured reporter from the CLI:

```bash
vendor/bin/doc-guard --config=doc-guard.yaml --reporter=json
vendor/bin/doc-guard --config=doc-guard.yaml --format=text
```

Configure violation ordering with `report.order_by`. Supported fields are `path`, `line`, and `rule` (default `path`, `line`, `rule`). Violations without a line sort before those with one.

## Design Decisions

DocGuard has its own heading parser instead of reusing the Markdown processing of DocGen. Each toolkit tool is an independent Deptrac layer, and the layer rules are human-managed, so sharing code would mean a new shared layer or a dependency from a guard on the documentation renderer. The guard needs only block structure — about a dozen line classifications — while DocGen renders inline content for a site. Keeping the parser inside DocGuard means a renderer change cannot silently change what the guard accepts, and the parser can stay small enough to review as a whole.
