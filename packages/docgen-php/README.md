# docgen-php

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Generate PHP API documentation and coverage sites from source and PHPDoc.

## Requirements

- PHP 8.0 or later

## Getting Started

```console
$ composer require --dev k-kinzal/docgen-php
$ vendor/bin/docgen --help
Description:
  Generate a static HTML documentation site for the composer packages of the project

Usage:
  docgen [options]

Options:
      --packages=PACKAGES          Comma-separated directory globs probed for a composer.json; each match becomes a documented package [default: [".","packages/*"]] (multiple values allowed)
      --exclude=EXCLUDE            Comma-separated path globs, relative to the project root, pruned from source scanning, such as tests/Fixture/* (multiple values allowed)
      --vendor[=VENDOR]            Also document installed runtime vendor packages whose name matches a glob, such as acme/*; bare --vendor means all of them (multiple values allowed)
      --vendor-dev[=VENDOR-DEV]    Also document installed dev vendor packages whose name matches a glob, such as phpunit/*; bare --vendor-dev means all of them (multiple values allowed)
  -o, --output=OUTPUT              Directory the site is written to [default: "build/docs"]
      --title=TITLE                Site title (default: the name of the root package, else of the project directory)
      --public-api                 Publish only declarations marked @visibility public
      --deptrac=DEPTRAC            Deptrac configuration file the architecture graph and layer badges are read from (default: deptrac.yaml when it exists)
      --coverage=COVERAGE          PHPUnit --coverage-xml report directory, used to link methods to the tests that cover them
      --base-url=BASE-URL          Address the site is published at, such as https://example.github.io/project; adds canonical links and social preview tags
      --repository=REPOSITORY      Repository address every page links back to (default: support.source, then homepage, of the root package)
      --diff=DIFF                  Compare git revisions: BASE compares the working tree against BASE, BASE..HEAD compares two revisions
      --base=BASE                  Base revision of the comparison; overrides the base of --diff
      --head=HEAD                  Head revision of the comparison (default: the working tree); overrides the head of --diff
      --cache-dir=CACHE-DIR        Directory parsed sources and written pages are remembered in between runs [default: "build/docgen-cache"]
      --no-cache                   Parse every source and write every page, and remember nothing of it
      --clear-cache                Remove the cache directory before generating
      --serve[=SERVE]              Serve the generated site at HOST:PORT or a port after generation (default address: 127.0.0.1:8090)
      --memory-limit=MEMORY-LIMIT  Memory limit of the run, such as 1G or -1 (default: the environment limit, raised to 512M)
  -j, --jobs=JOBS                  Number of worker processes (default: one per CPU core minus one, at most 16); 1 stays in one process
  -h, --help                       Display help for the given command. When no command is given display help for the docgen command
      --silent                     Do not output any message
  -q, --quiet                      Only errors are displayed. All other output is suppressed
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Generates a static HTML documentation site for the composer packages of the
  current project. Everything is named on the command line: without options,
  the project root and packages/* are documented into build/docs.

  Options that take globs accept a comma-separated list and may be repeated,
  which adds to what the earlier occurrences named.

  Examples:
    docgen --exclude=tests/Fixture/*        document the project into build/docs
    docgen --vendor=acme/* -o public/docs   also document acme packages
    docgen --diff=main                      mark what changed since main
    docgen --serve=8080                     generate, then preview the site

  Exit codes:
    0  documentation generated
    2  invalid command line, configuration, or runtime error
```

## License

[MIT](LICENSE)
