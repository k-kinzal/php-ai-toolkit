<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocBlock
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(FunctionDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(TypeSignature::class)]
final class FunctionDocTest extends TestCase
{
    public function testStoresDeclarationData(): void
    {
        $parameter = new ParameterDoc('input', new TypeSignature('string', null), false, false, null, null, 'the raw input');
        $returnType = new TypeSignature('int', null);
        $docBlock = new DocBlock('Counts widgets.', '', [], null, null, [], [], [], [], [], [], null, false, '/** Counts widgets. */');

        $function = new FunctionDoc('App\\Acme\\count_widgets', 'count_widgets', 'App\\Acme', 'acme/widget', 'src/functions.php', 3, 9, [$parameter], $returnType, $docBlock, ['base' => 'App\\Acme\\Base'], true);

        self::assertSame('App\\Acme\\count_widgets', $function->fqn);
        self::assertSame('count_widgets', $function->shortName);
        self::assertSame('App\\Acme', $function->namespace);
        self::assertSame('acme/widget', $function->packageName);
        self::assertSame('src/functions.php', $function->file);
        self::assertSame(3, $function->startLine);
        self::assertSame(9, $function->endLine);
        self::assertSame([$parameter], $function->parameters);
        self::assertSame($returnType, $function->returnType);
        self::assertSame($docBlock, $function->docBlock);
        self::assertSame(['base' => 'App\\Acme\\Base'], $function->useMap);
        self::assertTrue($function->isDev);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $returnType = new TypeSignature(null, null);

        $function = new FunctionDoc('render', 'render', '', 'acme/widget', 'src/render.php', 1, 4, [], $returnType, null, [], false);

        self::assertSame('render', $function->fqn);
        self::assertSame('render', $function->shortName);
        self::assertSame('', $function->namespace);
        self::assertSame('acme/widget', $function->packageName);
        self::assertSame('src/render.php', $function->file);
        self::assertSame(1, $function->startLine);
        self::assertSame(4, $function->endLine);
        self::assertSame([], $function->parameters);
        self::assertSame($returnType, $function->returnType);
        self::assertNull($function->docBlock);
        self::assertSame([], $function->useMap);
        self::assertFalse($function->isDev);
    }
}
