# decisions-lint

[![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Lint PHP code against rules no static analyser can check: whether a method
belongs to the class that holds it, whether a use case leaks a business rule,
whether a name says what the code does. Each rule is a typed question a
decision model answers in one short request, so a run over a code base takes
seconds, and each violation comes with the change that resolves it.

## Requirements

- PHP 8.0 or later with the cURL extension
- An API key for [Jev](https://docs.typesafe.ai) (TypeSafe AI) or the
  [OpenAI Decisions API](https://developers.openai.com/api/docs/guides/decisions),
  or a local chat model served by [Ollama](https://ollama.com), [LocalAI](https://localai.io)
  or any OpenAI-compatible server with JSON schema output

## Getting Started

```console
$ composer require --dev k-kinzal/decisions-lint
$ vendor/bin/decisions-lint init
$ export TYPESAFE_API_KEY=...
$ vendor/bin/decisions-lint --help
decisions-lint 1.0.0

Judges PHP code against nuanced rules with a decision model, as configured in decisions.yaml. Running decisions-lint without a command runs check.

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
  check       Judge the configured files against the rules with the decision model and report violations
  completion  Dump the shell completion script
  help        Display help for a command
  init        Create a decisions.yaml that imports the shipped rule files
  list        List commands
  plan        List the declarations each rule would judge and the requests that takes, without calling the provider
  rules       List the effective rules after imports and overrides, with their severity, target and decision
  validate    Check the configuration and its imports, and any rule file given, against their schemas
```

`decisions-lint plan` shows what a run would ask before anything is sent.
`decisions-lint check` asks the model and reports each violation with its file,
line, column, rule, message and fix, as text, `ai`, `json`, `checkstyle` or
`github` output. See [the documentation](docs/decisions-lint.md) for the
configuration, the rule format and the providers.

## License

[MIT](LICENSE)
