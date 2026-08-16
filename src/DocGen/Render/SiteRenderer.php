<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function count;
use function filesize;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Cache\CachedPageWriter;
use PhpAiToolkit\DocGen\Cache\PageRecord;
use PhpAiToolkit\DocGen\Cache\RenderCache;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
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
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;

/**
 * Renders the complete static documentation site of a project model.
 *
 * Every page reports what it was written from, so a run knows the site it
 * produced and not merely how many files it wrote. That is what lets the
 * next run leave the pages nothing happened to alone, and remove the pages
 * the project no longer has.
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

    /** @readonly */
    private SitePages $pages;

    /** @readonly */
    private PageSignature $signatures;

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
        ?SitePages $pages = null,
        ?PageSignature $signatures = null,
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
        $this->pages = $pages ?? new SitePages();
        $this->signatures = $signatures ?? new PageSignature();
    }

    /**
     * Renders the whole site into the output directory.
     *
     * @param ?DiffIndex $diff the comparison the site displays, if any
     * @param ?int $workers how many workers to use, or null for the default
     * @param ?RenderCache $cache what the previous run wrote into this directory
     *
     * @return int the number of pages the site holds
     *
     * @throws DocGenException when a page cannot be rendered or written
     */
    public function render(ProjectModel $model, string $outputRoot, ?DiffIndex $diff = null, ?int $workers = null, ?RenderCache $cache = null): int
    {
        $services = $this->services($model, $diff);
        $writer = new CachedPageWriter($this->writer, $cache);
        $this->assets->publish($outputRoot);
        $this->writer->write($outputRoot, 'assets/search-index.js', $this->searchIndex->build($model, $services->diff));
        $records = [$writer->write(
            $outputRoot,
            'index.html',
            $this->signatures->index($services),
            fn (): string => $this->indexPage->render($services),
        )];
        foreach ([
            $this->renderPackagePages($services, $model, $outputRoot, $writer),
            $this->renderClassLikePages($services, $model, $outputRoot, $writer, $workers),
            $this->renderFunctionPages($services, $model, $outputRoot, $writer),
            $this->renderDocumentPages($services, $model, $outputRoot, $writer),
            $this->renderSourcePages($services, $model, $outputRoot, $writer, $workers),
        ] as $group) {
            foreach ($group as $record) {
                $records[] = $record;
            }
        }

        $cache?->record($outputRoot, $records);

        return count($records);
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
     * @return list<PageRecord>
     *
     * @throws DocGenException when a worker cannot finish its pages
     */
    public function renderClassLikePages(RenderKit $services, ProjectModel $model, string $outputRoot, CachedPageWriter $writer, ?int $workers = null): array
    {
        $documented = [];
        foreach ($model->classLikes as $classLike) {
            if (!$classLike->isDev) {
                $documented[] = $classLike;
            }
        }

        return $writer->records($this->workers->map(
            $this->scheduler->plan($documented, static fn (ClassLikeDoc $classLike): int => $classLike->endLine - $classLike->startLine, $workers),
            function (array $job) use ($services, $outputRoot, $writer): array {
                $records = [];
                foreach ($job as $classLike) {
                    $records[] = $writer->write(
                        $outputRoot,
                        $this->url->classLikePage($classLike),
                        $this->signatures->classLike($services, $classLike),
                        fn (): string => $this->classLikePage->render($services, $classLike),
                    );
                }

                return $records;
            },
        ));
    }

    /**
     * Renders one page per documented top-level function.
     *
     * @return list<PageRecord>
     *
     * @throws DocGenException when a page cannot be written
     */
    public function renderFunctionPages(RenderKit $services, ProjectModel $model, string $outputRoot, CachedPageWriter $writer): array
    {
        $records = [];
        foreach ($model->functions as $function) {
            if (!$function->isDev) {
                $records[] = $writer->write(
                    $outputRoot,
                    $this->url->functionPage($function),
                    $this->signatures->functionPage($services, $function),
                    fn (): string => $this->functionPage->render($services, $function),
                );
            }
        }

        return $records;
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
     * @return list<PageRecord>
     *
     * @throws DocGenException when a worker cannot finish its pages
     */
    public function renderSourcePages(RenderKit $services, ProjectModel $model, string $outputRoot, CachedPageWriter $writer, ?int $workers = null): array
    {
        $files = $this->pages->sourceFiles($model);
        $root = $model->root;

        return $writer->records($this->workers->map(
            $this->scheduler->plan($files, static fn (string $file): int => (int) @filesize($root . '/' . $file), $workers),
            fn (array $job): array => $this->writeSourcePages($services, $root, $outputRoot, $writer, $job),
        ));
    }

    /**
     * Writes the source pages of one job and reports what it wrote.
     *
     * @param list<string> $files project-relative source files
     *
     * @return list<PageRecord>
     *
     * @throws DocGenException when a page cannot be written
     */
    public function writeSourcePages(RenderKit $services, string $root, string $outputRoot, CachedPageWriter $writer, array $files): array
    {
        $records = [];
        foreach ($files as $relativeFile) {
            $code = $this->pages->contents($root . '/' . $relativeFile);
            $baseCode = $services->diff->baseSource($relativeFile);
            if ($code === null && $baseCode === null) {
                continue;
            }

            $records[] = $writer->write(
                $outputRoot,
                $this->url->sourcePage($relativeFile),
                $this->signatures->source($services, $relativeFile, $code, $baseCode),
                fn (): string => $this->sourcePage->render($services, $relativeFile, $code, $baseCode),
            );
        }

        return $records;
    }

    /**
     * Renders the package and namespace pages of every package.
     *
     * @return list<PageRecord>
     *
     * @throws DocGenException when a page cannot be written
     */
    public function renderPackagePages(RenderKit $services, ProjectModel $model, string $outputRoot, CachedPageWriter $writer): array
    {
        $records = [];
        foreach ($model->packages as $package) {
            $name = $package->manifest->name;
            $readme = $this->pages->readme($package);
            $records[] = $writer->write(
                $outputRoot,
                $this->url->packagePage($name),
                $this->signatures->package($services, $package, $readme),
                fn (): string => $this->packagePage->render($services, $package, $readme),
            );
            $records[] = $writer->write(
                $outputRoot,
                $this->url->allItemsPage($name),
                $this->signatures->allItems($services, $name),
                fn (): string => $this->allItemsPage->render($services, $name),
            );
            foreach ($this->sidebar->packageLayers($services, $name) as $layer) {
                $records[] = $writer->write(
                    $outputRoot,
                    $this->url->layerPage($name, $layer),
                    $this->signatures->layer($services, $name, $layer),
                    fn (): string => $this->layerPage->render($services, $name, $layer),
                );
            }

            foreach ($this->namespacePages($services, $model, $outputRoot, $writer, $name) as $record) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Renders the namespace listing pages of one package.
     *
     * @return list<PageRecord>
     *
     * @throws DocGenException when a page cannot be written
     */
    public function namespacePages(RenderKit $services, ProjectModel $model, string $outputRoot, CachedPageWriter $writer, string $packageName): array
    {
        $records = [];
        foreach ($this->pages->namespacesOf($model, $packageName) as $namespace) {
            if ($namespace === '') {
                continue;
            }

            $records[] = $writer->write(
                $outputRoot,
                $this->url->namespacePage($packageName, $namespace),
                $this->signatures->namespaced($services, $packageName, $namespace),
                fn (): string => $this->namespacePage->render($services, $packageName, $namespace),
            );
        }

        return $records;
    }

    /**
     * Renders one page per Markdown document of the project.
     *
     * @return list<PageRecord>
     *
     * @throws DocGenException when a page cannot be written
     */
    public function renderDocumentPages(RenderKit $services, ProjectModel $model, string $outputRoot, CachedPageWriter $writer): array
    {
        $records = [];
        foreach ($model->documents as $document) {
            $markdown = $this->pages->contents($model->root . '/' . $document->file);
            $baseMarkdown = $services->diff->baseSource($document->file);
            if ($markdown === null && $baseMarkdown === null) {
                continue;
            }

            $records[] = $writer->write(
                $outputRoot,
                $this->url->documentPage($document->packageName, $document->path),
                $this->signatures->document($services, $document, $markdown, $baseMarkdown),
                fn (): string => $this->documentPage->render($services, $document, $markdown, $baseMarkdown),
            );
        }

        return $records;
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
}
