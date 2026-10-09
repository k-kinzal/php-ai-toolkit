<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Symbol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Symbol\DocBlock;
use Toolkit\DocGen\Model\Symbol\PropertyDoc;
use Toolkit\DocGen\Model\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Model\Symbol\PropertyDoc
 * @uses \Toolkit\DocGen\Model\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Model\Symbol\TypeSignature
 */
#[CoversClass(PropertyDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(\Toolkit\DocGen\Model\Mutation\MutationContract::class)]
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
