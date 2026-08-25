<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Config\ConfigScalarReader;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(ConfigScalarReader::class)]
#[UsesClass(TreeGuardException::class)]
final class ConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsValue(): void
    {
        self::assertSame('json', (new ConfigScalarReader())->string(['reporter' => 'json'], 'reporter', 'ai', 'report'));
    }

    public function testStringReturnsDefaultWhenAbsent(): void
    {
        self::assertSame('ai', (new ConfigScalarReader())->string([], 'reporter', 'ai', 'report'));
    }

    public function testStringRejectsEmptyValue(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.reporter" must be a non-empty string.');

        (new ConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai', 'report');
    }

    public function testStringRejectsAbsentValueWithoutDefault(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].path" must be a non-empty string.');

        (new ConfigScalarReader())->string([], 'path', null, 'rules[0]');
    }

    public function testBoolReturnsValue(): void
    {
        self::assertTrue((new ConfigScalarReader())->bool(['forbid_empty' => true], 'forbid_empty', false, 'rules[0]'));
    }

    public function testBoolReturnsDefaultWhenAbsent(): void
    {
        self::assertFalse((new ConfigScalarReader())->bool([], 'forbid_empty', false, 'rules[0]'));
    }

    public function testBoolRejectsNonBooleanValue(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].forbid_empty" must be a boolean.');

        (new ConfigScalarReader())->bool(['forbid_empty' => 'yes'], 'forbid_empty', false, 'rules[0]');
    }

    public function testOptionalPositiveIntReturnsNullWhenAbsent(): void
    {
        self::assertNull((new ConfigScalarReader())->optionalPositiveInt([], 'max_files', 'rules[0]'));
    }

    public function testOptionalPositiveIntReturnsValue(): void
    {
        self::assertSame(25, (new ConfigScalarReader())->optionalPositiveInt(['max_files' => 25], 'max_files', 'rules[0]'));
    }

    public function testOptionalPositiveIntRejectsZero(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].max_files" must be a positive integer.');

        (new ConfigScalarReader())->optionalPositiveInt(['max_files' => 0], 'max_files', 'rules[0]');
    }

    public function testOptionalPositiveIntRejectsNonInteger(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].max_files" must be a positive integer.');

        (new ConfigScalarReader())->optionalPositiveInt(['max_files' => '25'], 'max_files', 'rules[0]');
    }

    public function testOptionalCaseReturnsNullWhenAbsent(): void
    {
        self::assertNull((new ConfigScalarReader())->optionalCase([], 'file_case', 'rules[0]'));
    }

    public function testOptionalCaseReturnsValue(): void
    {
        self::assertSame('pascal', (new ConfigScalarReader())->optionalCase(['file_case' => 'pascal'], 'file_case', 'rules[0]'));
    }

    public function testOptionalCaseRejectsUnknownConvention(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].file_case" must be one of: pascal, camel, snake, kebab.');

        (new ConfigScalarReader())->optionalCase(['file_case' => 'upper'], 'file_case', 'rules[0]');
    }

    public function testLabelJoinsContextAndKey(): void
    {
        self::assertSame('rules[0].path', (new ConfigScalarReader())->label('rules[0]', 'path'));
        self::assertSame('paths', (new ConfigScalarReader())->label('', 'paths'));
    }
}
