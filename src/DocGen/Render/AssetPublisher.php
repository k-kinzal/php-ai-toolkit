<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function file_get_contents;
use function is_file;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;

use function sprintf;

/**
 * Publishes the static stylesheet and script assets into the site.
 */
final class AssetPublisher
{
    /** @readonly */
    private SiteFileWriter $writer;

    /** @readonly */
    private SocialCard $card;

    /**
     * Creates an asset publisher.
     */
    public function __construct(?SiteFileWriter $writer = null, ?SocialCard $card = null)
    {
        $this->writer = $writer ?? new SiteFileWriter();
        $this->card = $card ?? new SocialCard();
    }

    /**
     * Publishes all bundled assets and the GitHub Pages marker file.
     *
     * @throws DocGenException when a bundled asset is missing
     */
    public function publish(string $outputRoot): void
    {
        foreach (['style.css', 'app.js'] as $asset) {
            $this->writer->write($outputRoot, 'assets/' . $asset, $this->assetContents($asset));
        }

        $this->writer->write($outputRoot, '.nojekyll', '');
    }

    /**
     * Draws the image a link to the site is previewed with, if it is asked for.
     *
     * A site that does not say where it is published cannot be linked to
     * absolutely, so nothing previews it and nothing is drawn. Neither is
     * anything drawn where the gd extension is missing: the pages then
     * carry no image tag either, and the card is a card without a picture
     * rather than one pointing at a missing file.
     *
     * @param ?string $baseUrl the address the site is published at, if it is known
     * @param string $title the site title, printed as the headline of the card
     * @param string $description what the documented project is, printed below it
     *
     * @throws DocGenException when the image cannot be drawn or written
     */
    public function publishCard(string $outputRoot, ?string $baseUrl, string $title, string $description): void
    {
        if ($baseUrl === null || !$this->card->supported()) {
            return;
        }

        $this->writer->write($outputRoot, SocialCard::PATH, $this->card->render($title, $description));
    }

    /**
     * Reads one bundled asset from the toolkit resources.
     *
     * @throws DocGenException when the asset file is missing or unreadable
     */
    public function assetContents(string $asset): string
    {
        $path = __DIR__ . '/../../../resources/docgen/' . $asset;
        if (!is_file($path)) {
            throw new DocGenException(sprintf('Bundled asset not found: %s', $path));
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new DocGenException(sprintf('Bundled asset is not readable: %s', $path));
        }

        return $contents;
    }
}
