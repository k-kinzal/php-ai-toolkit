<?php

declare(strict_types=1);

namespace Tests\Unit\Structure;

use Guard\Structure\Source;
use Guard\Structure\Text;
use Guard\Structure\TextStructurer;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\TextStructurer
 * @uses \Guard\Structure\Source
 * @uses \Guard\Structure\Text
 */
#[CoversClass(TextStructurer::class)]
#[UsesClass(Source::class)]
#[UsesClass(Text::class)]
final class TextStructurerTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureWrapsTheSourceText(): void
    {
        $text = (new TextStructurer())->structure(new Source("\xEF\xBB\xBF@AGENTS.md\n", []));

        self::assertSame("\xEF\xBB\xBF@AGENTS.md\n", $text->content());
    }
}
