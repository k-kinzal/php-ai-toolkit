# Markdown documents

Use `guard rules --query=documentation` to inspect declarations and
`guard check --query=documentation` to inspect findings. All built-in document
findings are required and need manual edits; `fix` does not rewrite Markdown.

| Finding | What to inspect |
|---------|-----------------|
| Missing, unexpected, renamed, moved, or changed-level heading | Declared heading text, level, and order; move the section with its content. |
| Missing or unexpected outline heading | The allowed headings at the reported outline position. |
| `documentation.undeclared_document` | `documentation.scan` and the declarations under `documentation.files`. |
| `documentation.missing_document` | The declared file path and whether it was moved or removed. |
| Missing or unexpected badge | Labels, URL patterns, and badge order directly below the title. |
| `documentation.unexpected_content` | Exact declared bytes, including the final newline. |

Preserve document content when restoring structure. Change declarations when the
task calls for a new document shape; regenerating them from an accidental edit
would accept that edit as policy.

For requested policy changes, choose the declaration that expresses the constraint:

| Form | Checks |
|------|--------|
| `headings` | Exact ordered headings, written as quoted ATX strings such as `'## Usage'`. |
| `outlines` | A section pattern with optional or repeated headings and accepted alternatives. |
| `badges` | The linked-image badge block below the title; combines with headings or outlines. |
| `content` | The entire file, compared byte for byte. |

`documentation.scan` finds undeclared Markdown files. Use intended roots such as
`['*.md', 'docs/**/*.md']`; a root `**/*.md` may include dependency documents.
`max_level` limits the checked heading depth. `guard init` records current README
headings as a starting point; review them. Additional documents need declarations.

See the [documents schema](../../../docs/documents.md)
for outline patterns, badge syntax, and shipped declarations.
