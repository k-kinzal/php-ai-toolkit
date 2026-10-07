# Markdown documents

The `documentation` section fixes the headings, the outline, the badges or the exact content of Markdown files, and reports Markdown files that nobody declared. What is written inside a section stays free; adding, removing, renaming or moving a section is a decision recorded in `guard.yaml`. Every finding is required and Guard never rewrites a document. The semantics are in [the documents reference](../../../docs/documents.md).

## Resolving findings

Edit the content of the existing sections; leave the set of sections as declared. When the information has no section to go to, that is a question for a human, not a reason to add one.

| Rule ID | Practice |
|---------|----------|
| `documentation.unexpected_heading`, `documentation.unexpected_outline_heading` | Remove the heading and write its content into the existing section it belongs to. For an outline, the message lists what the outline allows at that position; use one of those headings only when the content is what that section is for. |
| `documentation.missing_heading`, `documentation.missing_outline_heading` | Restore the section at the position the message names. Removing a section hides information as surely as a stray section adds noise, so restore its content too, not an empty heading. |
| `documentation.renamed_heading` | Restore the declared text and edit only the content below it. Text is compared case-sensitively after collapsing spaces, with inline Markdown such as code spans as part of the text. |
| `documentation.moved_heading` | Move the whole section, with its content, back to the declared position. |
| `documentation.changed_heading_level` | Restore the declared level. To structure the content of a section, use paragraphs, lists or tables, or a deeper heading where the declaration allows it. |
| `documentation.undeclared_document` | Move the content into an existing section of a declared document and delete the file. Do not answer a forbidden section with a new file. |
| `documentation.missing_document` | Restore the declared document. Removing or renaming it is a policy change. |
| `documentation.missing_badges`, `documentation.unexpected_badge`, `documentation.missing_badge` | Put the badges on the lines right after the `# ` title, after one blank line, in the declared order. Each badge line holds only linked images. Add a missing required badge with an image URL that matches its pattern and the declared label. |
| `documentation.unexpected_content` | Replace the whole file with the declared content. Bytes are compared exactly, so a trailing newline that the declaration lacks is a difference. |

### Changes that hide a finding instead of fixing it

Guard recognizes headings as CommonMark does and skips code blocks, HTML blocks, block quotes and list items. Using that to keep a section out of sight is evasion:

- Turning a heading into bold text, a list item, an HTML `<h2>` or `<details>` block, a block quote or a code block.
- Renaming an undeclared `.md` file to `.txt`, `.markdown` or a dotted name to escape `scan`, or putting it under an excluded path.
- Emptying a section but keeping its heading, or moving information into a comment.
- Regenerating the `headings` list in `guard.yaml` from the edited document.

## Writing rules

- Declare each document with the least strict form that still records the decision:

  | Form | Use for |
  |------|---------|
  | `content` | Files that only point elsewhere, such as a `CLAUDE.md` containing `@AGENTS.md`. |
  | `headings` | Reference documents whose sections are fixed. Lists every heading, in order. |
  | `outlines` | Documents that follow a shape with room for project-specific sections, such as a README. |
  | `badges` | The badge block under the title. Combine with `outlines` or `headings`. |

- Write headings in ATX notation and quote them, because YAML reads an unquoted `#` as a comment.
- In an outline, name the sections every document of that kind must have, and allow the rest with `{heading: '## *', optional: true, repeat: true}`. Use `one_of` for accepted synonyms instead of a wildcard. Declare several named outlines when documents of different kinds share a path, such as `cli` and `library` READMEs.
- Set `max_level` only when a document lists items as headings, such as one `###` per supported statement in a reference; there a new heading documents a new item rather than a new section.
- Set `scan` so a new document cannot slip past the declarations: `*.md` for the root and `docs/**/*.md` for a documentation tree. Avoid `**/*.md` at the root, which also scans `vendor/`. Use `exclude` for generated documents only.
- Extend a shipped declaration by declaring the same path with the keys to add, such as `README.md: {headings: [...]}` next to the preset's outlines.
- Record the headings a human has agreed on. `guard init` captures the current README headings as a starting point; review them instead of committing them unread.

```yaml
documentation:
  files:
    docs/reference.md:
      headings: ['# Reference', '## API', '## Errors']
    docs/statements.md:
      headings: ['# Statements', '## Supported']
      max_level: 2
  scan: ['*.md', 'docs/**/*.md']
```
