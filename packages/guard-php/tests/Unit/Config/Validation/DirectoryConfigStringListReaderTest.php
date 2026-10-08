<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
