<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Config\LimitConfig;

use function sprintf;

/**
 * Builds LocGuard violations for whole-file metrics.
 */
final class FileMetricViolationBuilder
{
    /**
     * Returns file-level threshold violations.
     *
     * @return list<Violation>
     */
    public function violations(FileMetric $file, LimitConfig $limits): array
    {
        $violations = [];
        if ($file->physicalLines > $limits->maxFileLines) {
            $violations[] = new Violation(
                $file->path,
                1,
                'file_lines',
                $file->physicalLines,
                $limits->maxFileLines,
                sprintf('File has %d physical lines; maximum is %d.', $file->physicalLines, $limits->maxFileLines),
            );
        }

        if ($file->nonCommentLines > $limits->maxFileNcloc) {
            $violations[] = new Violation(
                $file->path,
                1,
                'file_ncloc',
                $file->nonCommentLines,
                $limits->maxFileNcloc,
                sprintf('File has %d non-comment lines of code; maximum is %d.', $file->nonCommentLines, $limits->maxFileNcloc),
            );
        }

        return $violations;
    }
}
