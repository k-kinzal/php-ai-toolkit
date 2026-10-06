# Doc policy

`Guard\Policy\HeadingStructure` declares file selections and checks their shared `markdown.headings` structures. It runs through [Guard](guard.md), alongside source and directory policies. Content within sections remains free to change.

## Configuration

```yaml
version: 1
documentation:
  files:
    README.md:
      headings:
        - '# Project'
        - '## Installation'
        - '## Usage'
    docs/reference.md:
      headings:
        - '# Reference'
        - '## API'
      max_level: 2
  scan: ['*.md', 'docs/**/*.md']
  exclude: ['docs/generated/**']
```

`documentation.files` is a required, non-empty mapping of document paths to `headings` and optional `max_level` (1–6, default 6). Use `headings: []` for a document without headings. Quote heading entries because YAML treats an unquoted `#` as a comment. Paths are relative to the policy file's directory.

`scan` discovers undeclared files. `exclude` removes files from discovery; explicitly declared files are still checked. Unknown configuration keys fail validation. `guard init` records the current README headings and can migrate an existing `doc-guard.yaml`. Existing declarations remain the authority for later checks.

## Structure Semantics

Two headings are equal when their level and their text are equal. The text is compared after trimming and collapsing runs of spaces and tabs; inline Markdown such as code spans, emphasis, and links is part of the text. The comparison is case-sensitive.

The markup style of a heading is not structure. `Title` underlined with `===` equals `# Title`, a closing sequence such as `## Usage ##` equals `## Usage`, and extra spaces inside the text are ignored. Changing the style passes; changing the words, the level, or the position does not.

HeadingStructure aligns the declared and the actual headings along their longest common subsequence, and classifies what remains:

- A declared heading that appears at a different position is moved.
- Within one gap between aligned headings, a declared and an actual heading with the same text are a level change.
- When the remaining declared and actual headings of a gap pair up one to one at the same levels, each pair is a rename.
- Everything else is an added heading or a missing heading. When one declared section is replaced by several new ones, HeadingStructure reports the additions and the removal instead of guessing which new heading replaced the old one.

## Heading Recognition

The Markdown structurer parses the block structure that decides whether a line is a heading, following CommonMark:

- ATX headings: up to three spaces of indentation, one to six `#`, then a space, a tab, or the end of the line. `#hashtag`, `\# text`, and seven `#` are not headings.
- Setext headings: one or more paragraph lines followed by an underline of `=` (level 1) or `-` (level 2). The paragraph lines are joined with a space. Without a preceding paragraph, `---` is a thematic break.
- Fenced code blocks opened with three or more backticks or tildes are skipped until a closing fence of the same character that is at least as long. An unclosed fence runs to the end of the document. A backtick fence whose info string contains a backtick is inline code, not a fence.
- Lines indented by four or more columns are never headings. Inside a list item, such a line may open a fenced code block, which is skipped as well.
- HTML blocks are skipped: comments, `<script>`, `<pre>`, `<style>`, and `<textarea>` blocks until their terminator, and block-level tags such as `<details>` or `<div>` until the next blank line.
- A paragraph of a list item or a block quote never becomes a setext heading, and a `#` line inside a block quote is not a document heading.
- A YAML front matter block between `---` lines at the start of the file is skipped.

Inline content is not parsed. The Markdown parser is shared by registered structurers and initialization. Policies consume its parsed headings without reading or parsing documents again.

## Undeclared Documents

A new Markdown file is the same failure as a new section: the structure grew without a human decision. If HeadingStructure ignored new files, an agent that is told not to add a section to the README would add `docs/development.md` instead.

HeadingStructure therefore reports `undeclared_document` for every file that matches a `scan` pattern and is not declared. Patterns are segment-aware and relative to the directory of `guard.yaml`:

- `*` and `?` match within one path segment and never cross `/`.
- A `**` segment matches zero or more directories.
- Wildcards never match names that start with a dot, and `**` does not follow symbolic links. A literal segment such as `.github` still matches.

`*.md` catches a new file next to the README, such as `CONTRIBUTING.md` or `DEVELOPMENT.md`, and `docs/**/*.md` catches a new page at any depth under `docs/`. Avoid `**/*.md` at a repository root, which also scans `vendor/` and every package.

The heading policy uses the declared document list to identify undeclared files and report the required correction. File discovery is shared with every other policy through the common collector.

## Rule Identifiers

Reports prefix these identifiers with `documentation.`.

| Rule id | Reported location | Fires when |
|---------|-------------------|-----------|
| `unexpected_heading` | `path:line` of the heading | A heading is not in the declared structure. |
| `missing_heading` | `path` | A declared heading is absent. The message names the heading declared before it. |
| `renamed_heading` | `path:line` of the new heading | A different heading at the same level took the place of a declared heading. |
| `changed_heading_level` | `path:line` of the heading | A declared heading appears with a different level. |
| `moved_heading` | `path:line` of the heading | A declared heading appears out of its declared order. |
| `missing_document` | `path` | A declared document does not exist. |
| `undeclared_document` | `path` | A file matching a `scan` pattern is not declared. |

Diagnostic messages identify the affected heading and the required correction.

## Relaxations

The default is strict: adding, removing, renaming, moving, and re-leveling a section are all structural changes and all fail. Two relaxations exist, each for a stated reason.

| Relaxation | Why |
|------------|-----|
| Markup style and whitespace are ignored | ATX versus setext, closing `#` sequences, and spacing change how a heading is written, not the structure a reader navigates. Failing on them would only teach agents to ask for configuration updates that carry no decision. |
| `max_level` per document | Some reference documents enumerate items as headings, such as one `###` per supported SQL statement in a specification. There a new heading documents a new feature rather than growing the structure, and requiring a configuration update for every item turns the gate into noise. Limit this to the document that needs it; product READMEs and `AGENTS.md` keep the default. |

Removing a section is not relaxed. Deleting a Limitations or Security section hides information as effectively as adding a stray section adds noise, and the declaration must stay in sync with the document either way.

## Monorepos

Each package can declare its documents in its own `guard.yaml`. A root policy can declare root-level files. `guard check --config=../../guard.yaml` checks that root policy from a package directory; paths remain relative to the policy file.

## Reporting

Use `guard check --format=json` for machine-readable findings or the default text report. Missing files, changed headings and undeclared documents are required violations. Source documentation is not automatically rewritten by `guard apply`.
