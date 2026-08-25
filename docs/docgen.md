# DocGen

## Purpose

DocGen is a first-party CLI that generates a complete static HTML documentation site for the composer packages of a
project. AI agents work best when the whole picture of a codebase — interfaces, relationships, architectural layers,
and behavioral guarantees — is visible in one place; DocGen renders exactly that, with docs.rs-grade information
density, from the code and its PHPDoc without any manual documentation step.

## Command

```bash
vendor/bin/docgen
```

Exit codes:

- `0`: documentation generated
- `2`: configuration or runtime error

Without options, the project root and `packages/*` are documented into `build/docs`.

**There is no configuration file.** A run is described by its options alone: `--packages=GLOBS` and `--exclude=GLOBS`
(what is scanned), `--vendor[=GLOBS]` and `--vendor-dev[=GLOBS]` (document installed runtime and dev dependencies),
`--output=DIR`, `--title=TEXT`, `--deptrac=FILE`, `--coverage=DIR` (PHPUnit `--coverage-xml` report),
`--base-url=URL` (the address the site is published at), `--repository=URL` (the repository every page links back
to), `--diff=RANGE` / `--base=REVISION` / `--head=REVISION` (compare two git revisions),
`--serve[=HOST:PORT]` (preview the generated site locally), `--memory-limit=VALUE`, `--jobs=N`,
`--cache-dir=DIR`, `--no-cache`, and `--clear-cache`.

Documenting a large dependency tree needs more memory than the common 128M default, so the limit is raised to 512M
when the environment allows less. A higher environment limit is kept as is, and `--memory-limit=1G` or
`--memory-limit=-1` overrides both.

## Parallel Generation

Both expensive phases run in worker processes: the sources are parsed in parallel, and the symbol and source pages
are rendered in parallel. The workers are forked after the project has been analyzed, so they inherit the documented
model instead of being handed a copy of it.

The worker count comes from the machine unless a run says otherwise: one worker per logical CPU core, minus one for
the process that waits for them, and never more than 16. `--jobs=N` sets the count directly for a constrained runner,
and `--jobs=1` keeps everything in one process.

```bash
vendor/bin/docgen --jobs=4    # four workers
vendor/bin/docgen --jobs=1    # one process, no workers
```

Workers need the `pcntl` extension, and they are not used while OPcache or the JIT is on for the CLI, because their
shared memory is not safe to fill from several forked processes at once. A run without them is sequential rather than
failed, and the generated site is byte for byte the same however many workers wrote it: the work is split into
consecutive jobs and the results are merged in job order, so nothing about the site depends on which worker finished
first.

## Incremental Generation

A run remembers two things in its cache directory: what every source file parsed into, and what every page of the
site was written from. The next run parses only the files that changed, and rewrites only the pages that changed.

```bash
vendor/bin/docgen                       # parse and write only what changed
vendor/bin/docgen --no-cache            # parse everything, write everything, remember nothing
vendor/bin/docgen --clear-cache         # start from an empty cache
vendor/bin/docgen --cache-dir=.docgen   # keep the cache somewhere else
```

Every run reports what it reused:

```
Generated 1411 pages for 1 packages into build/docs
Cache: 861 of 862 sources and 1365 of 1411 pages reused
```

**The site never depends on the cache.** A cached run writes exactly the site a `--no-cache` run writes, down to the
byte, or it writes the page again. That holds because of what the two halves are keyed on:

- A **source** is remembered under the content of the file, the package it belongs to, its path in the project, and
  the generator itself. Parsing one file reads nothing but that file, so a file that agrees on all four parses into
  the same symbols and references it did before.
- A **page** is remembered under a digest of everything it is rendered from: the symbol it documents, the relations,
  call sites, test cases, and coverage the rest of the project has for it, the navigation of its scope, and what
  every name it prints currently resolves to. The last part is what a digest of the page's own data would miss: a
  page changes when a class it merely names appears, disappears, or moves, because that is when its links change.
  A page is also rewritten when the file it was written to is gone or no longer has the size this cache wrote it
  with, so an emptied or half-copied output directory is rebuilt rather than declared up to date.

A page the project no longer has is removed from the output directory, so a cached run leaves no page behind that a
full run would not have written.

The cache is invalidated as a whole by the installed version of the generator — this toolkit and the parser
libraries it reads with, each with the exact revision composer installed — so upgrading any of them never serves
pages the previous version wrote. A generator changed without being installed again, such as a checkout of the
toolkit being worked on or a patched vendor directory, keeps its version and therefore its cache: generate with
`--no-cache` while changing the generator itself, or `--clear-cache` once afterwards.

Both halves survive branch switching: entries are keyed by content, not by path, and an entry no run has read for a
week is dropped. In a comparison run (`--diff`) the sources of both revisions are cached the same way, and the pages
are reused when the same two revisions are compared again.

The cache directory is a build artifact. It holds one entry per source file, of the same order of size as the symbols
that file declares, so a project of a thousand files keeps a few tens of megabytes.

## Options

```bash
vendor/bin/docgen \
    --packages=.,packages/* \
    --exclude='tests/Fixture/*' \
    --output=build/docs \
    --coverage=build/coverage-xml \
    --base-url=https://example.github.io/project
```

`--packages` (default `.` and `packages/*`) names directory globs, relative to the working directory, that are probed
for a `composer.json`. This is the only scope control: the documented unit is the composer package, and its PSR-4
autoload map decides which sources are read (`autoload` as API pages, `autoload-dev` as linkable source pages). A
repository without a root `composer.json` works because glob entries without a manifest are skipped.

`--vendor` and `--vendor-dev` (default: neither) name **composer package name** globs such as `k-kinzal/*` or `*`;
matching packages installed under any discovered `vendor/` directory are documented alongside the project packages.
`--vendor` selects runtime dependencies and `--vendor-dev` selects dev dependencies, because the dev tree is usually
noise: documenting it is opt-in per glob. Either option without a value means every package of that kind. The split
is read from `composer.lock` when one exists, and derived from the transitive closure of `require` otherwise.

Directory names such as `vendor` match nothing, so every glob that documents no installed package of that kind is
reported as a warning, as is every selected package that ships no autoloadable source (a phar-only package such as
`phpstan/phpstan` can never be documented or linked).

`--exclude` (default: nothing) names path globs, relative to the project root, pruned from source scanning.

Every option that takes globs reads a comma-separated list and may be repeated, which adds to what the earlier
occurrences named — a Composer script and the job that calls it both get a say.

`--output` (default `build/docs`) is the site output directory. `--title` (default: the root package name, else the
project directory name) overrides the site title.

`--cache-dir` (default `build/docgen-cache`) is the directory the parsed sources and the written pages are
remembered in; `--no-cache` turns caching off for one run. Keep it outside the output directory: everything below the
output directory is part of the published site.

`--deptrac` (default: `deptrac.yaml` at the project root when present) points at a deptrac configuration whose layers
and ruleset are rendered as an architecture graph and per-class layer badges. `--coverage` (default: none) points at
a PHPUnit `--coverage-xml` report directory; when given, every method shows the test cases covering it.

`--base-url` (default: none) is the absolute address the site is published at, such as
`https://example.github.io/project`, without a trailing slash. It is the one thing a site of relative links cannot
work out for itself, and it is what the social preview below is rendered from. An address that is not an absolute
`http` or `https` URL is rejected.

`--repository` (default: what the root package's `composer.json` declares) is the address of the repository the
documented project lives in, and it is what the link back to the code below leads to. It overrides what the project's
own packages declare, which is the answer where a repository has moved, where the manifest declares nothing, and
where the site is generated from a checkout that is not the published one. An address that is not an absolute
`http` or `https` URL is rejected.

An unknown option is rejected with an error, and `docgen --help` lists every option there is.

## Scope Semantics

The documented scope is exactly the union of the discovered packages' PSR-4 autoload directories. Production
autoload sources get full API pages, search entries, and navigation; `autoload-dev` sources (tests) get highlighted
source pages only, so coverage references and test call sites remain linkable without polluting the API surface.
Symbols reachable through more than one package autoload map are documented once, first package wins.

## Visibility and Public API Sites

DocGen reads the toolkit's custom `@visibility` tag from classes, interfaces, traits, enums, functions, and members.
In the default complete site, symbol lists label explicit public API with a green **public API** chip and label narrower
scopes with the tag as written, such as **@visibility namespace** or **@visibility App\\Billing**. Declaration and
member pages repeat that status in a notice before the prose, so package, layer, and namespace routes all lead to the
same boundary statement. Scope resolution and enforcement remain PHPStan's responsibility; DocGen does not maintain
a second implementation of those rules.

`--public-api` publishes a consumer-facing view:

```bash
vendor/bin/docgen --public-api
```

- Package, architecture-layer, namespace, all-item, sidebar, count, and search surfaces include only top-level
  declarations carrying `@visibility public`. An untagged declaration is intentionally omitted: it remains reachable
  in PHP, but it has not declared the public-API intent this mode publishes.
- A listed class-like page includes its public and protected PHP members unless a member narrows its own scope with a
  non-public `@visibility` tag. Private members, restricted members, test cases, call sites, and relation indexes are
  omitted as implementation detail.
- Non-listed production symbol pages and highlighted sources are still generated. A public signature can therefore
  link to a support type without creating a broken link or promoting that type into navigation or search. This is
  documentation curation, not an access-control or secrecy boundary; generated URLs and the source repository remain
  readable.

The default remains the complete internal view so adding `@visibility` cannot silently remove existing documentation.
Use that view while developing and `--public-api` for a library's published site. Diff mode applies the same selection
to both revisions, so public API additions and removals stay visible without internal churn entering the indexes.

## Analysis

Types merge the native declaration with PHPDoc, where PHPStan-prefixed tags win over Psalm-prefixed tags, which win
over standard tags — generics, array shapes, conditional types, `@template` bounds, and `@phpstan-type` aliases are
rendered in full with every documented class name linked. Relations are indexed project-wide: interface implementors,
subclasses, trait users, and reference sites (`new`, static and resolvable instance calls, `instanceof`, attributes,
type declarations), with call sites listed per method and test references labeled. Examples use the doctest
notation: `@example` blocks and ` ```php ` fences are rendered as runnable doctest figures with their `// =>`,
`// Output:`, and `// throws` assertions styled; single-line `@example expr // note` tags render as display-only
examples. A runnable figure carries a **doctest** badge titled with the example's identifier, a **copy** button for
the example itself, and a **run** button carrying the `vendor/bin/phpunit --filter` command for that one example. See
[Doctest](doctest.md).

## Navigation

The site drills down the way rustdoc does. `index.html` lists the packages and their dependency graph; a package page
lists its architecture layers, its namespaces with item counts per kind, its documents, and its README; `all-items.html`
lists every symbol of the package grouped by kind; a layer page lists the namespaces the layer spans and then its
symbols; a namespace page lists its child namespaces and its symbols; a symbol page documents one class, interface,
trait, enum, or function.

The sidebar is scoped to the current page rather than to the whole project: the sections of the page itself, then the
widest scope first — the package entry points with its layers and documents — and then the current namespace with the
symbols next to this page, grouped by kind with interfaces first.

## Documents

Every Markdown file of the repository is rendered as a page under `<package>/doc/`, titled by its first heading, and
listed on the package page. Dependency, build, and hidden directories are pruned, and the configured `exclude` globs
apply, so the documents are exactly the prose of the analyzed repository. Markdown links between those files resolve
to the rendered pages, which makes a `docs/` tree readable inside the site instead of ending in dead relative paths;
a link to anything else is rendered as plain text with the target kept as a tooltip.

## Symbol pages

A symbol page reads from the symbol outwards: first what it is (signature with fully linked types, prose, examples),
then its members, then its private surface, and only then what surrounds it — the test cases that exercise it and the
relations that connect it.

Each member documents its parameters, its return, and its throws as separate labeled blocks, and lists both directions
of the dependency question: what calls it, and what it calls. Test call sites never appear there; what tests guarantee
is the test case section, which merges the coverage report with the calls made by the analyzed test sources and splits
the result into the symbol's own dedicated test class and the other tests that reach it.

Relations are grouped by what each reference does — implemented by, extended by, instantiated in, static calls, type
declarations, and so on — with every group rendered the same way.

A declaration that carries a `@visibility` tag opens its page with a **Restricted visibility** notice naming the scopes
exactly as written, so a reader sees what is public API and what is an implementation detail before reaching for it.
The notice only reports the declaration; [EnforceVisibilityScopeRule](rules/EnforceVisibilityScopeRule.md) is what
enforces the scope, and documentation that resolved scopes itself would be a second implementation of the same rules.

## Output

The site is fully static and self-contained: relative links only, bundled CSS/JS, client-side search, light and dark
themes, and a `.nojekyll` marker, so publishing the output directory with GitHub Pages needs no further setup. The
top level contains `index.html`, one directory per package (with its `doc/` documents), `src/` (highlighted sources
with line anchors), and `assets/`.

## Back to the Repository

A generated site is the read side of a repository, so every page carries the way back to it: the page shell links the
host of the repository next to the theme toggle, and a package page names it in full as its `Repository` row.

The address is read from the `composer.json` of the package itself — `support.source` first, because that is what
composer links a package to its code with, and `homepage` after it, because it is the only address a package that
declares no source offers a reader. Only an absolute `http` or `https` address is linked, so a `git@…` or `git://`
source is ignored rather than rendered as a link a browser cannot follow. `--repository` overrides what the
project's own packages declare; a documented dependency always links to what its own manifest says, because it is
published from somewhere else. A package that says nothing gets no link rather than a guess.

The other direction is the repository's own: the site is published at one address, so a link to it from the README,
from `homepage`, or from `support.docs` in `composer.json` closes the loop.

## Social Preview

A link to a page shared in a chat or on a timeline is rendered as a card by whoever receives it, from tags the page
has to carry. Those tags name absolute addresses, so they are written only when `base_url` says where the site is
published; without it nothing changes about the site.

With it, every page carries its canonical link, a `description` read from what the page documents, and the Open
Graph and Twitter tags a card is built from: `og:type`, `og:site_name`, `og:title`, `og:description`, `og:url`, and
the image below. The description is the page's own — the summary line of a class, interface, trait, enum, or
function, what a package says about itself in its manifest, or what a namespace, layer, document, or source file is
— cut to 200 characters.

The card image is drawn per site rather than shipped with the generator, so every project previews as itself: a
1200×630 PNG at `assets/og-image.png`, in the palette of the site, with the site title, the description of the
documented project, and the color band of the generator across the top. Drawing it needs the `gd` extension
with FreeType support; without it the run writes no image, the pages carry no `og:image`, the card degrades to a
`summary` card, and a warning says so — a card that names an image the site does not serve would be worse.

```bash
vendor/bin/docgen --base-url=https://example.github.io/project/pr/42
```

names the address of one run, which is what a pull request preview published under its own directory wants.

## Local Preview

```bash
vendor/bin/docgen --serve
```

generates the site and serves it with the PHP built-in web server at `127.0.0.1:8090` until interrupted. Pass
`--serve=PORT` or `--serve=HOST:PORT` to change the address.

## Diff Mode

```bash
vendor/bin/docgen --diff=main            # main against the working tree
vendor/bin/docgen --diff=v1.0.0..HEAD    # two revisions
vendor/bin/docgen --base=main --serve    # the same, previewed locally
```

`--diff=BASE` compares the working tree against `BASE`; `--diff=BASE..HEAD` compares two revisions. `--base` and
`--head` set the same two revisions separately, and `--head` without `--base` is rejected. Diff mode needs the project
to live in a git working tree: the base revision — and the head revision when one is named — is checked out into a
temporary worktree, the installed dependencies of the project are linked into it so a dependency is not read as newly
added, and both checkouts are removed again whatever the run does.

Both revisions are analyzed and merged into one site: what the head revision has, plus what the base revision had and
the head dropped. Every element carries the state it is in — added, removed, modified, or unchanged — and pages show
it down to the single argument of a declaration: a constructor that gained a parameter is shown whole, with only that
parameter marked. Symbols are matched by name rather than by position, so moving a method inside a class is not a
change; a documentation comment that was only reflowed is not a change either, while its wording is.

Removals keep their page. A class the head revision no longer has is documented from the base revision, marked as
removed, and its source page is rendered from the base checkout, so the source a removed symbol links to stays
readable. Source pages merge both revisions line by line, numbered after the head revision so every `#L…` link of the
site keeps working; documents are merged block by block, so a changed paragraph, list, table, or example is marked on
its own.

Marks use the three state hues and nothing else: **green** for what the head revision added, **red** for what it
dropped, **amber** for what it changed. A container carries the state of its parts, so a section, a namespace, an
architecture layer, or a package is added only when everything in it is, changed as soon as one part of it is, and
unchanged only when nothing in it moved.

The generated site serves three display modes, switched next to the theme toggle and remembered across pages:

| Mode | Shows |
|------|-------|
| **Off** | The documentation of the head revision, without any diff marks |
| **Diff** | Every page with its additions, removals, and changes marked |
| **Changes** | Only what the comparison touched; a page nothing touched says so |

Changes mode narrows the navigation with the page: the sidebar keeps the sections, namespaces, layers, and sibling
symbols a revision touched and drops the rest, so the way to a change is as short as the change is visible.

The mode is applied before the page is laid out, so a page never flashes the wrong colors or the whole file when the
changes alone were asked for. Source lines and the arguments of a signature keep their neighbours in Changes mode,
because a line or an argument cannot be read without the declaration around it.

A coverage report describes the working tree, so it is only read for the head revision; the base revision is analyzed
without one, and its methods carry no coverage figure.
