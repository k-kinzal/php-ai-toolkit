<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function ltrim;
use function max;

use PHPStan\Analyser\Error;
use PHPStan\Command\Output;

use function rtrim;
use function sprintf;
use function str_repeat;
use function strlen;

/**
 * Prints one PHPStan error in the human formatter.
 */
final class HumanErrorPrinter
{
    /**
     * Creates a single-error printer from source and gutter formatters.
     */
    public function __construct(
        /** @readonly */
        private ErrorSourceReader $sourceReader,
        /** @readonly */
        private ErrorGutter $gutter,
    ) {
    }

    /**
     * Writes one formatted PHPStan error.
     */
    public function write(Error $error, string $file, int $gutterWidth, Output $output): void
    {
        $line = $error->getLine();
        $codeLine = $line !== null ? $this->sourceReader->read($error->getTraitFilePath() ?? $file, $line) : null;
        $output->writeLineFormatted('');
        if ($codeLine !== null) {
            $trimmedCode = ltrim($codeLine);
            $leadingSpaces = strlen($codeLine) - strlen($trimmedCode);
            $caretLength = max(1, strlen(rtrim($trimmedCode)));
            $output->writeLineFormatted(sprintf('  <fg=blue>%s</> | %s', $this->gutter->line($line !== null ? (string) $line : '?', $gutterWidth), $codeLine));
            $output->writeLineFormatted(sprintf('  %s | <fg=red>%s</>', str_repeat(' ', $gutterWidth), str_repeat(' ', $leadingSpaces) . str_repeat('^', $caretLength)));
        }

        $identifier = $error->getIdentifier();
        $output->writeLineFormatted(sprintf('  %s%s', $identifier !== null ? sprintf('<fg=magenta>%s</>: ', $identifier) : '', $error->getMessage()));
        if ($error->getTip() !== null) {
            $output->writeLineFormatted(sprintf('  <fg=yellow>Tip:</> %s', $error->getTip()));
        }
    }
}
