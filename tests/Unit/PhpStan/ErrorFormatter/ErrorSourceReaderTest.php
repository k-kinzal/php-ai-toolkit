<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use function dirname;

use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorSourceReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorSourceReader::class)]
final class ErrorSourceReaderTest extends TestCase
{
    public function testReadReturnsRequestedSourceLine(): void
    {
        $reader = new ErrorSourceReader();
        $file = dirname(__DIR__, 3) . '/Fixture/ErrorFormatter/SampleSource.php';

        self::assertSame('    private string $name;', $reader->read($file, 9));
    }

    public function testReadReturnsNullForMissingFile(): void
    {
        $reader = new ErrorSourceReader();

        self::assertNull($reader->read('/missing.php', 1));
    }
}
