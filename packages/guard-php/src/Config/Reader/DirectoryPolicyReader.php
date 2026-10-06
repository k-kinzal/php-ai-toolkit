<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Config\Schema;
use Guard\Config\Value\StructureConfig;

/**
 * Reads directory policies while retaining every existing directory constraint.
 */
final class DirectoryPolicyReader
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
            (new DirectoryRuleListConfigReader())->read($data['directories'] ?? []),
        );
    }
}
