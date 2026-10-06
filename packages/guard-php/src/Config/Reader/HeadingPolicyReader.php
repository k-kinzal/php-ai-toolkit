<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Config\Schema;
use Guard\Config\Value\DocumentationConfig;

/**
 * Reads declared heading structures with the existing Markdown validation.
 */
final class HeadingPolicyReader
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
