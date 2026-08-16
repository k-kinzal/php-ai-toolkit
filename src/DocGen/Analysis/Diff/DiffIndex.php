<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

use function file_get_contents;
use function is_file;

/**
 * The recorded diff state of every documented element of one site.
 *
 * The index also keeps the base checkout around, so a page can show the
 * lines a file lost without the analysis having to hold every source of
 * both revisions in memory.
 */
final class DiffIndex
{
    /** @var array<string, string> */
    private array $statuses = [];

    /** @readonly */
    private string $baseLabel;

    /** @readonly */
    private string $headLabel;

    /** @readonly */
    private ?string $baseRoot;

    /** @readonly */
    private DiffKey $keys;

    /**
     * Creates an empty index for one pair of revisions.
     *
     * @param string $baseLabel the label of the compared base revision
     * @param string $headLabel the label of the compared head revision
     * @param ?string $baseRoot the checkout the base revision was read from
     */
    public function __construct(string $baseLabel, string $headLabel, ?string $baseRoot = null, ?DiffKey $keys = null)
    {
        $this->baseLabel = $baseLabel;
        $this->headLabel = $headLabel;
        $this->baseRoot = $baseRoot;
        $this->keys = $keys ?? new DiffKey();
    }

    /**
     * Records the state of one element.
     */
    public function mark(string $key, string $status): void
    {
        $this->statuses[$key] = $status;
    }

    /**
     * Returns the recorded state of one element.
     *
     * An element nobody recorded is unchanged: only the elements a revision
     * touched are marked, so the absence of a mark is the answer.
     */
    public function status(string $key): string
    {
        return $this->statuses[$key] ?? DiffStatus::SAME;
    }

    /**
     * Returns the key builder shared with the renderers.
     */
    public function keys(): DiffKey
    {
        return $this->keys;
    }

    /**
     * Returns the label of the compared base revision.
     */
    public function baseLabel(): string
    {
        return $this->baseLabel;
    }

    /**
     * Returns the label of the compared head revision.
     */
    public function headLabel(): string
    {
        return $this->headLabel;
    }

    /**
     * Digests the comparison itself, without where it was read from.
     *
     * The checkout of the base revision is a scratch directory of one run
     * and says nothing about what is being compared, so two runs comparing
     * the same revisions digest the same. What the base revision held is
     * not part of this: a page that shows base sources digests them where
     * it shows them.
     */
    public function digest(): string
    {
        $statuses = $this->statuses;
        ksort($statuses);

        return hash('sha256', $this->baseLabel . "\0" . $this->headLabel . "\0" . serialize($statuses));
    }

    /**
     * Returns the checkout directory of the base revision.
     */
    public function baseRoot(): ?string
    {
        return $this->baseRoot;
    }

    /**
     * Reads one project-relative file as it was in the base revision.
     */
    public function baseSource(string $relativeFile): ?string
    {
        if ($this->baseRoot === null) {
            return null;
        }

        $path = $this->baseRoot . '/' . $relativeFile;
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false ? null : $contents;
    }
}
