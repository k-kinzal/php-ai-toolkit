<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture;

use function file;
use function is_array;
use function is_file;
use function is_readable;

use PHPStan\Rules\IdentifierRuleError;

use function sprintf;
use function stripos;
use function strtolower;

use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;

/**
 * Finds forbidden literal terms in every kind of source text in restricted files.
 */
final class ForbiddenFileTermInspector
{
    /** @readonly */
    private ForbiddenFileTermErrorBuilder $errorBuilder;

    /** @readonly */
    private LineOrderedErrors $lineOrderedErrors;

    /**
     * Creates an inspector from restrictions and error reporting services.
     */
    public function __construct(
        private ForbiddenFileTermRestrictions $restrictions,
        ?ForbiddenFileTermErrorBuilder $errorBuilder = null,
        ?LineOrderedErrors $lineOrderedErrors = null,
    ) {
        $this->errorBuilder = $errorBuilder ?? new ForbiddenFileTermErrorBuilder();
        $this->lineOrderedErrors = $lineOrderedErrors ?? new LineOrderedErrors();
    }

    /**
     * Returns one error per forbidden term and source line.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(string $filePath): array
    {
        $restrictions = $this->restrictions->matching($filePath);
        if ($restrictions === []) {
            return [];
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $errors = [];
        $reported = [];
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            foreach ($restrictions as $restriction) {
                foreach ($restriction['terms'] as $term) {
                    $reportKey = sprintf('%d:%s', $lineNumber, strtolower($term));
                    if (isset($reported[$reportKey]) || stripos($line, $term) === false) {
                        continue;
                    }

                    $reported[$reportKey] = true;
                    $errors[] = $this->errorBuilder->build($term, $restriction['path'], $lineNumber);
                }
            }
        }

        return $this->lineOrderedErrors->sorted($errors);
    }
}
