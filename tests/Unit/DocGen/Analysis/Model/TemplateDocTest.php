<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc
 */
#[CoversClass(TemplateDoc::class)]
final class TemplateDocTest extends TestCase
{
    public function testStoresTemplateData(): void
    {
        $bound = new IdentifierTypeNode('object');

        $template = new TemplateDoc('T', $bound, 'the subject type');

        self::assertSame('T', $template->name);
        self::assertSame($bound, $template->bound);
        self::assertSame('the subject type', $template->description);
    }

    public function testStoresAbsentBoundAsNull(): void
    {
        $template = new TemplateDoc('U', null, '');

        self::assertSame('U', $template->name);
        self::assertNull($template->bound);
        self::assertSame('', $template->description);
    }
}
