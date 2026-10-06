<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Config\Tree\RuleListConfigReader;
use Guard\Config\Tree\StructureConfig;

/**
 * Reads directory policies while retaining every existing directory constraint.
 */
final class StructureReader
{
    /**
     * @param mixed $value
     */
    public function read($value, string $root): StructureConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['paths', 'exclude', 'directories'], 'structure');
        return new StructureConfig(
            $root,
            $schema->strings($data['paths'] ?? ['.'], 'structure.paths'),
            $schema->strings($data['exclude'] ?? [], 'structure.exclude'),
            (new RuleListConfigReader())->read($data['directories'] ?? []),
        );
    }
}
