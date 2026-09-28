<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Analysis;

use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;

/**
 * Reports documents matched by the scan patterns that doc-guard.yaml does not declare.
 */
final class UndeclaredDocumentInspector
{
    /** @readonly */
    private MarkdownFileFinder $finder;

    /** @readonly */
    private ViolationFactory $violationFactory;

    /**
     * Creates an inspector from file discovery and violation construction.
     */
    public function __construct(?MarkdownFileFinder $finder = null, ?ViolationFactory $violationFactory = null)
    {
        $this->finder = $finder ?? new MarkdownFileFinder();
        $this->violationFactory = $violationFactory ?? new ViolationFactory();
    }

    /**
     * Returns one violation per undeclared document, naming the first pattern that matched it.
     *
     * @return list<Violation>
     */
    public function inspect(DocGuardConfig $config): array
    {
        $reported = [];
        foreach ($config->documents as $document) {
            $reported[$document->path] = true;
        }

        $violations = [];
        foreach ($config->scan as $pattern) {
            foreach ($this->finder->find($config->root, [$pattern]) as $path) {
                if (!isset($reported[$path])) {
                    $reported[$path] = true;
                    $violations[] = $this->violationFactory->undeclaredDocument($path, $pattern, $config->configName);
                }
            }
        }

        return $violations;
    }
}
