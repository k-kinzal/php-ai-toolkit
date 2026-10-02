<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\OutputStyle;
use PHPStan\File\RelativePathHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\ErrorFormatter\RecordingOutput;
use Toolkit\PhpStan\ErrorFormatter\ErrorGutter;
use Toolkit\PhpStan\ErrorFormatter\ErrorSourceReader;
use Toolkit\PhpStan\ErrorFormatter\HumanErrorPrinter;
use Toolkit\PhpStan\ErrorFormatter\HumanFileErrorPrinter;

/**
 * @covers \Toolkit\PhpStan\ErrorFormatter\HumanFileErrorPrinter
 * @uses \Toolkit\PhpStan\ErrorFormatter\ErrorGutter
 * @uses \Toolkit\PhpStan\ErrorFormatter\ErrorSourceReader
 * @uses \Toolkit\PhpStan\ErrorFormatter\HumanErrorPrinter
 */
#[CoversClass(HumanFileErrorPrinter::class)]
#[UsesClass(ErrorGutter::class)]
#[UsesClass(ErrorSourceReader::class)]
#[UsesClass(HumanErrorPrinter::class)]
final class HumanFileErrorPrinterTest extends TestCase
{
    public function testWriteEmitsFileHeaderAndErrors(): void
    {
        $relativePathHelper = self::createStub(RelativePathHelper::class);
        $relativePathHelper->method('getRelativePath')->willReturn('SampleSource.php');
        $output = new RecordingOutput(self::createStub(OutputStyle::class));
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';

        (new HumanFileErrorPrinter($relativePathHelper, new ErrorGutter()))->write([
            $file => [new Error('Property.', $file, 9, true, null, null, null, null, null, 'custom.a')],
        ], $output);

        self::assertNotSame([], $output->formattedLines());
    }
}
