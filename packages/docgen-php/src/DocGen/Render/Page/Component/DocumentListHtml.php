<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function count;
use function sprintf;

use Toolkit\DocGen\Analysis\Model\MarkdownDoc;
use Toolkit\DocGen\Render\MarkdownLinks;
use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders and resolves the Markdown documents of a package.
 *
 * The listing and the link resolver share one source of truth, so a
 * document that is listed is also a document that links resolve to.
 */
final class DocumentListHtml
{
    /**
     * Lists the documents of one package.
     *
     * @return list<MarkdownDoc>
     */
    public function documents(RenderKit $services, string $packageName): array
    {
        $documents = [];
        foreach ($services->model->documents as $document) {
            if ($document->packageName === $packageName) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * Lists the document paths of one package.
     *
     * @return list<string>
     */
    public function paths(RenderKit $services, string $packageName): array
    {
        $paths = [];
        foreach ($this->documents($services, $packageName) as $document) {
            $paths[] = $document->path;
        }

        return $paths;
    }

    /**
     * Builds the link resolver for one rendered document.
     *
     * @param string $directory the directory of the rendered document, relative to the package
     */
    public function links(RenderKit $services, string $pagePath, string $packageName, string $directory): MarkdownLinks
    {
        return new MarkdownLinks($services->url, $packageName, $pagePath, $directory, $this->paths($services, $packageName));
    }

    /**
     * Renders the document listing section of one package.
     */
    public function section(RenderKit $services, string $pagePath, string $packageName): string
    {
        $documents = $this->documents($services, $packageName);
        if ($documents === []) {
            return '';
        }

        $statuses = [];
        foreach ($documents as $document) {
            $statuses[] = $services->diff->documentStatus($packageName, $document->path);
        }

        $html = sprintf(
            '<section%s><h2 id="documents">Documents <span class="count">%d</span><a class="anchor" href="#documents">§</a></h2>',
            $services->diff->combined($statuses),
            count($documents),
        ) . '<div class="table-wrap"><table class="symbol-table">';
        foreach ($documents as $document) {
            $html .= sprintf(
                '<tr%s><td><a href="%s">%s</a></td><td class="item-ns">%s</td></tr>',
                $services->diff->mark($services->diff->documentStatus($packageName, $document->path)),
                $services->escaper->e($services->url->href($pagePath, $services->url->documentPage($packageName, $document->path))),
                $services->escaper->e($document->title),
                $services->escaper->e($document->path),
            );
        }

        return $html . '</table></div></section>' . "\n";
    }
}
