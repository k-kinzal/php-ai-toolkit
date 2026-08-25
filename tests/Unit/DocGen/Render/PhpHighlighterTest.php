<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\PhpHighlighter
 * @uses \PhpAiToolkit\DocGen\Render\HtmlText
 */
#[CoversClass(PhpHighlighter::class)]
#[UsesClass(HtmlText::class)]
final class PhpHighlighterTest extends TestCase
{
    public function testHighlightWrapsTokenClasses(): void
    {
        $expected = <<<'HTML'
&lt;?php
<span class="tok-com">// note</span>
<span class="tok-var">$x</span> = <span class="tok-str">&#039;hi&#039;</span> . <span class="tok-num">42</span>;
<span class="tok-kw">echo</span> <span class="tok-var">$x</span>;
HTML;

        self::assertSame($expected . "\n", (new PhpHighlighter())->highlight("<?php\n// note\n\$x = 'hi' . 42;\necho \$x;\n"));
    }

    public function testHighlightSplitsMultilineTokensAtLineBreaks(): void
    {
        $expected = <<<'HTML'
&lt;?php
<span class="tok-com">/* a</span>
<span class="tok-com">b */</span>
HTML;

        self::assertSame($expected . "\n", (new PhpHighlighter())->highlight("<?php\n/* a\nb */\n"));
    }

    public function testHighlightSnippetDropsInjectedOpenTag(): void
    {
        self::assertSame(
            '<span class="tok-var">$y</span> = <span class="tok-num">1.5</span>;',
            (new PhpHighlighter())->highlightSnippet('$y = 1.5;'),
        );
    }

    public function testClassForMapsLexicalTokenClasses(): void
    {
        $tokens = PhpToken::tokenize("<?php\n/** doc */ \$n = 42; 'txt'; Foo\\Bar; ?> html");

        self::assertNull((new PhpHighlighter())->classFor($tokens[0]));
        self::assertSame('tok-com', (new PhpHighlighter())->classFor($tokens[1]));
        self::assertSame('tok-var', (new PhpHighlighter())->classFor($tokens[3]));
        self::assertNull((new PhpHighlighter())->classFor($tokens[5]));
        self::assertSame('tok-num', (new PhpHighlighter())->classFor($tokens[7]));
        self::assertSame('tok-str', (new PhpHighlighter())->classFor($tokens[10]));
        self::assertSame('tok-id', (new PhpHighlighter())->classFor($tokens[13]));
        self::assertNull((new PhpHighlighter())->classFor($tokens[16]));
        self::assertNull((new PhpHighlighter())->classFor($tokens[17]));
    }

    public function testClassForMarksAlphabeticKeywords(): void
    {
        $tokens = PhpToken::tokenize('<?php echo $a;');

        self::assertSame('tok-kw', (new PhpHighlighter())->classFor($tokens[1]));
    }
}
