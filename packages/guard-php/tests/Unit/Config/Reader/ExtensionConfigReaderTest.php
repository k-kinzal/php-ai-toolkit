<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ExtensionConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class ExtensionConfigReaderTest extends TestCase
{
    public function testReadRetainsClassOrderAndExtensionOwnedOptions(): void
    {
        $options = ['App\\SchemaExtension' => ['schema' => 'schema.xsd', 'files' => ['**/*.xml']], 'App\\OtherExtension' => []];
        self::assertSame($options, (new ExtensionConfigReader())->read($options));
    }

    public function testOptionsAcceptsAnEmptyMapping(): void
    {
        self::assertSame([], (new ExtensionConfigReader())->options([], 'App\\Extension'));
    }

    /**
     * @dataProvider providerMalformedMappings
     */
    #[DataProvider('providerMalformedMappings')]
    public function testReadRejectsMalformedExtensionDeclarations(mixed $value): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('guard.yaml.extensions');
        (new ExtensionConfigReader())->read($value);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function providerMalformedMappings(): iterable
    {
        yield 'null' => [null];
        yield 'string' => ['App\\Extension'];
        yield 'list' => [['App\\Extension']];
        yield 'empty class' => [['' => []]];
        yield 'padded class' => [[' App\\Extension' => []]];
        yield 'null options' => [['App\\Extension' => null]];
        yield 'string options' => [['App\\Extension' => 'file.xml']];
        yield 'list options' => [['App\\Extension' => ['file.xml']]];
        yield 'empty option name' => [['App\\Extension' => ['' => true]]];
    }
}
