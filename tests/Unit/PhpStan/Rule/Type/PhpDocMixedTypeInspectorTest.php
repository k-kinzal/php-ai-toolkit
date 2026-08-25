<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use PhpAiToolkit\PhpStan\Rule\Type\PhpDocMixedTypeInspector;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpDocMixedTypeInspector::class)]
#[UsesClass(RulePhpDocParser::class)]
final class PhpDocMixedTypeInspectorTest extends TestCase
{
    public function testContainsFindsMixedInsideArrayShapes(): void
    {
        $phpDoc = (new RulePhpDocParser())->parse('/** @phpstan-type Payload array{payload: mixed} */');
        $tag = $phpDoc->getTagsByName('@phpstan-type')[0];
        self::assertInstanceOf(TypeAliasTagValueNode::class, $tag->value);

        self::assertTrue((new PhpDocMixedTypeInspector())->contains($tag->value->type));
    }

    public function testObjectContainsRejectsSpecificTypes(): void
    {
        $phpDoc = (new RulePhpDocParser())->parse('/** @phpstan-type Payload array{payload: string} */');
        $tag = $phpDoc->getTagsByName('@phpstan-type')[0];
        self::assertInstanceOf(TypeAliasTagValueNode::class, $tag->value);

        self::assertFalse((new PhpDocMixedTypeInspector())->objectContains($tag->value->type));
    }
}
