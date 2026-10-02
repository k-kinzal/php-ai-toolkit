<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentListConfigReader;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * Reads declared heading structures with the existing Markdown validation.
 */
final class DocumentationReader
{
    /**
     * @param mixed $value
     */
    public function read($value, string $root, string $name): DocGuardConfig
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['files', 'scan', 'exclude'], 'documentation');
        return new DocGuardConfig(
            $root,
            $name,
            (new DocumentListConfigReader())->read($data['files'] ?? null),
            $schema->strings($data['scan'] ?? [], 'documentation.scan'),
            new ReportConfig('ai', ['path', 'line', 'rule']),
            $schema->strings($data['exclude'] ?? [], 'documentation.exclude'),
        );
    }
}
