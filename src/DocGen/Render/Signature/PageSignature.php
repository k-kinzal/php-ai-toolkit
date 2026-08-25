<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Signature;

use function hash;
use function implode;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Cache\ToolkitFingerprint;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Render\Page\Component\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\Social\SocialCard;

use function serialize;
use function strtolower;

/**
 * Digests everything one page of a site is rendered from.
 *
 * Two runs that would write the same page produce the same signature, and
 * two runs that would write different pages do not: that is the whole
 * contract, and it is what lets a run leave a page alone. The digest is
 * therefore built from the answers the renderers actually ask the model
 * for, plus the navigation of the page and the symbols its names resolve
 * to, and it always errs towards depending on too much rather than too
 * little.
 *
 * A symbol is digested through the source file it is declared in, because
 * parsing one file reads nothing but that file: a file that is unchanged
 * documents unchanged symbols.
 */
final class PageSignature
{
    /** @readonly */
    private ToolkitFingerprint $toolkit;

    /** @readonly */
    private SourceDigestIndex $sources;

    /** @readonly */
    private SidebarDigest $sidebars;

    /** @readonly */
    private SymbolReferenceScanner $references;

    /** @readonly */
    private SymbolIndex $symbols;

    /** @readonly */
    private DocumentListHtml $documents;

    /** @readonly */
    private SocialCard $card;

    private ?RenderKit $run = null;

    private string $runDigest = '';

    /**
     * Creates a page signature from its digest collaborators.
     */
    public function __construct(
        ?ToolkitFingerprint $toolkit = null,
        ?SourceDigestIndex $sources = null,
        ?SidebarDigest $sidebars = null,
        ?SymbolReferenceScanner $references = null,
        ?SymbolIndex $symbols = null,
        ?DocumentListHtml $documents = null,
        ?SocialCard $card = null,
    ) {
        $this->toolkit = $toolkit ?? new ToolkitFingerprint();
        $this->sources = $sources ?? new SourceDigestIndex();
        $this->sidebars = $sidebars ?? new SidebarDigest();
        $this->references = $references ?? new SymbolReferenceScanner();
        $this->symbols = $symbols ?? new SymbolIndex();
        $this->documents = $documents ?? new DocumentListHtml();
        $this->card = $card ?? new SocialCard();
    }

    /**
     * Returns the digest of what every page of one run has in common.
     *
     * The comparison a site displays is part of it as a whole rather than
     * page by page: a mark can appear on any page, so a run that compares
     * different revisions writes a different site.
     */
    public function run(RenderKit $services): string
    {
        if ($this->run !== $services) {
            $this->run = $services;
            $this->runDigest = hash('sha256', implode("\0", [
                $this->toolkit->value(),
                $services->model->title,
                (string) $services->model->baseUrl,
                (string) $services->model->repository,
                $this->card->supported() ? 'card' : 'no-card',
                $services->diff->digest(),
            ]));
        }

        return $this->runDigest;
    }

    /**
     * Digests one page from the parts it is rendered from.
     *
     * @param list<string> $parts
     * @param string $namespace the namespace the names in the parts are written in
     * @param array<string, string> $useMap the imports the names in the parts are written under
     */
    public function of(RenderKit $services, array $parts, string $namespace = '', array $useMap = []): string
    {
        $blob = implode("\0", $parts);

        return hash('sha256', implode("\0", [
            $this->run($services),
            $blob,
            $this->references->digest($services, $this->sources, $blob, $namespace, $useMap),
        ]));
    }

    /**
     * Digests the site index page.
     */
    public function index(RenderKit $services): string
    {
        $counts = [];
        foreach ($services->model->classLikes as $classLike) {
            if (!$classLike->isDev) {
                $counts[$classLike->packageName] = ($counts[$classLike->packageName] ?? 0) + 1;
            }
        }

        return $this->of($services, [
            'index',
            serialize($services->model->packages),
            serialize($services->model->graph->edges),
            serialize($services->model->warnings),
            serialize($counts),
            $this->sidebars->of($services, null, null),
        ]);
    }

    /**
     * Digests the overview page of one package.
     */
    public function package(RenderKit $services, DiscoveredPackage $package, ?string $readme): string
    {
        $name = $package->manifest->name;

        return $this->of($services, [
            'package',
            serialize($package),
            serialize($services->model->packages),
            serialize($services->model->graph->edges),
            serialize($services->model->layers),
            serialize($this->symbols->inPackage($services, $name)),
            serialize($this->documents->documents($services, $name)),
            hash('sha256', (string) $readme),
            $this->sidebars->of($services, $name, null),
        ]);
    }

    /**
     * Digests the complete item listing of one package.
     */
    public function allItems(RenderKit $services, string $packageName): string
    {
        return $this->of($services, [
            'all-items',
            $packageName,
            serialize($this->symbols->inPackage($services, $packageName)),
            $this->sidebars->of($services, $packageName, null),
        ]);
    }

    /**
     * Digests the listing of one architecture layer of one package.
     */
    public function layer(RenderKit $services, string $packageName, string $layer): string
    {
        return $this->of($services, [
            'layer',
            $packageName,
            $layer,
            serialize($this->symbols->inLayer($services, $packageName, $layer)),
            serialize($services->model->layers),
            $this->sidebars->of($services, $packageName, null),
        ]);
    }

    /**
     * Digests the listing of one namespace of one package.
     */
    public function namespaced(RenderKit $services, string $packageName, string $namespace): string
    {
        return $this->of($services, [
            'namespace',
            $packageName,
            $namespace,
            serialize($this->symbols->inNamespace($services, $packageName, $namespace)),
            serialize($this->symbols->childNamespaces($services, $packageName, $namespace)),
            $this->sidebars->of($services, $packageName, $namespace),
        ]);
    }

    /**
     * Digests the page of one class, interface, trait, or enum.
     */
    public function classLike(RenderKit $services, ClassLikeDoc $classLike): string
    {
        $model = $services->model;
        $fqcn = $classLike->fqcn;

        return $this->of($services, [
            'class-like',
            $services->url->classLikePage($classLike),
            serialize($classLike),
            serialize([
                $model->hierarchy->implementorsOf($fqcn),
                $model->hierarchy->interfaceExtendersOf($fqcn),
                $model->hierarchy->subclassesOf($fqcn),
                $model->hierarchy->traitUsersOf($fqcn),
            ]),
            serialize($model->usages->forTypeGrouped($fqcn, false)),
            serialize($model->testCases->forType($fqcn)),
            serialize($this->memberParts($services, $classLike)),
            serialize($model->layerAssignments[strtolower($fqcn)] ?? []),
            $this->sidebars->of($services, $classLike->packageName, $classLike->namespace),
        ], $classLike->namespace, $classLike->useMap);
    }

    /**
     * Collects what the members of one symbol are documented with.
     *
     * Members are documented from the file of their own symbol, which is
     * digested with it; what has to be collected here is what the rest of
     * the project says about them.
     *
     * @return list<list<mixed>>
     */
    public function memberParts(RenderKit $services, ClassLikeDoc $classLike): array
    {
        $model = $services->model;
        $fqcn = $classLike->fqcn;
        $parts = [];
        foreach ($classLike->methods as $method) {
            $parts[] = [
                $method->name,
                $model->usages->forMember($fqcn, $method->name, false),
                $model->usages->callsFrom($fqcn, $method->name),
                $model->testCases->forMember($fqcn, $method->name),
                $this->coverageOf($services, $classLike->file, $method->startLine, $method->endLine),
            ];
        }

        foreach ([$classLike->properties, $classLike->constants, $classLike->enumCases] as $members) {
            foreach ($members as $member) {
                $parts[] = [$member->name, $this->coverageOf($services, $classLike->file, $member->line, $member->line)];
            }
        }

        return $parts;
    }

    /**
     * Returns the coverage a member shows, or null when there is none.
     */
    public function coverageOf(RenderKit $services, string $file, int $startLine, int $endLine): mixed
    {
        $coverage = $services->model->coverage;

        return $coverage === null ? null : $coverage->methodAt($file, $startLine, $endLine);
    }

    /**
     * Digests the page of one top-level function.
     */
    public function functionPage(RenderKit $services, FunctionDoc $function): string
    {
        return $this->of($services, [
            'function',
            $services->url->functionPage($function),
            serialize($function),
            serialize($services->model->usages->forType($function->fqn, false)),
            serialize($services->model->testCases->forType($function->fqn)),
            $this->sidebars->of($services, $function->packageName, $function->namespace),
        ], $function->namespace, $function->useMap);
    }

    /**
     * Digests one highlighted source page.
     *
     * @param ?string $code the file as the head revision has it
     * @param ?string $baseCode the file as the base revision had it
     */
    public function source(RenderKit $services, string $relativeFile, ?string $code, ?string $baseCode): string
    {
        return $this->of($services, [
            'source',
            $relativeFile,
            hash('sha256', (string) $code),
            hash('sha256', (string) $baseCode),
            $this->sidebars->of($services, null, null),
        ]);
    }

    /**
     * Digests one rendered Markdown document.
     *
     * @param ?string $markdown the document as the head revision has it
     * @param ?string $baseMarkdown the document as the base revision had it
     */
    public function document(RenderKit $services, MarkdownDoc $document, ?string $markdown, ?string $baseMarkdown): string
    {
        return $this->of($services, [
            'document',
            serialize($document),
            hash('sha256', (string) $markdown),
            hash('sha256', (string) $baseMarkdown),
            serialize($this->documents->paths($services, $document->packageName)),
            $this->sidebars->of($services, $document->packageName, null),
        ]);
    }
}
