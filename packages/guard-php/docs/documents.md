# Documents

The `documentation` section fixes the structure of Markdown files: their headings, the outline they follow, the badges under their title, or their exact content. It also reports Markdown files that nobody declared. What is written inside a section stays free; adding, removing, renaming or moving a section is a decision that a human records in `guard.yaml`. Guard never rewrites a document.

## Example

```yaml
version: 1
documentation:
  files:
    README.md:
      outlines:
        library:
          - '# *'
          - '## Installation'
          - '## Usage'
          - {heading: '## *', optional: true, repeat: true}
          - '## License'
      badges:
        - {name: PHP, label: PHP, image: 'https://img.shields.io/badge/php-*'}
        - {name: License, label: License, image: 'https://img.shields.io/badge/license-*'}
    docs/reference.md:
      headings: ['# Reference', '## API', '## Errors']
    CLAUDE.md:
      content: '@AGENTS.md'
  scan: ['*.md', 'docs/**/*.md']
  exclude: ['docs/generated/**']
```

`README.md` has any title, then Installation, Usage, any further sections, and License, and a PHP and a License badge under the title. `docs/reference.md` has exactly three headings. `CLAUDE.md` contains nothing but `@AGENTS.md`. Any other Markdown file in the root or under `docs/` fails.

## Fields

| Key | Description |
|-----|-------------|
| `files` | Required. Declared documents, keyed by path relative to `guard.yaml`. |
| `files.<path>.headings` | The exact list of headings. See [Headings](#headings). |
| `files.<path>.max_level` | Deepest heading level that `headings` checks, 1–6. Default `6`. |
| `files.<path>.outlines` | Named outlines, of which the document must follow one. See [Outlines](#outlines). |
| `files.<path>.badges` | The badges under the title, in order. See [Badges](#badges). |
| `files.<path>.content` | The exact content of the file. See [Content](#content). |
| `scan` | Patterns of Markdown files that must be declared. Default `[]`. See [Undeclared documents](#undeclared-documents). |
| `exclude` | Patterns that `scan` skips. Declared files are still checked. Default `[]`. |

A document declares at least one of `headings`, `outlines`, `badges` and `content`, and may combine them; each one is checked separately. Write headings in ATX notation and quote them, because YAML reads an unquoted `#` as a comment. `headings: []` declares a document without headings.

A declared document that does not exist is reported as `documentation.missing_document`.

## Headings

`headings` lists every heading of the document, in order, down to `max_level`. Two headings are equal when their level and text are equal. The text is compared after trimming and collapsing spaces and tabs, case-sensitively, with inline Markdown such as code spans and links as part of the text. How a heading is written does not matter: `Title` underlined with `===` equals `# Title`, and `## Usage ##` equals `## Usage`.

Guard aligns the declared and actual headings on their longest common subsequence and classifies the rest:

| Rule ID | Reported at | When |
|---------|-------------|------|
| `documentation.moved_heading` | the heading | A declared heading appears at another position. |
| `documentation.changed_heading_level` | the heading | A declared heading appears with another level. |
| `documentation.renamed_heading` | the new heading | Another heading of the same level took the place of a declared one. |
| `documentation.unexpected_heading` | the heading | A heading is not declared. |
| `documentation.missing_heading` | the document | A declared heading is absent. The message names the heading declared before it. |

When one declared section is replaced by several new ones, Guard reports the additions and the removal instead of guessing which new heading replaced the old one.

Set `max_level` only for a document that lists items as headings, such as one `###` per supported statement in a reference. There a new heading documents a new item rather than a new section. Removing a section is never relaxed: deleting a Limitations section hides information as surely as a stray section adds noise.

## Outlines

`outlines` allows more than one shape. Each outline is an ordered list of entries, and the document passes when it follows any one of them:

| Entry | Matches |
|-------|---------|
| `'## License'` | That heading, exactly once. |
| `'## *'` | One heading of that level whose text no other entry of the outline names. |
| `{heading: '## Architecture', optional: true}` | That heading at most once. |
| `{heading: '## *', optional: true, repeat: true}` | Any number of other level-2 headings, including none. |
| `{one_of: ['## Vision', '## Product Vision']}` | Either heading, exactly once. |

A mapping sets exactly one of `heading` and `one_of`, and may add `optional` (the entry may be absent) and `repeat` (it may occur more than once). `one_of` cannot contain `*`.

An outline sees headings down to its deepest declared level, so deeper headings are content; `max_level` does not apply. When no outline fits, Guard reports the outline with the fewest findings, and on a tie the one whose named headings the document uses most:

| Rule ID | Reported at | When |
|---------|-------------|------|
| `documentation.unexpected_outline_heading` | the heading | A heading does not fit the outline at its position. The message lists what the outline allows there. |
| `documentation.missing_outline_heading` | the document | A required entry is not filled. The message names the entry it follows. |

A renamed section is reported once, as one unexpected and one missing heading, not as a failure of every heading after it.

## Badges

`badges` declares the badge block under the document's first level-1 heading, in order:

| Key | Description |
|-----|-------------|
| `name` | Required. Unique name, used in messages. |
| `image` | Required. Glob for the image URL, as in `fnmatch`; `*` also matches `/`. |
| `label` | The exact alternative text of the image. Any text when omitted. |
| `optional` | The badge may be absent. Default `false`. |
| `repeat` | The badge may occur more than once, such as one per workflow. Default `false`. |

The badge block is the first run of badge-only lines after the title, after any blank lines. A badge-only line holds nothing but linked images such as `[![PHP](https://img.shields.io/badge/php-8.0%2B-blue)](https://www.php.net/)`. The first other line ends the block.

| Rule ID | When |
|---------|------|
| `documentation.missing_badges` | The document has no level-1 heading, no badge line under it, or its badges stand above it. |
| `documentation.unexpected_badge` | A badge matches no declaration, or matches one out of order or more often than allowed. |
| `documentation.missing_badge` | A required badge is absent. The message gives its image pattern, label and position. |

## Content

`content` is the exact text of the file. Bytes are compared without normalization, so a trailing newline that `content` lacks is a difference. Use it for files that only point elsewhere, such as a `CLAUDE.md` that imports `AGENTS.md`. A difference is reported as `documentation.unexpected_content`.

## Undeclared documents

A new Markdown file is the same change as a new section: an agent told not to add a section to the README can add `docs/development.md` instead. Each file that matches a `scan` pattern and is not declared is reported as `documentation.undeclared_document`.

`scan` and `exclude` patterns are relative to `guard.yaml`. `*` and `?` match within one path segment, and a `**` segment matches any number of directories. Wildcards do not match names that start with a dot, but a literal segment such as `.github` does. `*.md` catches a new file next to the README, and `docs/**/*.md` a new page at any depth under `docs/`. Avoid `**/*.md` at the root, which also scans `vendor/`. When [`collect`](configuration.md#collection-scope) is set, it replaces `exclude`.

## Heading recognition

Guard parses the block structure of Markdown as CommonMark does, so only real headings count:

- An ATX heading has up to three spaces of indentation, one to six `#`, then a space, a tab or the end of the line. `#hashtag` and `\# text` are not headings.
- A setext heading is one or more paragraph lines underlined with `=` (level 1) or `-` (level 2). Without a paragraph above it, `---` is a thematic break.
- Fenced code blocks, indented code, HTML blocks such as `<details>` or comments, and YAML front matter are skipped.
- A line in a block quote or a list item paragraph is not a document heading.

Inline Markdown is not parsed; it is part of the heading text.

## Shipped declarations

| Preset | Declares |
|--------|----------|
| `readme-md.yaml` | `README.md` follows one of three outlines and has the toolkit badges. `cli`: title, Requirements, Getting Started, other sections, License. `library`: title, Requirements, Installation, Usage, other sections, License. `monorepo`: title, Packages, an optional Related Projects, License. Badges, in order: docs, workflows, Packagist, PHP, License and DeepWiki, of which PHP and License are required. |
| `agents-md.yaml` | `AGENTS.md` has `# AGENTS`, then Vision or Product Vision, Supported Versions, an optional Architecture, and Documents. |
| `claude-md.yaml` | `CLAUDE.md` contains exactly `@AGENTS.md`. |
| `disable-doc.yaml` | The project root has no `docs/` directory, through a [structure](structure.md) rule reported as `structure.denied_dir`. |

To add a check to a shipped declaration, declare the same path in the project file; the keys you set are added to the preset's, as in `README.md: {headings: [...]}` next to its outlines.

## Monorepos

Each package declares its documents in its own `guard.yaml`, and a root policy declares the root files. `guard check --config=../../guard.yaml` checks the root policy from a package directory; its paths stay relative to the root policy.
