<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Config;

use PhpAiToolkit\DocGen\Config\ConfigScalarReader;
use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigScalarReader::class)]
#[UsesClass(DocGenException::class)]
final class ConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsDefaultWhenKeyIsAbsent(): void
    {
        self::assertSame('build/docs', (new ConfigScalarReader())->string([], 'output', 'build/docs'));
    }

    public function testStringReturnsConfiguredValue(): void
    {
        self::assertSame('public/docs', (new ConfigScalarReader())->string(['output' => 'public/docs'], 'output', 'build/docs'));
    }

    public function testStringRejectsEmptyString(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "output" must be a non-empty string.');

        (new ConfigScalarReader())->string(['output' => ''], 'output', 'build/docs');
    }

    public function testStringRejectsNonStringValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "output" must be a non-empty string.');

        (new ConfigScalarReader())->string(['output' => 5], 'output', 'build/docs');
    }

    public function testOptionalStringReturnsNullWhenKeyIsAbsent(): void
    {
        self::assertNull((new ConfigScalarReader())->optionalString([], 'title'));
    }

    public function testOptionalStringReturnsConfiguredValue(): void
    {
        self::assertSame('My Project', (new ConfigScalarReader())->optionalString(['title' => 'My Project'], 'title'));
    }

    public function testOptionalStringRejectsEmptyString(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "title" must be a non-empty string.');

        (new ConfigScalarReader())->optionalString(['title' => ''], 'title');
    }

    public function testOptionalStringRejectsNonStringValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "title" must be a non-empty string.');

        (new ConfigScalarReader())->optionalString(['title' => ['x']], 'title');
    }
}
