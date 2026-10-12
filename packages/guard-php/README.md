# guard-php

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Check and repair PHP project policies for source metrics, directory structure,
Markdown documents, and tool configuration.

## Requirements

- PHP 8.0 or later

## Getting Started

```console
$ composer require --dev k-kinzal/guard-php
$ vendor/bin/guard --help
guard 1.0.0

Checks and repairs a project against the policies in its guard.yaml. Running guard without a command runs check.

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display help for the given command, or list the commands when none is given
      --silent          Do not output any message
  -q, --quiet           Only errors are displayed. All other output is suppressed
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  baseline    Record current violations in a baseline for subsequent checks
  check       Check the project against its policy without changing any file
  completion  Dump the shell completion script
  fix         Repair the violations guard can fix, then report what remains
  help        Display help for a command
  init        Create a policy file with the presets that fit the project
  list        List commands
  rules       List active rules, their messages and automatic fix support
```

## License

[MIT](LICENSE)
