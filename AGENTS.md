<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->
# AGENTS

A comprehensive toolkit for AI-assisted PHP development. This package provides PHPStan rules, PHPUnit reporters, configuration templates, and AGENTS.md
templates — everything needed to set up and maintain a PHP project where AI agents write and modify code. AI-generated code tends to introduce subtle issues
that pass standard analysis; the tools in this toolkit catch those patterns early and enforce a higher quality bar.

## Supported Versions

- **PHP**: 8.0 or later
- **PHPStan**: ^1.12 || ^2.0
- **PHPUnit**: ^9.6 || ^10.5 || ^11 || ^12 || ^13

## Branching Strategy

This project is developed on `main` only. Commit directly to `main`, whatever the size of the change.

- Do not create branches. A branch cannot be merged back here, so work left on one is stranded.
- Do not open pull requests. They are not part of this project's workflow.
- The common agent habit of branching before committing on a default branch does not apply here: `main` is the working branch.

## Rule Design Principles

- AI-friendly error messages: Every error message must clearly state *what is wrong* and *how to fix it*. An AI agent reading the message should be able to resolve the violation without additional context.
- Specific and identifiable: Messages must include enough detail (e.g., the offending symbol name, the expected pattern) so that each violation can be individually targeted in `ignoreErrors` configurations. Vague messages like "invalid code" are not acceptable.

## Document References

- [DocGen](docs/docgen.md): DocGen documentation scope, caching, and generated site behavior
- [Doctest](docs/doctest.md): Running the examples written in PHPDoc blocks as PHPUnit tests, the assertion notation, and how the port differs from upstream
- [LocGuard](docs/loc-guard.md): LocGuard source metric limits and reporting
- [PHPStan AI Formatter](docs/phpstan-ai-formatter.md): The `ai` error formatter, its mode detection, and its output
- [PHPStan Rules](docs/phpstan-rules.md): Custom rules and their error identifiers
- [PHPUnit AI Reporter](docs/phpunit-ai-reporter.md): The failure reporter for PHPUnit 9.6 and 10.5 or later
- [TreeGuard](docs/tree-guard.md): TreeGuard directory and file structure constraints
