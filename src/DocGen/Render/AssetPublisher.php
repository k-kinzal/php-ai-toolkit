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

    /**
     * Creates an asset publisher.
     */
    public function __construct(?SiteFileWriter $writer = null)
    {
        $this->writer = $writer ?? new SiteFileWriter();
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
