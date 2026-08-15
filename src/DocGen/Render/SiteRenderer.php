<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function array_keys;
use function file_get_contents;
use function is_file;
use function ksort;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
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
    ) {
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
    }

    /**
     * Renders the whole site into the output directory.
     *
     * @return int the number of written HTML pages
     */
    public function render(ProjectModel $model, string $outputRoot): int
    {
        $services = $this->services($model);
        $this->assets->publish($outputRoot);
        $this->writer->write($outputRoot, 'assets/search-index.js', $this->searchIndex->build($model));
        $count = 0;
        $this->writer->write($outputRoot, 'index.html', $this->indexPage->render($services));
        $count++;
        $count += $this->renderPackagePages($services, $model, $outputRoot);
        foreach ($model->classLikes as $classLike) {
            if (!$classLike->isDev) {
                $this->writer->write($outputRoot, $this->url->classLikePage($classLike), $this->classLikePage->render($services, $classLike));
                $count++;
            }
        }

        foreach ($model->functions as $function) {
            if (!$function->isDev) {
                $this->writer->write($outputRoot, $this->url->functionPage($function), $this->functionPage->render($services, $function));
                $count++;
            }
        }

        foreach ($this->sourceFiles($model) as $relativeFile) {
            $code = is_file($model->root . '/' . $relativeFile) ? file_get_contents($model->root . '/' . $relativeFile) : false;
            if ($code !== false) {
                $this->writer->write($outputRoot, $this->url->sourcePage($relativeFile), $this->sourcePage->render($services, $relativeFile, $code));
                $count++;
            }
        }

        return $count;
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
     * Builds the shared render services of one generation run.
     */
    public function services(ProjectModel $model): RenderKit
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
