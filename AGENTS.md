<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->
# AGENTS

## Vision

Provide tools for AI-assisted PHP development that detect code, test, and configuration problems early and explain how to fix them. This repository is a monorepo of five Composer packages, each with its own dependencies and checks.

## Supported Versions

- **PHP**: 8.0 or later
- **PHPStan**: 1.12 or later on 1.x, and 2.x
- **PHPUnit**: 9.6, 10.5 or later on 10.x, and 11, 12, and 13
- **Rector**: 2.6.7 or later on 2.x, which requires PHPStan 2

## Branching Strategy

This project is developed on `main` only. Commit directly to `main`, whatever the size of the change.

- Do not create branches. A branch cannot be merged back here, so work left on one is stranded.
- Do not open pull requests. They are not part of this project's workflow.
- The common agent habit of branching before committing on a default branch does not apply here: `main` is the working branch.

## Rule Design Principles

- AI-friendly error messages: Every error message must clearly state *what is wrong* and *how to fix it*. An AI agent reading the message should be able to resolve the violation without additional context.
- Specific and identifiable: Messages must include enough detail (e.g., the offending symbol name, the expected pattern) so that each violation can be individually targeted in `ignoreErrors` configurations. Vague messages like "invalid code" are not acceptable.

## Documents

- [DocGen](packages/docgen-php/docs/docgen.md): How the DocGen CLI builds a static HTML documentation site from Composer package source and PHPDoc, with no configuration file of its own.
- [DocGen architecture](packages/docgen-php/docs/architecture.md): How a DocGen run is split into discovery, parsing, analysis, comparison, and rendering, and which layer may depend on which.
- [Guard policies](packages/guard-php/docs/policies.md): What Guard checks against `guard.yaml`, which findings fail the command, and which configuration values it can repair.
- [Guard command line](packages/guard-php/docs/cli.md): How to list the effective rules, check a project, record a baseline, repair configuration, and create the first `guard.yaml`.
- [Guard configuration](packages/guard-php/docs/configuration.md): How `guard.yaml` combines imported presets with project overrides, and which files a run is allowed to read.
- [Guard metrics](packages/guard-php/docs/metrics.md): How to cap the length and cyclomatic complexity of production PHP, and how to give some paths a different profile.
- [Guard structure](packages/guard-php/docs/structure.md): How to restrict the files and subdirectories each directory may contain, including their names and how many are allowed.
- [Guard documents](packages/guard-php/docs/documents.md): How to require a Markdown file to follow a declared outline, badge list, or exact text, and how undeclared files are reported.
- [Guard configuration fields](packages/guard-php/docs/fields.md): How to require a specific value in another tool's configuration file, and when `guard fix` can write that value.
- [Guard extensions](packages/guard-php/docs/extensions.md): How to add a custom check, and a parser for the files that check reads, to the same run as the built-in policies.
- [PHPStan rules](packages/phpstan-guard-rules/docs/phpstan.md): The toolkit PHPStan rules for patterns common in AI-written PHP, with each rule's identifier and how to turn it on or off.
- [PHPStan AI Formatter](packages/phpstan-ai-formatter/README.md): How to install the formatter that prints PHPStan findings for a coding agent, and what that output contains.
- [PHPUnit AI Reporter and Doctest](packages/phpunit-ai-reporter/README.md): How to install the reporter that prints PHPUnit failures for a coding agent, and how Doctest runs PHPDoc examples as tests.
