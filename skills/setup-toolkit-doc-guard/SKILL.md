---
name: setup-toolkit-doc-guard
description: >-
  Set up DocGuard Markdown document structure checks for a PHP project. Use
  when asked to configure doc-guard, doc-guard.yaml, fixed section structure for
  README.md, AGENTS.md, or docs/, stopping AI agents from adding sections,
  pages, or documents, detecting renamed, moved, re-leveled, or removed
  headings, generating a document structure declaration from the current
  documents, DocGuard in a monorepo, DocGuard reporters, Composer scripts for
  DocGuard, or CI checks for documentation structure.
---

# Setup DocGuard (Document Structure Guardrails)

## Unified Guard entry point

The current package is `k-kinzal/guard-php`, which exposes `vendor/bin/guard`. For a new project, run `guard init`, then configure the `documentation` section of `guard.yaml`. Preserve the fixed constraints documented below. Run `guard check` from Composer and CI; use `guard apply --dry-run` to inspect configuration-value repairs and `guard apply` for authorized changes. Source and structural violations still require code/document edits.

The legacy workflow below is retained for migration. When starting with these legacy templates, run `guard init` after adaptation to import them without changing constraints, then retire the separate legacy policies. Do not replace an existing `guard.yaml` or reduce constraints to pass a check. See `vendor/k-kinzal/php-ai-toolkit/docs/guard.md`. Legacy generation/explanation executables are available as `php vendor/k-kinzal/guard-php/bin/doc-guard`.


This skill configures `doc-guard`, the php-ai-toolkit CLI that fixes the heading structure of Markdown documents. A human-managed `doc-guard.yaml` declares each document's headings — level, text, and order — and lists the patterns under which no undeclared document may appear. Content inside sections stays free to edit.

## Prerequisites

Inspect the project before configuring:

- Confirm the target project requires `k-kinzal/php-ai-toolkit`.
- List the maintained Markdown documents: the root `README.md`, `AGENTS.md`, `CLAUDE.md`, and the pages under `docs/`.
- Check for an existing `doc-guard.yaml` and existing Composer scripts and CI jobs.
- Check the working tree. The generated declaration adopts the documents as they are, so uncommitted documentation edits would become part of the reference.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

The unversioned requirement is intentional for a new install: Composer should
select the newest stable toolkit release compatible with the target project. Check
current package metadata and the target's PHP/dependency graph first. If the toolkit
is already constrained, update its lock to the newest admitted release and preserve
an intentional pin unless changing that policy is in scope. Never copy this
repository's root constraint or lock resolution.

## Generate the Declaration

The reference structure is the structure the documents have now. Generate it instead of writing it by hand:

```bash
php vendor/k-kinzal/guard-php/bin/doc-guard --generate > doc-guard.yaml
```

Without paths, the generator declares every `*.md` file in the working directory and every Markdown file below `docs/`, and scans the same patterns. Pass files and directories to choose the set explicitly:

```bash
php vendor/k-kinzal/guard-php/bin/doc-guard --generate README.md AGENTS.md docs > doc-guard.yaml
```

Review the generated file before keeping it:

- Every maintained document is declared, and nothing generated or vendored is.
- `scan` covers the places where a new document would be a structural change: `*.md` next to the README and `docs/**/*.md`. Do not scan `**/*.md` at a repository root, which reaches `vendor/` and every package.
- The NOTE header is the first line. It marks the file as human-managed, like `tree.yaml` and `loc.yaml`.

Do not restructure, trim, or rename the documents while setting up DocGuard. Adopting the gate does not authorize a documentation change; fix the declaration to the documents, not the documents to a preferred outline.

## Policy

Keep the default strictness. Adding, removing, renaming, moving, and re-leveling a section all fail, and so does a new document under a scanned pattern. Heading style (ATX or setext, closing `#`, spacing) is not structure and passes.

Use `max_level` only for a reference document whose deeper headings enumerate items, such as one `###` per supported statement in a specification, where each new heading documents a new feature. Set it on that document alone:

```yaml
documents:
  'docs/spec.md':
    headings:
      - '# Specification'
      - '## SELECT'
    max_level: 2
```

Do not add `max_level` to `README.md` or `AGENTS.md`, and do not drop a document or a scan pattern to make a violation pass.

## Monorepo

Configuration paths are relative to the configuration file, so each file describes the documents next to it:

- Each package gets its own `doc-guard.yaml`, generated in the package directory, declaring its `README.md` and `docs/` and scanning `*.md` and `docs/**/*.md`. Run it from the package's `lint` script next to `tree-guard`.
- The repository root gets a `doc-guard.yaml` for the root `README.md`, `AGENTS.md`, and `CLAUDE.md`, scanning `*.md` only. Generate it from the root with `php vendor/k-kinzal/guard-php/bin/doc-guard --generate README.md AGENTS.md CLAUDE.md`, then add `scan: ['*.md']` if new root documents should fail.

Run the root configuration where the root is checked, such as a root Composer script invoked by a root CI job. If the repository has no root-level CI, run it from the package workflow that owns repository-wide checks with `doc-guard --config=../../doc-guard.yaml`; do not copy the root documents into a package configuration.

## Reporter

Keep `report.reporter: ai` by default for this toolkit. Its guidance tells an agent to write new information inside the existing section that covers it and to ask a human to update `doc-guard.yaml` when the structure really has to change.

Use `text` for concise human output and `json` for CI or machine consumers. Supported `order_by` fields are `path`, `line`, and `rule`.

## Recommended Composer Scripts

Add scripts that match the project:

```json
{
    "scripts": {
        "doc-guard": "doc-guard --config=doc-guard.yaml",
        "lint": [
            "@format:check",
            "@phpstan",
            "@loc-guard",
            "@tree-guard",
            "@doc-guard",
            "@deptrac"
        ]
    }
}
```

If the project already has `lint` or `check`, merge `@doc-guard` into it after TreeGuard and before Deptrac when those scripts exist. Do not remove existing lint steps.

## Changing the Structure

A structure change is a human decision. When one is approved, edit the documents, regenerate the declaration with the same paths, and review the diff of `doc-guard.yaml` together with the documentation change. An agent that meets a DocGuard violation restores the declared structure; it does not edit or regenerate `doc-guard.yaml`.

## Verification

After applying:

```bash
php vendor/k-kinzal/guard-php/bin/doc-guard --config=doc-guard.yaml
```

Exit codes:

- `0`: no violations
- `1`: structure violations found
- `2`: configuration or runtime error

A fresh declaration must pass. Confirm that the gate works by adding a throwaway heading to a declared document, running the check, and reverting the edit.

## References

- [DocGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/doc-guard.md) — Settings, heading semantics, undeclared documents, and CLI behavior.
