---
name: setup-toolkit-readme
description: >-
  Set up a minimal README.md for a PHP project: badges, a one-paragraph
  overview, Requirements, Getting Started, and License. Use when asked to
  create, trim, or restructure README.md, add badges for CI workflows, a docs
  site, PHP versions, license, Packagist, or DeepWiki, or link the repository's
  DeepWiki page.
---

# Setup README

This skill writes the product README the way php-ai-toolkit projects keep it:
the least a reader needs to decide whether to install the package and to run it
once. Everything else lives in `docs/` or the generated documentation site.

Use this skill only when the user explicitly asks to create or change
`README.md`. Applying the toolkit, adding a workflow, or publishing
documentation is not permission to rewrite the product README; the other setup
skills say the same. If `README.md` already exists, treat it as product-owned:
keep the facts and prose in it, add or change only what the request names, and
ask before removing a section the user did not mention.

## Discover Project Facts

Read these before writing:

- `composer.json`: `name`, `description`, `require.php`, the `require` entries
  a user must provide themselves (extensions, external tools, framework peers),
  `license`, `homepage`, and `support.docs`.
- `LICENSE`: the license named by the badge and the closing section.
- `.github/workflows/*.yml`: every workflow that runs on the default branch is
  a status badge candidate.
- The docs workflow, the `gh-pages` branch, or `support.docs`: whether a
  documentation site is published and at which address.
- Packagist: whether `composer require` works without a `repositories` entry.
  Check `https://packagist.org/packages/<vendor>/<package>` rather than
  assuming; a package that is not there needs the VCS repository entry in
  Getting Started.
- The default branch name, for `?branch=` on workflow badges.
- In a monorepo, the package directory: badges link to the repository's
  workflows and to the package's own documentation path.

## Template

Read the template from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-readme/README.md` and
replace every `{{PLACEHOLDER}}` from project evidence. Do not copy this
repository's badges, versions, or wording.

| Placeholder | Value |
|-------------|-------|
| `{{PROJECT_NAME}}` | The package or repository name. |
| `{{BADGES}}` | The badge block from the Badges section, one badge per line. |
| `{{OVERVIEW}}` | One paragraph: what the package is, who it is for, and the one thing that distinguishes it. |
| `{{PHP_CONSTRAINT}}` | `require.php` written for people, such as `8.1 or higher`. |
| `{{ADDITIONAL_REQUIREMENTS}}` | One bullet per extension, external tool, or peer the package cannot install itself; remove the line when there are none. |
| `{{INSTALL_COMMAND}}` | The `composer require` line, with `--dev` for development tools and the `repositories` entry first when the package is not on Packagist. |
| `{{GETTING_STARTED_EXAMPLE}}` | The shortest complete example that does something useful: a code block for a library, the command and its output for a CLI. |
| `{{LICENSE_NAME}}` | The license name, such as `MIT License`. |

## Structure

The README has a title, a badge block, one paragraph, and three sections:
Requirements, Getting Started, and License. The overview is prose, not a
feature list.

Add a further section only when the reader cannot use the package without it:
a table of supported syntaxes or drivers, a CLI's `--help` output, or the shape
of a configuration file. Name that section for its content and keep it between
Getting Started and License. Do not add Features, Architecture, Contributing,
Changelog, Roadmap, or Acknowledgements sections; architecture and tooling
belong to `docs/` and the generated site, and the docs badge is the link to
them.

Prefer leaving information out over compressing it. A reader who needs more
follows the docs badge.

## Badges

Add every badge the project can back with a real target, in this order:

1. The documentation site, when DocGen or another generator publishes one:
   ```markdown
   [![docs](https://img.shields.io/badge/docs-<package>-0969da?logo=php&logoColor=white)](https://<owner>.github.io/<repository>/)
   ```
2. One status badge per workflow that runs on the default branch (CI, docs,
   Context7, and so on), pinned to that branch:
   ```markdown
   [![CI](https://github.com/<owner>/<repository>/actions/workflows/ci.yml/badge.svg?branch=<default-branch>)](https://github.com/<owner>/<repository>/actions/workflows/ci.yml)
   ```
3. The Packagist version, only when the package is published there:
   ```markdown
   [![Packagist](https://img.shields.io/packagist/v/<vendor>/<package>)](https://packagist.org/packages/<vendor>/<package>)
   ```
4. The supported PHP versions, derived from `require.php` and the CI matrix:
   ```markdown
   [![PHP](https://img.shields.io/badge/php-8.1%20%7C%208.2%20%7C%208.3-777bb4?logo=php&logoColor=white)](https://www.php.net/)
   ```
5. The license, from `composer.json` and `LICENSE`:
   ```markdown
   [![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
   ```
6. DeepWiki, for every public repository, as described below.

A badge must point at something that exists: no workflow badge for a workflow
the project does not have, no Packagist badge for a VCS-only package, no
coverage badge without a coverage service. Add a badge in the same change that
adds its target, never ahead of it.

## DeepWiki

DeepWiki (https://deepwiki.com) generates a browsable wiki and a question
interface for a public GitHub repository. Add its badge to every public
repository:

```markdown
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/<owner>/<repository>)
```

The link is the repository, not a package path: DeepWiki indexes repositories,
so a package inside a monorepo links to the monorepo's page.

Indexing is free for public repositories but does not happen by itself. Open
`https://deepwiki.com/<owner>/<repository>`; if the page offers to index the
repository instead of showing a wiki, ask the user to submit it there. The badge
renders before indexing finishes, but the link shows nothing useful until then,
so say so in the delivery. DeepWiki for private repositories is a paid Devin
feature; report that instead of adding a badge that leads to a sign-in page.

## Verification

After editing:

```bash
git diff --check
```

Then check that every absolute link and badge in the README resolves:

```bash
grep -o 'https://[^)"]*' README.md | sort -u | while read -r url; do
  if curl --silent --fail --head --location --output /dev/null "$url"; then
    echo "ok   $url"
  else
    echo "FAIL $url"
  fi
done
```

Confirm the install command works as written from an empty directory when the
package is public, and that the Getting Started example runs against the
installed package. Report anything that could not be executed locally.
