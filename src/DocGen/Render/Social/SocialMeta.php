<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Social;

use function array_slice;
use function count;
use function implode;

use PhpAiToolkit\DocGen\Render\RenderKit;

use function preg_replace;
use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

use function sprintf;
use function str_ends_with;
use function strip_tags;
use function strlen;
use function substr;
use function trim;

/**
 * Renders what a link to one page is previewed from elsewhere.
 *
 * A site of relative links needs no address of its own, but a card shown
 * for a link shared in a chat or on a timeline does: the address is
 * absolute, and so is the image beside it. Nothing is rendered until a
 * project says where its site is published, because a card that names
 * pages and images nobody can fetch is worse than no card at all.
 */
final class SocialMeta
{
    /**
     * How much of a description a card shows before it is cut off.
     */
    public const SUMMARY_LENGTH = 200;

    /** @readonly */
    private SocialCard $card;

    /**
     * Creates the social preview renderer from the card it names.
     */
    public function __construct(?SocialCard $card = null)
    {
        $this->card = $card ?? new SocialCard();
    }

    /**
     * Renders the preview tags of one page, or nothing.
     *
     * @param string $pagePath the site path of the page, such as index.html
     * @param string $title the page title, as the document itself carries it
     * @param string $description what this page documents, in one sentence
     */
    public function render(RenderKit $services, string $pagePath, string $title, string $description): string
    {
        $baseUrl = $services->model->baseUrl;
        if ($baseUrl === null) {
            return '';
        }

        $escaper = $services->escaper;
        $url = $this->url($baseUrl, $pagePath);
        $heading = sprintf('%s — %s', $title, $services->model->title);

        return sprintf('<link rel="canonical" href="%s">', $escaper->e($url)) . "\n"
            . $this->tag('name', 'description', $escaper->e($this->summary($description)))
            . $this->tag('property', 'og:type', 'website')
            . $this->tag('property', 'og:site_name', $escaper->e($services->model->title))
            . $this->tag('property', 'og:title', $escaper->e($heading))
            . $this->tag('property', 'og:description', $escaper->e($this->summary($description)))
            . $this->tag('property', 'og:url', $escaper->e($url))
            . $this->image($services, $baseUrl)
            . $this->tag('name', 'twitter:card', $this->card->supported() ? 'summary_large_image' : 'summary');
    }

    /**
     * Renders the tags of the card image, or nothing without one.
     */
    public function image(RenderKit $services, string $baseUrl): string
    {
        if (!$this->card->supported()) {
            return '';
        }

        $escaper = $services->escaper;

        return $this->tag('property', 'og:image', $escaper->e($baseUrl . '/' . SocialCard::PATH))
            . $this->tag('property', 'og:image:width', (string) SocialCard::WIDTH)
            . $this->tag('property', 'og:image:height', (string) SocialCard::HEIGHT)
            . $this->tag('property', 'og:image:alt', $escaper->e($services->model->title));
    }

    /**
     * Renders one meta tag, or nothing when it carries no content.
     *
     * @param string $attribute the naming attribute, "property" for Open Graph and "name" for the rest
     * @param string $content the value, already escaped for an attribute
     */
    public function tag(string $attribute, string $name, string $content): string
    {
        if ($content === '') {
            return '';
        }

        return sprintf('<meta %s="%s" content="%s">', $attribute, $name, $content) . "\n";
    }

    /**
     * Returns the absolute address of one page of the site.
     *
     * An index page is named by the directory it indexes, because that is
     * the address readers share and the one a site is linked by.
     */
    public function url(string $baseUrl, string $pagePath): string
    {
        if ($pagePath === 'index.html') {
            return $baseUrl . '/';
        }

        if (str_ends_with($pagePath, '/index.html')) {
            return $baseUrl . '/' . substr($pagePath, 0, -strlen('index.html'));
        }

        return $baseUrl . '/' . $pagePath;
    }

    /**
     * Returns one description as a card shows it: one line, and short.
     */
    public function summary(string $text): string
    {
        $plain = trim((string) preg_replace('/\s+/', ' ', strip_tags($text)));
        $characters = preg_split('//u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false || count($characters) <= self::SUMMARY_LENGTH) {
            return $plain;
        }

        return trim(implode('', array_slice($characters, 0, self::SUMMARY_LENGTH))) . '…';
    }
}
