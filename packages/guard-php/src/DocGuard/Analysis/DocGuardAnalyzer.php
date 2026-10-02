<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Analysis;

use function array_filter;
use function array_merge;
use function array_values;
use function count;
use function is_file;

use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileReader;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingParser;

/**
 * Checks every declared document against its declared heading structure.
 */
final class DocGuardAnalyzer
{
    /** @readonly */
    private DocGuardPathResolver $pathResolver;

    /** @readonly */
    private MarkdownFileReader $reader;

    /** @readonly */
    private HeadingParser $parser;

    /** @readonly */
    private HeadingStructureComparator $comparator;

    /** @readonly */
    private UndeclaredDocumentInspector $undeclaredInspector;

    /** @readonly */
    private ViolationFactory $violationFactory;

    /**
     * Creates an analyzer with injectable reading, parsing, and comparison.
     */
    public function __construct(
        ?DocGuardPathResolver $pathResolver = null,
        ?MarkdownFileReader $reader = null,
        ?HeadingParser $parser = null,
        ?HeadingStructureComparator $comparator = null,
        ?UndeclaredDocumentInspector $undeclaredInspector = null,
        ?ViolationFactory $violationFactory = null,
    ) {
        $this->pathResolver = $pathResolver ?? new DocGuardPathResolver();
        $this->reader = $reader ?? new MarkdownFileReader();
        $this->parser = $parser ?? new HeadingParser();
        $this->comparator = $comparator ?? new HeadingStructureComparator();
        $this->undeclaredInspector = $undeclaredInspector ?? new UndeclaredDocumentInspector();
        $this->violationFactory = $violationFactory ?? new ViolationFactory();
    }

    /**
     * Analyzes the declared documents and the scan patterns.
     *
     * @throws DocGuardException when a declared document exists but cannot be read
     */
    public function analyze(DocGuardConfig $config): AnalysisResult
    {
        $violations = [];
        $headingCount = 0;

        foreach ($config->documents as $document) {
            $path = $this->pathResolver->resolve($config->root, $document->path);
            if (!is_file($path)) {
                $violations[] = $this->violationFactory->missingDocument($document->path, $config->configName);
                continue;
            }

            $maxLevel = $document->maxLevel;
            $headings = array_values(array_filter(
                $this->parser->parse($this->reader->read($path)),
                static fn (Heading $heading): bool => $heading->level <= $maxLevel,
            ));
            $headingCount += count($headings);
            $violations = array_merge($violations, $this->comparator->compare($document, $headings, $config->configName));
        }

        $violations = array_merge($violations, $this->undeclaredInspector->inspect($config));

        return new AnalysisResult(count($config->documents), $headingCount, $violations);
    }
}
