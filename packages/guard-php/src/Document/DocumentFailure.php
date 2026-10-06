<?php

declare(strict_types=1);

namespace Guard\Document;

use Guard\Policy\PolicyException;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;

/**
 * Adds the target path to parsing and policy errors with stable CLI messages.
 */
final class DocumentFailure
{
    /**
     * Returns the original exception category with document context.
     */
    public function at(string $path, RuntimeException|JsonException|NeonException $exception): RuntimeException|JsonException|NeonException
    {
        if ($exception instanceof JsonException) {
            return new JsonException('Invalid JSON value in ' . $path . '. Correct the document or repair value: ' . $exception->getMessage(), 0, $exception);
        }
        if ($exception instanceof NeonException) {
            return new NeonException('Invalid NEON value in ' . $path . '. Correct the document or repair value: ' . $exception->getMessage(), 0, $exception);
        }
        return new PolicyException('Cannot process ' . $path . ': ' . $exception->getMessage(), 0, $exception);
    }
}
