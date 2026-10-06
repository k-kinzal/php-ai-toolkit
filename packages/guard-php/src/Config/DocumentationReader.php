<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Config\Doc\DocumentationConfig;
use Guard\Config\Doc\DocumentListConfigReader;

/**
 * Reads declared heading structures with the existing Markdown validation.
 */
final class DocumentationReader
{
    /**
     * @param mixed $value
     */
    public function read($value, string $root, string $name): DocumentationConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['files', 'scan', 'exclude'], 'documentation');
        return new DocumentationConfig(
            $root,
            $name,
            (new DocumentListConfigReader())->read($data['files'] ?? null),
            $schema->strings($data['scan'] ?? [], 'documentation.scan'),
            $schema->strings($data['exclude'] ?? [], 'documentation.exclude'),
        );
    }
}
