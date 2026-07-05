<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorGutter;
use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorSourceReader;
use PhpAiToolkit\PhpStan\ErrorFormatter\HumanErrorPrinter;
use PHPStan\Analyser\Error;
use PHPStan\Command\Output;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HumanErrorPrinter::class)]
#[UsesClass(ErrorGutter::class)]
#[UsesClass(ErrorSourceReader::class)]
final class HumanErrorPrinterTest extends TestCase
{
    public function testWriteEmitsFormattedErrorLines(): void
    {
        $output = self::createMock(Output::class);
        $output->expects(self::atLeastOnce())->method('writeLineFormatted');
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';

        (new HumanErrorPrinter(new ErrorSourceReader(), new ErrorGutter()))->write(
            new Error('Property.', $file, 9, true, null, null, 'Remove it.', null, null, 'custom.a'),
            $file,
            2,
            $output,
        );
    }
}
