<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Social;

use PhpAiToolkit\DocGen\Render\Social\SocialCard;
use PhpAiToolkit\DocGen\Render\Social\SocialCardText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\Social\SocialCard
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialCardText
 */
#[CoversClass(SocialCard::class)]
#[UsesClass(SocialCardText::class)]
#[RequiresPhpExtension('gd')]
final class SocialCardTest extends TestCase
{
    public function testSupportedReportsAnInstallationThatCanDraw(): void
    {
        self::assertTrue((new SocialCard())->supported());
    }

    public function testFontPathNamesTheBundledFont(): void
    {
        self::assertFileExists((new SocialCard())->fontPath());
    }

    public function testRenderDrawsAPngOfTheSizeCardsAreScaledFrom(): void
    {
        $png = (new SocialCard())->render('k-kinzal/php-ai-toolkit', 'A toolkit for AI-assisted PHP development.');

        $size = getimagesizefromstring($png);

        self::assertIsArray($size);
        self::assertSame(SocialCard::WIDTH, $size[0]);
        self::assertSame(SocialCard::HEIGHT, $size[1]);
        self::assertSame('image/png', $size['mime']);
    }

    public function testRenderDrawsTheSameImageForTheSameProject(): void
    {
        $card = new SocialCard();

        self::assertSame(
            $card->render('demo/project', 'One sentence.'),
            $card->render('demo/project', 'One sentence.'),
        );
    }

    public function testRenderDrawsADifferentImageForADifferentProject(): void
    {
        $card = new SocialCard();

        self::assertNotSame(
            $card->render('demo/project', 'One sentence.'),
            $card->render('other/project', 'One sentence.'),
        );
    }

    public function testRenderDrawsACardForATitleTooLongToSetAtFullSize(): void
    {
        $png = (new SocialCard())->render(
            'organisation/a-package-name-that-is-far-longer-than-the-card-is-wide-and-then-some',
            'A description that itself runs well past the two lines the card keeps for it, and so has to be cut short somewhere sensible.',
        );

        self::assertNotSame('', $png);
        self::assertStringStartsWith("\x89PNG", $png);
    }

    public function testImageCreatesTheCanvasOfOneCard(): void
    {
        $image = (new SocialCard())->image();

        self::assertSame(SocialCard::WIDTH, imagesx($image));
        self::assertSame(SocialCard::HEIGHT, imagesy($image));
    }

    public function testBackgroundFillsTheCardAndPrintsTheHueStrip(): void
    {
        $card = new SocialCard();
        $image = $card->image();

        $card->background($image);

        self::assertSame(0x0D1117, imagecolorat($image, 600, 400));
        self::assertSame(0x905754, imagecolorat($image, 4, 4));
        self::assertSame(0x327380, imagecolorat($image, SocialCard::WIDTH - 4, 4));
    }

    public function testHeadingPrintsTheTitleOnTheCard(): void
    {
        $card = new SocialCard();
        $image = $card->image();
        $card->background($image);
        ob_start();
        imagepng($image);
        $blank = ob_get_clean();

        $card->heading($image, 'demo/project');

        ob_start();
        imagepng($image);
        self::assertNotSame($blank, ob_get_clean());
    }

    public function testBodyPrintsTheDescriptionOnTheCard(): void
    {
        $card = new SocialCard();
        $image = $card->image();
        $card->background($image);
        ob_start();
        imagepng($image);
        $blank = ob_get_clean();

        $card->body($image, 'One sentence about the project.', 'demo/project');

        ob_start();
        imagepng($image);
        self::assertNotSame($blank, ob_get_clean());
    }

    public function testBodyPrintsNothingWhenItWouldRepeatTheTitle(): void
    {
        $card = new SocialCard();
        $image = $card->image();
        $card->background($image);
        ob_start();
        imagepng($image);
        $blank = ob_get_clean();

        $card->body($image, 'demo/project', 'demo/project');

        ob_start();
        imagepng($image);
        self::assertSame($blank, ob_get_clean());
    }

    public function testFooterPrintsWhatGeneratedTheSite(): void
    {
        $card = new SocialCard();
        $image = $card->image();
        $card->background($image);
        ob_start();
        imagepng($image);
        $blank = ob_get_clean();

        $card->footer($image);

        ob_start();
        imagepng($image);
        self::assertNotSame($blank, ob_get_clean());
    }

    public function testChannelReadsOneByteOfAHexColor(): void
    {
        $card = new SocialCard();

        self::assertSame(121, $card->channel('#79c0ff', 1));
        self::assertSame(192, $card->channel('#79c0ff', 3));
        self::assertSame(255, $card->channel('#79c0ff', 5));
    }

    public function testColorAllocatesAPaletteColor(): void
    {
        $card = new SocialCard();

        self::assertSame(0x0D1117, $card->color($card->image(), '#0d1117'));
    }
}
