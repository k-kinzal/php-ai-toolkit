<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Diagnostic\LintException;
use JsonException;

/**
 * Loads the JSON schemas the package ships for decisions.yaml and rule files.
 */
final class SchemaCatalog
{
    /**
     * The schema of decisions.yaml.
     */
    public const DECISIONS = 'decisions';

    /**
     * The schema of a rule file.
     */
    public const RULES = 'rules';

    /**
     * Creates a catalog reading from a schema directory, the shipped one by default.
     */
    public function __construct(private ?string $directory = null)
    {
    }

    /**
     * Returns the absolute path of a schema file.
     */
    public function path(string $name): string
    {
        return ($this->directory ?? dirname(__DIR__, 2) . '/schema') . '/' . $name . '.schema.json';
    }

    /**
     * Returns a decoded schema by name, such as decisions or rules.
     *
     * @return array<string, mixed>
     * @throws LintException when the schema file is missing or malformed
     */
    public function schema(string $name): array
    {
        $path = $this->path($name);
        $json = is_file($path) ? file_get_contents($path) : false;
        if ($json === false) {
            throw new LintException(sprintf('Schema file not found: %s. Reinstall decisions-lint so its schema directory is complete.', $path));
        }
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LintException(sprintf('Schema file %s is not valid JSON: %s. Reinstall decisions-lint.', $path, $exception->getMessage()), 0, $exception);
        }
        if (!is_array($decoded)) {
            throw new LintException(sprintf('Schema file %s must contain a JSON object. Reinstall decisions-lint.', $path));
        }
        $schema = [];
        foreach ($decoded as $key => $value) {
            $schema[(string) $key] = $value;
        }

        return $schema;
    }
}
