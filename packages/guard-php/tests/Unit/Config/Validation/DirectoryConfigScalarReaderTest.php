<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\DirectoryConfigScalarReader
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
#[CoversClass(DirectoryConfigScalarReader::class)]
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
final class DirectoryConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsValue(): void
    {
        self::assertSame('json', (new DirectoryConfigScalarReader())->string(['reporter' => 'json'], 'reporter', 'ai', 'report'));
    }

    public function testStringReturnsDefaultWhenAbsent(): void
    {
        self::assertSame('ai', (new DirectoryConfigScalarReader())->string([], 'reporter', 'ai', 'report'));
    }

    public function testStringRejectsEmptyValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.reporter" must be a non-empty string.');

        (new DirectoryConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai', 'report');
    }

    public function testStringRejectsAbsentValueWithoutDefault(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].path" must be a non-empty string.');

        (new DirectoryConfigScalarReader())->string([], 'path', null, 'rules[0]');
    }

    public function testBoolReturnsValue(): void
    {
        self::assertTrue((new DirectoryConfigScalarReader())->bool(['forbid_empty' => true], 'forbid_empty', false, 'rules[0]'));
    }

    public function testBoolReturnsDefaultWhenAbsent(): void
    {
        self::assertFalse((new DirectoryConfigScalarReader())->bool([], 'forbid_empty', false, 'rules[0]'));
    }

    public function testBoolRejectsNonBooleanValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].forbid_empty" must be a boolean.');

        (new DirectoryConfigScalarReader())->bool(['forbid_empty' => 'yes'], 'forbid_empty', false, 'rules[0]');
    }

    public function testOptionalPositiveIntReturnsNullWhenAbsent(): void
    {
        self::assertNull((new DirectoryConfigScalarReader())->optionalPositiveInt([], 'max_files', 'rules[0]'));
    }

    public function testOptionalPositiveIntReturnsValue(): void
    {
        self::assertSame(25, (new DirectoryConfigScalarReader())->optionalPositiveInt(['max_files' => 25], 'max_files', 'rules[0]'));
    }

    public function testOptionalPositiveIntRejectsZero(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].max_files" must be a positive integer.');

        (new DirectoryConfigScalarReader())->optionalPositiveInt(['max_files' => 0], 'max_files', 'rules[0]');
    }

    public function testOptionalPositiveIntRejectsNonInteger(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].max_files" must be a positive integer.');

        (new DirectoryConfigScalarReader())->optionalPositiveInt(['max_files' => '25'], 'max_files', 'rules[0]');
    }

    public function testOptionalCaseReturnsNullWhenAbsent(): void
    {
        self::assertNull((new DirectoryConfigScalarReader())->optionalCase([], 'file_case', 'rules[0]'));
    }

    public function testOptionalCaseReturnsValue(): void
    {
        self::assertSame('pascal', (new DirectoryConfigScalarReader())->optionalCase(['file_case' => 'pascal'], 'file_case', 'rules[0]'));
    }

    public function testOptionalCaseRejectsUnknownConvention(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].file_case" must be one of: pascal, camel, snake, kebab.');

        (new DirectoryConfigScalarReader())->optionalCase(['file_case' => 'upper'], 'file_case', 'rules[0]');
    }

    public function testLabelJoinsContextAndKey(): void
    {
        self::assertSame('rules[0].path', (new DirectoryConfigScalarReader())->label('rules[0]', 'path'));
        self::assertSame('paths', (new DirectoryConfigScalarReader())->label('', 'paths'));
    }
}
