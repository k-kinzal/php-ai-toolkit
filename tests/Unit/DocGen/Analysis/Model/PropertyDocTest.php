<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PropertyDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(TypeSignature::class)]
final class PropertyDocTest extends TestCase
{
    public function testStoresDeclarationData(): void
    {
        $type = new TypeSignature('string', null);
        $docBlock = new DocBlock('The widget name.', '', [], null, null, [], [], [], [], [], [], null, false, '/** The widget name. */');

        $property = new PropertyDoc('name', 'protected', false, true, $type, "'unnamed'", $docBlock, 14);

        self::assertSame('name', $property->name);
        self::assertSame('protected', $property->visibility);
        self::assertFalse($property->isStatic);
        self::assertTrue($property->isPromoted);
        self::assertSame($type, $property->type);
        self::assertSame("'unnamed'", $property->defaultText);
        self::assertSame($docBlock, $property->docBlock);
        self::assertSame(14, $property->line);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $type = new TypeSignature(null, null);

        $property = new PropertyDoc('registry', 'private', true, false, $type, null, null, 20);

        self::assertSame('registry', $property->name);
        self::assertSame('private', $property->visibility);
        self::assertTrue($property->isStatic);
        self::assertFalse($property->isPromoted);
        self::assertSame($type, $property->type);
        self::assertNull($property->defaultText);
        self::assertNull($property->docBlock);
        self::assertSame(20, $property->line);
    }
}
