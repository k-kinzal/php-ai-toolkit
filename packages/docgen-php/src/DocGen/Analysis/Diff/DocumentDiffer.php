<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use function file_get_contents;
use function is_file;
use function str_replace;

use Toolkit\DocGen\Analysis\Model\MarkdownDoc;
use Toolkit\DocGen\Analysis\ProjectModel;

/**
 * Merges the Markdown documents of two revisions.
 *
 * Prose is documentation as much as a signature is, so a document the head
 * revision dropped keeps its page and is shown as removed.
 */
final class DocumentDiffer
{
    /**
     * Merges the documents of both revisions and records their states.
     *
     * @return list<MarkdownDoc>
     */
    public function merge(ProjectModel $base, ProjectModel $head, DiffIndex $index): array
    {
        $baseDocuments = [];
        foreach ($base->documents as $document) {
            $baseDocuments[$index->keys()->document($document->packageName, $document->path)] = $document;
        }

        $documents = [];
        $seen = [];
        foreach ($head->documents as $document) {
            $key = $index->keys()->document($document->packageName, $document->path);
            $seen[$key] = true;
            $counterpart = $baseDocuments[$key] ?? null;
            $index->mark($key, $this->statusOf($counterpart, $document, $head->root, $index));
            $documents[] = $document;
        }

        foreach ($baseDocuments as $key => $document) {
            if (!isset($seen[$key])) {
                $index->mark($key, DiffStatus::REMOVED);
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * Compares one document against the base revision of the same path.
     *
     * @param ?MarkdownDoc $counterpart the document as the base revision had it
     * @param string $headRoot the root the head revision was read from
     */
    public function statusOf(?MarkdownDoc $counterpart, MarkdownDoc $document, string $headRoot, DiffIndex $index): string
    {
        if ($counterpart === null) {
            return DiffStatus::ADDED;
        }

        $baseText = $index->baseSource($counterpart->file);
        $headText = $this->contents($headRoot . '/' . $document->file);
        if ($baseText === null || $headText === null) {
            return DiffStatus::MODIFIED;
        }

        return $this->normalized($baseText) === $this->normalized($headText) ? DiffStatus::SAME : DiffStatus::MODIFIED;
    }

    /**
     * Reads one file, or returns null when it cannot be read.
     */
    public function contents(string $path): ?string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false ? null : $contents;
    }

    /**
     * Normalizes the line endings of a document before comparing it.
     */
    public function normalized(string $text): string
    {
        return str_replace("\r\n", "\n", $text);
    }
}
