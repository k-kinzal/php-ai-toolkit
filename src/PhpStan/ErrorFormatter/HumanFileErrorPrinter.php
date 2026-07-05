<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

use function sprintf;

/**
 * Prints file-grouped PHPStan errors in the human formatter.
 */
final class HumanFileErrorPrinter
{
    /** @readonly */
    private HumanErrorPrinter $errorPrinter;

    /**
     * Creates a file error printer from path and error formatting collaborators.
     */
    public function __construct(
        /** @readonly */
        private RelativePathHelper $relativePathHelper,
        /** @readonly */
        private ErrorGutter $gutter,
        ?HumanErrorPrinter $errorPrinter = null,
    ) {
        $this->errorPrinter = $errorPrinter ?? new HumanErrorPrinter(new ErrorSourceReader(), $gutter);
    }

    /**
     * @param array<string, list<Error>> $fileErrors
     */
    public function write(array $fileErrors, Output $output): void
    {
        foreach ($fileErrors as $file => $errors) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted(sprintf(' <fg=cyan>%s</>', $this->relativePathHelper->getRelativePath($file)));
            $gutterWidth = $this->gutter->width($errors);

            foreach ($errors as $error) {
                $this->errorPrinter->write($error, $file, $gutterWidth, $output);
            }
        }
    }
}
