<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\EnumCaseDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\PropertyDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Model\EnumCaseDoc
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Model\PropertyDoc
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(ClassLikeDoc::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(TypeSignature::class)]
final class ClassLikeDocTest extends TestCase
{
    public function testStoresDeclarationData(): void
    {
        $constant = new ConstantDoc('LIMIT', 'public', '10', null, 5);
        $property = new PropertyDoc('name', 'private', false, false, new TypeSignature('string', null), null, null, 8);
        $method = new MethodDoc('render', 'public', false, false, false, [], new TypeSignature('void', null), null, 12, 20);
        $enumCase = new EnumCaseDoc('Active', "'active'", null, 4);
        $docBlock = new DocBlock('One widget.', '', [], null, null, [], [], [], [], [], [], null, false, '/** One widget. */');

        $doc = new ClassLikeDoc('App\\Acme\\Widget', 'Widget', 'App\\Acme', 'enum', 'acme/widget', 'src/Widget.php', 3, 40, true, false, ['App\\Acme\\Base'], ['App\\Acme\\Renderable'], ['App\\Acme\\Loggable'], [$constant], [$property], [$method], [$enumCase], 'string', $docBlock, ['base' => 'App\\Acme\\Base'], true);

        self::assertSame('App\\Acme\\Widget', $doc->fqcn);
        self::assertSame('Widget', $doc->shortName);
        self::assertSame('App\\Acme', $doc->namespace);
        self::assertSame('enum', $doc->kind);
        self::assertSame('acme/widget', $doc->packageName);
        self::assertSame('src/Widget.php', $doc->file);
        self::assertSame(3, $doc->startLine);
        self::assertSame(40, $doc->endLine);
        self::assertTrue($doc->isAbstract);
        self::assertFalse($doc->isFinal);
        self::assertSame(['App\\Acme\\Base'], $doc->extends);
        self::assertSame(['App\\Acme\\Renderable'], $doc->implements);
        self::assertSame(['App\\Acme\\Loggable'], $doc->traits);
        self::assertSame([$constant], $doc->constants);
        self::assertSame([$property], $doc->properties);
        self::assertSame([$method], $doc->methods);
        self::assertSame([$enumCase], $doc->enumCases);
        self::assertSame('string', $doc->backingType);
        self::assertSame($docBlock, $doc->docBlock);
        self::assertSame(['base' => 'App\\Acme\\Base'], $doc->useMap);
        self::assertTrue($doc->isDev);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $doc = new ClassLikeDoc('App\\Acme\\Renderable', 'Renderable', 'App\\Acme', 'interface', 'acme/widget', 'src/Renderable.php', 1, 6, false, true, [], [], [], [], [], [], [], null, null, [], false);

        self::assertSame('App\\Acme\\Renderable', $doc->fqcn);
        self::assertSame('Renderable', $doc->shortName);
        self::assertSame('App\\Acme', $doc->namespace);
        self::assertSame('interface', $doc->kind);
        self::assertSame('acme/widget', $doc->packageName);
        self::assertSame('src/Renderable.php', $doc->file);
        self::assertSame(1, $doc->startLine);
        self::assertSame(6, $doc->endLine);
        self::assertFalse($doc->isAbstract);
        self::assertTrue($doc->isFinal);
        self::assertSame([], $doc->extends);
        self::assertSame([], $doc->implements);
        self::assertSame([], $doc->traits);
        self::assertSame([], $doc->constants);
        self::assertSame([], $doc->properties);
        self::assertSame([], $doc->methods);
        self::assertSame([], $doc->enumCases);
        self::assertNull($doc->backingType);
        self::assertNull($doc->docBlock);
        self::assertSame([], $doc->useMap);
        self::assertFalse($doc->isDev);
    }
}
