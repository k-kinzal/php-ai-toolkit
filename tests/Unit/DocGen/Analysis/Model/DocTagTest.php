<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\DocTag;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\DocTag
 */
#[CoversClass(DocTag::class)]
final class DocTagTest extends TestCase
{
    public function testStoresTagData(): void
    {
        $type = new IdentifierTypeNode('string');

        $tag = new DocTag($type, 'the widget name');

        self::assertSame($type, $tag->type);
        self::assertSame('the widget name', $tag->description);
    }

    public function testStoresAbsentTypeAsNull(): void
    {
        $tag = new DocTag(null, '');

        self::assertNull($tag->type);
        self::assertSame('', $tag->description);
    }
}
