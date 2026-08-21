<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\ConfigScalarReader;
use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigScalarReader::class)]
final class ConfigScalarReaderTest extends TestCase
{
    public function testStringReadsAValueAndFallsBackToTheDefault(): void
    {
        $reader = new ConfigScalarReader();

        self::assertSame('ai', $reader->string(['reporter' => 'ai'], 'reporter', 'text', 'report'));
        self::assertSame('text', $reader->string([], 'reporter', 'text', 'report'));
    }

    public function testStringRejectsAnEmptyOrNonStringValue(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Invalid doctest.yaml: "report.reporter" must be a non-empty string.');

        (new ConfigScalarReader())->string(['reporter' => ''], 'reporter', null, 'report');
    }

    public function testOptionalStringReturnsNullWhenTheKeyIsAbsent(): void
    {
        $reader = new ConfigScalarReader();

        self::assertNull($reader->optionalString([], 'bootstrap', ''));
        self::assertSame('boot.php', $reader->optionalString(['bootstrap' => 'boot.php'], 'bootstrap', ''));
    }

    public function testLabelQualifiesTheKeyWithItsSection(): void
    {
        $reader = new ConfigScalarReader();

        self::assertSame('paths', $reader->label('', 'paths'));
        self::assertSame('report.reporter', $reader->label('report', 'reporter'));
    }
}
