<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Social;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialCardText;

/**
 * @covers \Toolkit\DocGen\Render\Social\SocialCardText
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 */
#[CoversClass(SocialCardText::class)]
#[UsesClass(SocialCard::class)]
#[RequiresPhpExtension('gd')]
final class SocialCardTextTest extends TestCase
{
    public function testWidthGrowsWithTheTextAndWithTheSize(): void
    {
        $font = (new SocialCard())->fontPath();
        $text = new SocialCardText();

        self::assertSame(0, $text->width($font, 32.0, ''));
        self::assertGreaterThan($text->width($font, 32.0, 'ab'), $text->width($font, 32.0, 'abcd'));
        self::assertGreaterThan($text->width($font, 16.0, 'abcd'), $text->width($font, 32.0, 'abcd'));
    }

    public function testFitReturnsTheLargestSizeThatStillFits(): void
    {
        $font = (new SocialCard())->fontPath();
        $text = new SocialCardText();

        $size = $text->fit($font, 'k-kinzal/php-ai-toolkit', 600, 64, 20);

        self::assertLessThanOrEqual(64, $size);
        self::assertGreaterThanOrEqual(20, $size);
        self::assertLessThanOrEqual(600, $text->width($font, (float) $size, 'k-kinzal/php-ai-toolkit'));
    }

    public function testFitStopsAtTheSmallestSizeForTextThatNeverFits(): void
    {
        $font = (new SocialCard())->fontPath();

        self::assertSame(20, (new SocialCardText())->fit($font, 'a title far too long for the width it is given', 40, 64, 20));
    }

    public function testWrapBreaksTextIntoTheLinesItOccupies(): void
    {
        $font = (new SocialCard())->fontPath();

        $lines = (new SocialCardText())->wrap($font, 26.0, 'one two three four five six seven eight nine ten', 400, 10);

        self::assertGreaterThan(1, count($lines));
        self::assertSame('one two three four five six seven eight nine ten', trim(implode(' ', $lines)));
    }

    public function testWrapEndsTheLastKeptLineWithAnEllipsis(): void
    {
        $font = (new SocialCard())->fontPath();

        $lines = (new SocialCardText())->wrap($font, 26.0, 'one two three four five six seven eight nine ten', 200, 2);

        self::assertCount(2, $lines);
        self::assertStringEndsWith('…', $lines[1]);
        self::assertLessThanOrEqual(200, (new SocialCardText())->width($font, 26.0, $lines[1]));
    }

    public function testWrapReturnsNothingForTextThatIsOnlyWhitespace(): void
    {
        self::assertSame([], (new SocialCardText())->wrap((new SocialCard())->fontPath(), 26.0, '   ', 200, 2));
    }

    public function testLinesKeepsEveryLineOfTheText(): void
    {
        $font = (new SocialCard())->fontPath();

        self::assertSame(['single'], (new SocialCardText())->lines($font, 26.0, 'single', 400));
    }

    public function testShortenedCutsALineUntilItFits(): void
    {
        $font = (new SocialCard())->fontPath();

        $line = (new SocialCardText())->shortened($font, 26.0, 'a line that is very much longer than the width', 120);

        self::assertStringEndsWith('…', $line);
        self::assertLessThanOrEqual(120, (new SocialCardText())->width($font, 26.0, $line));
    }
}
