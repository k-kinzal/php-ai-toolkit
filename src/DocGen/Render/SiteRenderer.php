<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function array_keys;
use function count;
use function file_get_contents;
use function filesize;
use function is_file;
use function is_int;
use function ksort;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;

/**
 * Renders the complete static documentation site of a project model.
 */
final class SiteRenderer
{
    /** @readonly */
    private SiteFileWriter $writer;

    /** @readonly */
    private AssetPublisher $assets;

    /** @readonly */
    private SearchIndexBuilder $searchIndex;

    /** @readonly */
    private SiteUrl $url;

    /** @readonly */
    private IndexPage $indexPage;

    /** @readonly */
    private PackagePage $packagePage;

    /** @readonly */
    private NamespacePage $namespacePage;

    /** @readonly */
    private ClassLikePage $classLikePage;

    /** @readonly */
    private FunctionPage $functionPage;

    /** @readonly */
    private SourcePage $sourcePage;

    /** @readonly */
    private AllItemsPage $allItemsPage;

    /** @readonly */
    private LayerPage $layerPage;

    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private DocumentPage $documentPage;

    /** @readonly */
    private WorkerPool $workers;

    /** @readonly */
    private WorkScheduler $scheduler;

    /**
     * Creates a site renderer from its page and asset collaborators.
     */
    public function __construct(
        ?SiteFileWriter $writer = null,
        ?AssetPublisher $assets = null,
        ?SearchIndexBuilder $searchIndex = null,
        ?SiteUrl $url = null,
        ?IndexPage $indexPage = null,
        ?PackagePage $packagePage = null,
        ?NamespacePage $namespacePage = null,
        ?ClassLikePage $classLikePage = null,
        ?FunctionPage $functionPage = null,
        ?SourcePage $sourcePage = null,
        ?AllItemsPage $allItemsPage = null,
        ?LayerPage $layerPage = null,
        ?SidebarHtml $sidebar = null,
        ?DocumentPage $documentPage = null,
        ?WorkerPool $workers = null,
        ?WorkScheduler $scheduler = null,
    ) {
        $this->workers = $workers ?? new WorkerPool();
        $this->scheduler = $scheduler ?? new WorkScheduler();
        $this->writer = $writer ?? new SiteFileWriter();
        $this->assets = $assets ?? new AssetPublisher();
        $this->searchIndex = $searchIndex ?? new SearchIndexBuilder();
        $this->url = $url ?? new SiteUrl();
        $this->indexPage = $indexPage ?? new IndexPage();
        $this->packagePage = $packagePage ?? new PackagePage();
        $this->namespacePage = $namespacePage ?? new NamespacePage();
        $this->classLikePage = $classLikePage ?? new ClassLikePage();
        $this->functionPage = $functionPage ?? new FunctionPage();
        $this->sourcePage = $sourcePage ?? new SourcePage();
        $this->allItemsPage = $allItemsPage ?? new AllItemsPage();
        $this->layerPage = $layerPage ?? new LayerPage();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->documentPage = $documentPage ?? new DocumentPage();
    }

    /**
     * Renders the whole site into the output directory.
     *
     * @param ?DiffIndex $diff the comparison the site displays, if any
     *
     * @return int the number of written HTML pages
     */
    public function render(ProjectModel $model, string $outputRoot, ?DiffIndex $diff = null, ?int $workers = null): int
    {
        $services = $this->services($model, $diff);
        $this->assets->publish($outputRoot);
        $this->writer->write($outputRoot, 'assets/search-index.js', $this->searchIndex->build($model, $services->diff));
        $count = 0;
        $this->writer->write($outputRoot, 'index.html', $this->indexPage->render($services));
        $count++;
        $count += $this->renderPackagePages($services, $model, $outputRoot);
        $count += $this->renderClassLikePages($services, $model, $outputRoot, $workers);
        foreach ($model->functions as $function) {
            if (!$function->isDev) {
                $this->writer->write($outputRoot, $this->url->functionPage($function), $this->functionPage->render($services, $function));
                $count++;
            }
        }

        $count += $this->renderDocumentPages($services, $model, $outputRoot);

        return $count + $this->renderSourcePages($services, $model, $outputRoot, $workers);
    }

    /**
     * Renders one page per documented class-like symbol.
     *
     * This is the largest and the most expensive group of pages a site has,
     * so it is the group that is split across workers. Each page is written
     * by exactly one process and depends on nothing but the finished model,
     * so the site does not depend on how the work was divided.
     *
     * @param ?int $workers how many workers to use, or null for the default
     *
     * @return int the number of written pages
     *
     * @throws DocGenException when a worker cannot finish its pages
     */
    public function renderClassLikePages(RenderKit $services, ProjectModel $model, string $outputRoot, ?int $workers = null): int
    {
        $documented = [];
        foreach ($model->classLikes as $classLike) {
            if (!$classLike->isDev) {
                $documented[] = $classLike;
            }
        }

        return $this->countOf($this->workers->map(
            $this->scheduler->plan($documented, static fn (ClassLikeDoc $classLike): int => $classLike->endLine - $classLike->startLine, $workers),
            function (array $job) use ($services, $outputRoot): int {
                foreach ($job as $classLike) {
                    $this->writer->write($outputRoot, $this->url->classLikePage($classLike), $this->classLikePage->render($services, $classLike));
                }

                return count($job);
            },
        ));
    }

    /**
     * Adds up the page counts every worker reported.
     *
     * A count comes from a worker process, so nothing about it is
     * guaranteed by the type system that produced it. A worker that
     * reported anything else wrote pages this run cannot account for, and
     * a site of unknown completeness is not a site worth reporting.
     *
     * @param list<mixed> $results
     *
     * @throws DocGenException when a worker reported something else
     */
    public function countOf(array $results): int
    {
        $count = 0;
        foreach ($results as $result) {
            if (!is_int($result)) {
                throw new DocGenException('A documentation worker reported no page count.');
            }

            $count += $result;
        }

        return $count;
    }

    /**
     * Renders one highlighted page per source file of the model.
     *
     * A file that only the base revision had is rendered from the base
     * checkout, so the source a removed symbol links to stays readable.
     *
     * Highlighting every line of every documented file is the second most
     * expensive group of pages, so it is split across workers as well.
     *
     * @param ?int $workers how many workers to use, or null for the default
     *
     * @return int the number of written pages
     *
     * @throws DocGenException when a worker cannot finish its pages
     */
    public function renderSourcePages(RenderKit $services, ProjectModel $model, string $outputRoot, ?int $workers = null): int
    {
        $files = $this->sourceFiles($model);
        $root = $model->root;

        return $this->countOf($this->workers->map(
            $this->scheduler->plan($files, static fn (string $file): int => (int) @filesize($root . '/' . $file), $workers),
            fn (array $job): int => $this->writeSourcePages($services, $root, $outputRoot, $job),
        ));
    }

    /**
     * Writes the source pages of one job and reports how many it wrote.
     *
     * @param list<string> $files project-relative source files
     *
     * @throws DocGenException when a page cannot be written
     */
    public function writeSourcePages(RenderKit $services, string $root, string $outputRoot, array $files): int
    {
        $count = 0;
        foreach ($files as $relativeFile) {
            $code = $this->contents($root . '/' . $relativeFile);
            $baseCode = $services->diff->baseSource($relativeFile);
            if ($code === null && $baseCode === null) {
                continue;
            }

            $this->writer->write(
                $outputRoot,
                $this->url->sourcePage($relativeFile),
                $this->sourcePage->render($services, $relativeFile, $code, $baseCode),
            );
            $count++;
        }

        return $count;
    }

    /**
     * Reads one file, or returns null when it cannot be read.
     */
    public function contents(string $path): ?string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false ? null : $contents;
    }

    /**
     * Renders the package and namespace pages of every package.
     *
     * @return int the number of written pages
     */
    public function renderPackagePages(RenderKit $services, ProjectModel $model, string $outputRoot): int
    {
        $count = 0;
        foreach ($model->packages as $package) {
            $this->writer->write(
                $outputRoot,
                $this->url->packagePage($package->manifest->name),
                $this->packagePage->render($services, $package, $this->readme($package)),
            );
            $count++;
            $this->writer->write(
                $outputRoot,
                $this->url->allItemsPage($package->manifest->name),
                $this->allItemsPage->render($services, $package->manifest->name),
            );
            $count++;
            foreach ($this->sidebar->packageLayers($services, $package->manifest->name) as $layer) {
                $this->writer->write(
                    $outputRoot,
                    $this->url->layerPage($package->manifest->name, $layer),
                    $this->layerPage->render($services, $package->manifest->name, $layer),
                );
                $count++;
            }

            foreach ($this->namespacesOf($model, $package->manifest->name) as $namespace) {
                if ($namespace === '') {
                    continue;
                }

                $this->writer->write(
                    $outputRoot,
                    $this->url->namespacePage($package->manifest->name, $namespace),
                    $this->namespacePage->render($services, $package->manifest->name, $namespace),
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Renders one page per Markdown document of the project.
     *
     * @return int the number of written pages
     */
    public function renderDocumentPages(RenderKit $services, ProjectModel $model, string $outputRoot): int
    {
        $count = 0;
        foreach ($model->documents as $document) {
            $markdown = $this->contents($model->root . '/' . $document->file);
            $baseMarkdown = $services->diff->baseSource($document->file);
            if ($markdown === null && $baseMarkdown === null) {
                continue;
            }

            $this->writer->write(
                $outputRoot,
                $this->url->documentPage($document->packageName, $document->path),
                $this->documentPage->render($services, $document, $markdown, $baseMarkdown),
            );
            $count++;
        }

        return $count;
    }

    /**
     * Builds the shared render services of one generation run.
     *
     * @param ?DiffIndex $diff the comparison the site displays, if any
     */
    public function services(ProjectModel $model, ?DiffIndex $diff = null): RenderKit
    {
        return new RenderKit(
            $model,
            $this->url,
            new HtmlText(),
            new PhpHighlighter(),
            new MarkdownRenderer(),
            new TypeHtml(null, $this->url),
            new DoctestExtractor(),
            new AssertionScanner(),
            new DiffHtml($diff),
        );
    }

    /**
     * Reads the README of a package when one exists.
     */
    public function readme(DiscoveredPackage $package): ?string
    {
        $path = $package->manifest->directory . '/README.md';
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    /**
     * Lists the namespaces of one package in sorted order.
     *
     * @return list<string>
     */
    public function namespacesOf(ProjectModel $model, string $packageName): array
    {
        $namespaces = [];
        foreach ($model->classLikes as $classLike) {
            if ($classLike->packageName === $packageName && !$classLike->isDev) {
                $namespaces[$classLike->namespace] = true;
            }
        }

        foreach ($model->functions as $function) {
            if ($function->packageName === $packageName && !$function->isDev) {
                $namespaces[$function->namespace] = true;
            }
        }

        ksort($namespaces);

        return array_keys($namespaces);
    }

    /**
     * Lists every unique source file of the model, tests included.
     *
     * @return list<string>
     */
    public function sourceFiles(ProjectModel $model): array
    {
        $files = [];
        foreach ($model->classLikes as $classLike) {
            $files[$classLike->file] = true;
        }

        foreach ($model->functions as $function) {
            $files[$function->file] = true;
        }

        ksort($files);

        return array_keys($files);
    }
}
