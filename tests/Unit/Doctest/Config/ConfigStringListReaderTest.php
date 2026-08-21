<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\ConfigStringListReader;
use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigStringListReader::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsTheListOrTheDefault(): void
    {
        $reader = new ConfigStringListReader();

        self::assertSame(['src', 'lib'], $reader->read(['paths' => ['src', 'lib']], 'paths', ['src'], ''));
        self::assertSame(['src'], $reader->read([], 'paths', ['src'], ''));
    }

    public function testReadRejectsAValueThatIsNotAListOfStrings(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Invalid doctest.yaml: "paths" must be a list of strings.');

        (new ConfigStringListReader())->read(['paths' => 'src'], 'paths', [], '');
    }

    public function testReadRejectsAnEmptyEntry(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Invalid doctest.yaml: "exclude" must be a list of strings.');

        (new ConfigStringListReader())->read(['exclude' => ['']], 'exclude', [], '');
    }

    public function testLabelQualifiesTheKeyWithItsSection(): void
    {
        $reader = new ConfigStringListReader();

        self::assertSame('paths', $reader->label('', 'paths'));
        self::assertSame('report.order_by', $reader->label('report', 'order_by'));
    }
}
