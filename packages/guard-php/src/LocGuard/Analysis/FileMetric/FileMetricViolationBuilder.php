<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis\FileMetric;

use function sprintf;

use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;

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
    public function violations(FileMetric $file, LimitConfig $limits, string $policy = 'standard'): array
    {
        $violations = [];
        if ($limits->maxFileLines !== null && $file->physicalLines > $limits->maxFileLines) {
            $violations[] = new Violation(
                $file->path,
                1,
                'file_lines',
                $file->physicalLines,
                $limits->maxFileLines,
                sprintf('File has %d physical lines; maximum is %d.', $file->physicalLines, $limits->maxFileLines),
                $policy,
            );
        }

        if ($limits->maxFileNcloc !== null && $file->nonCommentLines > $limits->maxFileNcloc) {
            $violations[] = new Violation(
                $file->path,
                1,
                'file_ncloc',
                $file->nonCommentLines,
                $limits->maxFileNcloc,
                sprintf('File has %d non-comment lines of code; maximum is %d.', $file->nonCommentLines, $limits->maxFileNcloc),
                $policy,
            );
        }

        return $violations;
    }
}
