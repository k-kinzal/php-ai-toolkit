<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Symbol;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Symbol\DocTag;
use Toolkit\DocGen\Analysis\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Analysis\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Symbol\DocTag
 */
#[CoversClass(TypeSignature::class)]
#[UsesClass(DocTag::class)]
final class TypeSignatureTest extends TestCase
{
    public function testStoresTypeData(): void
    {
        $annotated = new DocTag(new IdentifierTypeNode('string'), 'the widget name');

        $signature = new TypeSignature('string', $annotated);

        self::assertSame('string', $signature->native);
        self::assertSame($annotated, $signature->annotated);
    }

    public function testStoresAbsentTypesAsNull(): void
    {
        $signature = new TypeSignature(null, null);

        self::assertNull($signature->native);
        self::assertNull($signature->annotated);
    }
}
