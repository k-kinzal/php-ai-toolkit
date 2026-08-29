<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\TemplateDoc;
use Toolkit\DocGen\Analysis\Model\TypeAliasDoc;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Model\DocTag
 * @uses \Toolkit\DocGen\Analysis\Model\TemplateDoc
 * @uses \Toolkit\DocGen\Analysis\Model\TypeAliasDoc
 */
#[CoversClass(DocBlock::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(TemplateDoc::class)]
#[UsesClass(TypeAliasDoc::class)]
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
final class DocBlockTest extends TestCase
{
    public function testStoresDocumentationData(): void
    {
        $param = new DocTag(new IdentifierTypeNode('string'), 'the widget name');
        $return = new DocTag(new IdentifierTypeNode('int'), 'the widget count');
        $var = new DocTag(new IdentifierTypeNode('bool'), 'the enabled flag');
        $throw = new DocTag(new IdentifierTypeNode('RuntimeException'), 'on failure');
        $template = new TemplateDoc('T', new IdentifierTypeNode('object'), 'the subject type');
        $alias = new TypeAliasDoc('Row', new IdentifierTypeNode('array'), null);
        $extendsTag = new DocTag(new IdentifierTypeNode('Base'), '');
        $implementsTag = new DocTag(new IdentifierTypeNode('Renderable'), '');
        $usesTag = new DocTag(new IdentifierTypeNode('Loggable'), '');

        $docBlock = new DocBlock('Summary line.', 'Longer description.', ['name' => $param], $return, $var, [$throw], [$template], [$alias], [$extendsTag], [$implementsTag], [$usesTag], 'use NewWidget instead', true, '/** Summary line. */');

        self::assertSame('Summary line.', $docBlock->summary);
        self::assertSame('Longer description.', $docBlock->description);
        self::assertSame(['name' => $param], $docBlock->params);
        self::assertSame($return, $docBlock->return);
        self::assertSame($var, $docBlock->var);
        self::assertSame([$throw], $docBlock->throws);
        self::assertSame([$template], $docBlock->templates);
        self::assertSame([$alias], $docBlock->aliases);
        self::assertSame([$extendsTag], $docBlock->extendsTags);
        self::assertSame([$implementsTag], $docBlock->implementsTags);
        self::assertSame([$usesTag], $docBlock->usesTags);
        self::assertSame('use NewWidget instead', $docBlock->deprecated);
        self::assertTrue($docBlock->internal);
        self::assertSame('/** Summary line. */', $docBlock->raw);
    }

    public function testStoresAbsentTagsAsEmpty(): void
    {
        $docBlock = new DocBlock('Summary.', '', [], null, null, [], [], [], [], [], [], null, false, '/** Summary. */');

        self::assertSame('Summary.', $docBlock->summary);
        self::assertSame('', $docBlock->description);
        self::assertSame([], $docBlock->params);
        self::assertNull($docBlock->return);
        self::assertNull($docBlock->var);
        self::assertSame([], $docBlock->throws);
        self::assertSame([], $docBlock->templates);
        self::assertSame([], $docBlock->aliases);
        self::assertSame([], $docBlock->extendsTags);
        self::assertSame([], $docBlock->implementsTags);
        self::assertSame([], $docBlock->usesTags);
        self::assertNull($docBlock->deprecated);
        self::assertFalse($docBlock->internal);
        self::assertSame('/** Summary. */', $docBlock->raw);
    }

    public function testIsPublicApiRecognizesTheKeywordCaseInsensitively(): void
    {
        $public = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '', ['PUBLIC']);
        $restricted = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace', 'Demo\Console']);
        $untagged = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '');

        self::assertTrue($public->isPublicApi());
        self::assertFalse($public->isRestricted());
        self::assertFalse($restricted->isPublicApi());
        self::assertTrue($restricted->isRestricted());
        self::assertFalse($untagged->isPublicApi());
        self::assertFalse($untagged->isRestricted());
    }

    public function testIsRestrictedRecognizesEveryNarrowerScope(): void
    {
        $restricted = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace', 'Demo\Console']);
        $public = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '', ['public']);

        self::assertTrue($restricted->isRestricted());
        self::assertFalse($public->isRestricted());
    }
}
