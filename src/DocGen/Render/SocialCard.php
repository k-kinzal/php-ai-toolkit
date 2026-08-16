<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function count;
use function function_exists;

use GdImage;

use function hexdec;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagepng;
use function imagettftext;
use function is_file;
use function max;
use function min;
use function ob_get_clean;
use function ob_start;

use PhpAiToolkit\DocGen\DocGenException;

use function round;
use function substr;

/**
 * Draws the image a link to the site is previewed with elsewhere.
 *
 * A link shared in a chat or on a timeline is rendered from the tags of
 * the page it points at, and an image is the part of that card a reader
 * sees first. The image is drawn from the project rather than shipped
 * with the generator, so every site previews as itself, and it is drawn
 * in the palette of the site it belongs to.
 *
 * Drawing needs the gd extension with FreeType support. Without it a run
 * writes no image and the pages carry no image tag, because a card that
 * names an image the site does not serve is worse than a card without
 * one.
 */
final class SocialCard
{
    /**
     * Where the drawn image is written inside the site.
     */
    public const PATH = 'assets/og-image.png';

    /**
     * The image size every social platform scales its cards from.
     */
    public const WIDTH = 1200;

    /**
     * The image height that gives the 1.91:1 ratio cards are cropped to.
     */
    public const HEIGHT = 630;

    /**
     * The margin the card keeps on every side.
     */
    public const PADDING = 72;

    /**
     * The band of colors the card is marked with, left to right.
     *
     * @var list<string>
     */
    public const HUES = ['#905754', '#c8a267', '#838060', '#327380'];

    /** @readonly */
    private SocialCardText $text;

    /**
     * Creates a card renderer from its text measurer.
     */
    public function __construct(?SocialCardText $text = null)
    {
        $this->text = $text ?? new SocialCardText();
    }

    /**
     * Reports whether this installation can draw the card at all.
     */
    public function supported(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagettftext')
            && is_file($this->fontPath());
    }

    /**
     * Returns the path of the font the card is set in.
     */
    public function fontPath(): string
    {
        return __DIR__ . '/../../../resources/docgen/og-font.ttf';
    }

    /**
     * Draws one card and returns it as PNG bytes.
     *
     * @param string $title the site title, printed as the card headline
     * @param string $subtitle what the documented project is, printed below it
     *
     * @throws DocGenException when the image cannot be drawn
     */
    public function render(string $title, string $subtitle): string
    {
        $image = $this->image();
        $this->background($image);
        $this->heading($image, $title);
        $this->body($image, $subtitle, $title);
        $this->footer($image);

        ob_start();
        imagepng($image, null, 9);
        $png = ob_get_clean();

        if ($png === '') {
            throw new DocGenException('Could not draw the social preview image: the gd extension encoded no PNG.');
        }

        return $png;
    }

    /**
     * Creates the canvas one card is drawn on.
     *
     * @throws DocGenException when the image cannot be created
     */
    public function image(): GdImage
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($image === false) {
            throw new DocGenException('Could not draw the social preview image: the gd extension refused an image of 1200x630.');
        }

        return $image;
    }

    /**
     * Fills the card and prints the hue strip that identifies the site.
     */
    public function background(GdImage $image): void
    {
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $this->color($image, '#0d1117'));

        $width = (int) round(self::WIDTH / count(self::HUES));
        $x = 0;
        foreach (self::HUES as $hue) {
            imagefilledrectangle($image, $x, 0, $x + $width, 12, $this->color($image, $hue));
            $x += $width;
        }
    }

    /**
     * Prints the label and the title of the card.
     */
    public function heading(GdImage $image, string $title): void
    {
        $font = $this->fontPath();
        imagettftext($image, 20.0, 0.0, self::PADDING, 132, $this->color($image, '#7d8590'), $font, 'DOCUMENTATION');

        $size = $this->text->fit($font, $title, self::WIDTH - self::PADDING * 2, 64, 34);
        $y = 262;
        foreach ($this->text->wrap($font, (float) $size, $title, self::WIDTH - self::PADDING * 2, 2) as $line) {
            imagettftext($image, (float) $size, 0.0, self::PADDING, $y, $this->color($image, '#e6edf3'), $font, $line);
            $y += (int) round($size * 1.4);
        }
    }

    /**
     * Prints what the documented project is, below its title.
     *
     * @param string $title the title already printed, so the subtitle never repeats it
     */
    public function body(GdImage $image, string $subtitle, string $title): void
    {
        if ($subtitle === '' || $subtitle === $title) {
            return;
        }

        $font = $this->fontPath();
        $y = 380;
        foreach ($this->text->wrap($font, 26.0, $subtitle, self::WIDTH - self::PADDING * 2, 2) as $line) {
            imagettftext($image, 26.0, 0.0, self::PADDING, $y, $this->color($image, '#9198a1'), $font, $line);
            $y += 44;
        }
    }

    /**
     * Prints what generated the site.
     */
    public function footer(GdImage $image): void
    {
        imagettftext(
            $image,
            22.0,
            0.0,
            self::PADDING,
            self::HEIGHT - self::PADDING,
            $this->color($image, '#7d8590'),
            $this->fontPath(),
            'generated by php-ai-toolkit doc-gen',
        );
    }

    /**
     * Allocates one color of the design system palette.
     *
     * @param string $hex the color as it is written in the stylesheet, such as #79c0ff
     *
     * @throws DocGenException when the palette runs out of colors
     */
    public function color(GdImage $image, string $hex): int
    {
        $color = imagecolorallocate(
            $image,
            $this->channel($hex, 1),
            $this->channel($hex, 3),
            $this->channel($hex, 5),
        );

        if ($color === false) {
            throw new DocGenException('Could not draw the social preview image: the gd extension allocated no color.');
        }

        return $color;
    }

    /**
     * Reads one channel of a hex color as a byte.
     *
     * @param string $hex the color as it is written in the stylesheet
     * @param int $offset where the channel starts, after the leading hash
     *
     * @return int<0, 255>
     */
    public function channel(string $hex, int $offset): int
    {
        return min(255, max(0, (int) hexdec(substr($hex, $offset, 2))));
    }
}
