<?php

declare(strict_types=1);

namespace Guard\Policy\Inspection;

use function array_merge;

use Guard\Input\DirectoryListing;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Diagnostic\DirectoryViolation;

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

    /** @readonly */
    private RequiredDirectoryInspector $requiredDirectoryInspector;

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
        ?RequiredDirectoryInspector $requiredDirectoryInspector = null,
    ) {
        $this->childCountInspector = $childCountInspector ?? new ChildCountInspector();
        $this->totalFileCountInspector = $totalFileCountInspector ?? new TotalFileCountInspector();
        $this->depthInspector = $depthInspector ?? new DepthInspector();
        $this->fileNameInspector = $fileNameInspector ?? new FileNameInspector();
        $this->dirNameInspector = $dirNameInspector ?? new DirNameInspector();
        $this->requiredFileInspector = $requiredFileInspector ?? new RequiredFileInspector();
        $this->emptyDirectoryInspector = $emptyDirectoryInspector ?? new EmptyDirectoryInspector();
        $this->requiredDirectoryInspector = $requiredDirectoryInspector ?? new RequiredDirectoryInspector();
    }

    /**
     * Returns all violations of one rule block for one matched directory.
     *
     * @param array<string, DirectoryListing> $listings
     * @return list<DirectoryViolation>
     */
    public function inspect(DirectoryRuleConfig $rule, DirectoryListing $listing, array $listings): array
    {
        if (!$this->matchesCondition($rule, $listing)) {
            return [];
        }

        return array_merge(
            $this->childCountInspector->inspect($rule, $listing),
            $this->totalFileCountInspector->inspect($rule, $listing, $listings),
            $this->depthInspector->inspect($rule, $listing, $listings),
            $this->fileNameInspector->inspect($rule, $listing),
            $this->dirNameInspector->inspect($rule, $listing),
            $this->requiredFileInspector->inspect($rule, $listing),
            $this->emptyDirectoryInspector->inspect($rule, $listing),
            $this->requiredDirectoryInspector->inspect($rule, $listing),
        );
    }

    /**
     * Activates a conditional rule when any direct file matches any configured glob.
     */
    public function matchesCondition(DirectoryRuleConfig $rule, DirectoryListing $listing): bool
    {
        if ($rule->whenFiles === null) {
            return true;
        }
        foreach ($listing->fileNames as $name) {
            if ($this->fileNameInspector->matchesAny($rule->whenFiles, $name)) {
                return true;
            }
        }

        return false;
    }
}
