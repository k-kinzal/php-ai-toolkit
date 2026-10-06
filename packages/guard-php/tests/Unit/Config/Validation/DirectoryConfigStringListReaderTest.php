<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\DirectoryConfigStringListReader
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
#[CoversClass(DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DirectoryConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsListOfStrings(): void
    {
        self::assertSame(['src', 'tests'], (new DirectoryConfigStringListReader())->read(['paths' => ['src', 'tests']], 'paths', ['src'], ''));
    }

    public function testReadReturnsDefaultWhenAbsent(): void
    {
        self::assertSame(['src'], (new DirectoryConfigStringListReader())->read([], 'paths', ['src'], ''));
    }

    public function testReadRejectsNonListValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "paths" must be a list of strings.');

        (new DirectoryConfigStringListReader())->read(['paths' => 'src'], 'paths', ['src'], '');
    }

    public function testReadRejectsNonStringEntry(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].allow" must be a list of strings.');

        (new DirectoryConfigStringListReader())->read(['allow' => [1]], 'allow', [], 'rules[0]');
    }

    public function testReadRejectsEmptyStringEntry(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "exclude" must be a list of strings.');

        (new DirectoryConfigStringListReader())->read(['exclude' => ['']], 'exclude', [], '');
    }

    public function testReadOptionalReturnsNullWhenAbsent(): void
    {
        self::assertNull((new DirectoryConfigStringListReader())->readOptional([], 'allow', 'rules[0]'));
    }

    public function testReadOptionalReturnsEmptyList(): void
    {
        self::assertSame([], (new DirectoryConfigStringListReader())->readOptional(['allow' => []], 'allow', 'rules[0]'));
    }

    public function testReadOptionalReturnsListOfStrings(): void
    {
        self::assertSame(['*.php'], (new DirectoryConfigStringListReader())->readOptional(['allow' => ['*.php']], 'allow', 'rules[0]'));
    }

    public function testReadOptionalRejectsExplicitNull(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].allow" must be a list of strings.');

        (new DirectoryConfigStringListReader())->readOptional(['allow' => null], 'allow', 'rules[0]');
    }

    public function testLabelJoinsContextAndKey(): void
    {
        self::assertSame('rules[2].deny', (new DirectoryConfigStringListReader())->label('rules[2]', 'deny'));
        self::assertSame('exclude', (new DirectoryConfigStringListReader())->label('', 'exclude'));
    }
}
