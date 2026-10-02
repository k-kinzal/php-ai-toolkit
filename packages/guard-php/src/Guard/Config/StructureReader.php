<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\RuleListConfigReader;
use Toolkit\TreeGuard\Config\TreeGuardConfig;

/**
 * Reads directory policies while retaining every existing TreeGuard constraint.
 */
final class StructureReader
{
    /**
     * @param mixed $value
     */
    public function read($value, string $root): TreeGuardConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['paths', 'exclude', 'directories'], 'structure');
        return new TreeGuardConfig(
            $root,
            $schema->strings($data['paths'] ?? ['.'], 'structure.paths'),
            $schema->strings($data['exclude'] ?? [], 'structure.exclude'),
            (new RuleListConfigReader())->read($data['directories'] ?? []),
            new ReportConfig('ai', ['path', 'rule']),
        );
    }
}
