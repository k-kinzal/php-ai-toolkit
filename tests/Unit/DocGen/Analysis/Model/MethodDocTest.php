<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(MethodDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(TypeSignature::class)]
final class MethodDocTest extends TestCase
{
    public function testStoresDeclarationData(): void
    {
        $parameter = new ParameterDoc('name', new TypeSignature('string', null), false, false, null, null, 'the widget name');
        $returnType = new TypeSignature('int', null);
        $docBlock = new DocBlock('Counts widgets.', '', [], null, null, [], [], [], [], [], [], null, false, '/** Counts widgets. */');

        $method = new MethodDoc('countWidgets', 'protected', true, false, true, [$parameter], $returnType, $docBlock, 12, 20);

        self::assertSame('countWidgets', $method->name);
        self::assertSame('protected', $method->visibility);
        self::assertTrue($method->isStatic);
        self::assertFalse($method->isAbstract);
        self::assertTrue($method->isFinal);
        self::assertSame([$parameter], $method->parameters);
        self::assertSame($returnType, $method->returnType);
        self::assertSame($docBlock, $method->docBlock);
        self::assertSame(12, $method->startLine);
        self::assertSame(20, $method->endLine);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $returnType = new TypeSignature(null, null);

        $method = new MethodDoc('render', 'public', false, true, false, [], $returnType, null, 5, 5);

        self::assertSame('render', $method->name);
        self::assertSame('public', $method->visibility);
        self::assertFalse($method->isStatic);
        self::assertTrue($method->isAbstract);
        self::assertFalse($method->isFinal);
        self::assertSame([], $method->parameters);
        self::assertSame($returnType, $method->returnType);
        self::assertNull($method->docBlock);
        self::assertSame(5, $method->startLine);
        self::assertSame(5, $method->endLine);
    }
}
