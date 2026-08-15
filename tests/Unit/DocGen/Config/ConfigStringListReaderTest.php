<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Config;

use PhpAiToolkit\DocGen\Config\ConfigStringListReader;
use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigStringListReader::class)]
#[UsesClass(DocGenException::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsDefaultWhenKeyIsAbsent(): void
    {
        self::assertSame(['.', 'packages/*'], (new ConfigStringListReader())->read([], 'packages', ['.', 'packages/*']));
    }

    public function testReadReturnsConfiguredList(): void
    {
        self::assertSame(['src', 'lib'], (new ConfigStringListReader())->read(['packages' => ['src', 'lib']], 'packages', []));
        self::assertSame([], (new ConfigStringListReader())->read(['vendor' => []], 'vendor', ['fallback']));
    }

    public function testReadRejectsMappingValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "packages" must be a list of strings.');

        (new ConfigStringListReader())->read(['packages' => ['name' => 'src']], 'packages', []);
    }

    public function testReadRejectsScalarValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "packages" must be a list of strings.');

        (new ConfigStringListReader())->read(['packages' => 'src'], 'packages', []);
    }

    public function testReadRejectsNonStringItem(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "packages" must contain only non-empty strings.');

        (new ConfigStringListReader())->read(['packages' => ['src', 1]], 'packages', []);
    }

    public function testReadRejectsEmptyStringItem(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid doc.yaml: "packages" must contain only non-empty strings.');

        (new ConfigStringListReader())->read(['packages' => ['']], 'packages', []);
    }
}
