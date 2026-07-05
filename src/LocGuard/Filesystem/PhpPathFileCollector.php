<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Filesystem;

use function is_dir;
use function is_file;

use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\LocGuardException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function sprintf;

/**
 * Collects PHP files from one configured absolute path.
 */
final class PhpPathFileCollector
{
    /**
     * Creates a collector from inclusion and path resolution policies.
     */
    public function __construct(
        private readonly PhpFileInclusionPolicy $inclusionPolicy = new PhpFileInclusionPolicy(),
        private readonly LocGuardPathResolver $pathResolver = new LocGuardPathResolver(),
    ) {
    }

    /**
     * Returns PHP files under the configured path.
     *
     * @return array<string, string>
     */
    public function files(LocGuardConfig $config, string $path): array
    {
        if (is_file($path)) {
            return $this->inclusionPolicy->includes($config, $path) ? [$path => $this->pathResolver->relative($config->root, $path)] : [];
        }

        if (!is_dir($path)) {
            throw new LocGuardException(sprintf('Configured path does not exist: %s', $path));
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $file = $item->getPathname();
            if ($this->inclusionPolicy->includes($config, $file)) {
                $files[$file] = $this->pathResolver->relative($config->root, $file);
            }
        }

        return $files;
    }
}
