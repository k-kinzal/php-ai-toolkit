<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Analysis;

use function array_merge;

use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;

/**
 * Applies every constraint of one rule block to one matched directory.
 */
final class DirectoryRuleInspector
{
    /** @readonly */
    private ChildCountInspector $childCountInspector;

    /** @readonly */
    private TotalFileCountInspector $totalFileCountInspector;

    /** @readonly */
    private DepthInspector $depthInspector;

    /** @readonly */
    private FileNameInspector $fileNameInspector;

    /** @readonly */
    private DirNameInspector $dirNameInspector;

    /** @readonly */
    private RequiredFileInspector $requiredFileInspector;

    /** @readonly */
    private EmptyDirectoryInspector $emptyDirectoryInspector;

    /**
     * Creates a rule inspector from the individual constraint inspectors.
     */
    public function __construct(
        ?ChildCountInspector $childCountInspector = null,
        ?TotalFileCountInspector $totalFileCountInspector = null,
        ?DepthInspector $depthInspector = null,
        ?FileNameInspector $fileNameInspector = null,
        ?DirNameInspector $dirNameInspector = null,
        ?RequiredFileInspector $requiredFileInspector = null,
        ?EmptyDirectoryInspector $emptyDirectoryInspector = null,
    ) {
        $this->childCountInspector = $childCountInspector ?? new ChildCountInspector();
        $this->totalFileCountInspector = $totalFileCountInspector ?? new TotalFileCountInspector();
        $this->depthInspector = $depthInspector ?? new DepthInspector();
        $this->fileNameInspector = $fileNameInspector ?? new FileNameInspector();
        $this->dirNameInspector = $dirNameInspector ?? new DirNameInspector();
        $this->requiredFileInspector = $requiredFileInspector ?? new RequiredFileInspector();
        $this->emptyDirectoryInspector = $emptyDirectoryInspector ?? new EmptyDirectoryInspector();
    }

    /**
     * Returns all violations of one rule block for one matched directory.
     *
     * @param array<string, DirectoryListing> $listings
     * @return list<Violation>
     */
    public function inspect(RuleConfig $rule, DirectoryListing $listing, array $listings): array
    {
        return array_merge(
            $this->childCountInspector->inspect($rule, $listing),
            $this->totalFileCountInspector->inspect($rule, $listing, $listings),
            $this->depthInspector->inspect($rule, $listing, $listings),
            $this->fileNameInspector->inspect($rule, $listing),
            $this->dirNameInspector->inspect($rule, $listing),
            $this->requiredFileInspector->inspect($rule, $listing),
            $this->emptyDirectoryInspector->inspect($rule, $listing),
        );
    }
}
