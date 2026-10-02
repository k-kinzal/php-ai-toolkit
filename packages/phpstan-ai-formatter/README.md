# phpstan-ai-formatter

AI-readable PHPStan error formatting.

Part of [php-ai-toolkit](https://github.com/k-kinzal/php-ai-toolkit). Use a Composer path repository pointing to `packages/*` in a checkout until package releases are published. For development versions, set `minimum-stability` to `dev` and `prefer-stable` to `true` in the consuming project, or explicitly require each local dependency at `dev-main`.

```sh
composer require --dev k-kinzal/phpstan-ai-formatter:dev-main
```

See the [guide](https://github.com/k-kinzal/php-ai-toolkit/blob/main/docs/phpstan-ai-formatter.md). Run the monorepo tests and strict lint checks from the repository root. Existing `Toolkit\` namespaces are retained.
