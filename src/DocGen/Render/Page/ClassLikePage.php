<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function array_merge;
use function count;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Render\Diff\DiffBanner;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;

use function sprintf;
use function strtolower;

/**
 * Renders the documentation page of one class, interface, trait, or enum.
 */
final class ClassLikePage
{
    /** @readonly */
    private PageChrome $chrome;

    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private BreadcrumbHtml $breadcrumb;

    /** @readonly */
    private SignatureHtml $signature;

    /** @readonly */
    private DocTextHtml $docText;

    /** @readonly */
    private MemberHtml $member;

    /** @readonly */
    private RelationsHtml $relations;

    /** @readonly */
    private TestCaseHtml $testCaseHtml;

    /** @readonly */
    private DiffBanner $banner;

    /** @readonly */
    private PrivateSurfaceHtml $privateSurface;

    /** @readonly */
    private SymbolDescription $descriptions;

    /**
     * Creates a class-like page renderer from its section collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?SignatureHtml $signature = null,
        ?DocTextHtml $docText = null,
        ?MemberHtml $member = null,
        ?RelationsHtml $relations = null,
        ?TestCaseHtml $testCaseHtml = null,
        ?DiffBanner $banner = null,
        ?PrivateSurfaceHtml $privateSurface = null,
        ?SymbolDescription $descriptions = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->signature = $signature ?? new SignatureHtml();
        $this->docText = $docText ?? new DocTextHtml();
        $this->member = $member ?? new MemberHtml();
        $this->relations = $relations ?? new RelationsHtml();
        $this->testCaseHtml = $testCaseHtml ?? new TestCaseHtml();
        $this->banner = $banner ?? new DiffBanner();
        $this->privateSurface = $privateSurface ?? new PrivateSurfaceHtml();
        $this->descriptions = $descriptions ?? new SymbolDescription();
    }

    /**
     * Renders one complete class-like page document.
     */
    public function render(RenderKit $services, ClassLikeDoc $classLike): string
    {
        $pagePath = $services->url->classLikePage($classLike);
        $context = $this->context($services, $pagePath, $classLike, []);
        $crumbs = [['label' => $classLike->packageName, 'path' => $services->url->packagePage($classLike->packageName)]];
        if ($classLike->namespace !== '') {
            $crumbs[] = ['label' => $classLike->namespace, 'path' => $services->url->namespacePage($classLike->packageName, $classLike->namespace)];
        }

        $crumbs[] = ['label' => $classLike->shortName, 'path' => null];

        return $this->chrome->page(
            $services,
            $pagePath,
            $classLike->shortName,
            $this->descriptions->ofClassLike($classLike),
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope(
                $classLike->packageName,
                $classLike->namespace,
                $classLike->fqcn,
                $this->sections($services, $classLike),
            )),
            $this->content($services, $pagePath, $classLike, $context),
        );
    }

    /**
     * Lists the sections this page will render, in page order.
     *
     * Every anchor carries the state of the section it points at, so the
     * navigation of a page narrows exactly as the page itself does.
     *
     * @return list<array{id: string, label: string, status: string}>
     */
    public function sections(RenderKit $services, ClassLikeDoc $classLike): array
    {
        $constants = $this->visibleMembers($classLike->constants);
        $properties = $this->visibleMembers($classLike->properties);
        $methods = $this->visibleMembers($classLike->methods);
        $candidates = [
            ['id' => 'aliases', 'label' => 'Type aliases', 'present' => ($classLike->docBlock !== null ? $classLike->docBlock->aliases : []) !== [], 'status' => $services->diff->headerStatus($classLike->fqcn)],
            ['id' => 'cases', 'label' => 'Cases', 'present' => $classLike->enumCases !== [], 'status' => $this->sectionStatus($services, $classLike, DiffKey::ENUM_CASE, $classLike->enumCases)],
            ['id' => 'constants', 'label' => 'Constants', 'present' => $constants !== [], 'status' => $this->sectionStatus($services, $classLike, DiffKey::CONSTANT, $constants)],
            ['id' => 'properties', 'label' => 'Properties', 'present' => $properties !== [], 'status' => $this->sectionStatus($services, $classLike, DiffKey::PROPERTY, $properties)],
            ['id' => 'methods', 'label' => 'Methods', 'present' => $methods !== [], 'status' => $this->sectionStatus($services, $classLike, DiffKey::METHOD, $methods)],
            ['id' => 'private-surface', 'label' => 'Private surface', 'present' => $this->privateSurface->members($classLike) !== [], 'status' => $services->diff->combine($this->privateSurface->statuses($services, $classLike))],
            ['id' => 'test-cases', 'label' => 'Test cases', 'present' => $services->model->testCases->forType($classLike->fqcn) !== [], 'status' => DiffStatus::SAME],
            ['id' => 'relations', 'label' => 'Relations', 'present' => true, 'status' => DiffStatus::SAME],
        ];

        $sections = [];
        foreach ($candidates as $candidate) {
            if ($candidate['present']) {
                $sections[] = ['id' => $candidate['id'], 'label' => $candidate['label'], 'status' => $candidate['status']];
            }
        }

        return $sections;
    }

    /**
     * Filters the members that are part of the documented public surface.
     *
     * @template T of \PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc|\PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc|\PhpAiToolkit\DocGen\Analysis\Model\MethodDoc
     *
     * @param list<T> $members
     *
     * @return list<T>
     */
    public function visibleMembers(array $members): array
    {
        $visible = [];
        foreach ($members as $member) {
            if ($member->visibility !== 'private') {
                $visible[] = $member;
            }
        }

        return $visible;
    }

    /**
     * Builds the type resolution context of this page.
     *
     * @param list<\PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc> $extraTemplates
     */
    public function context(RenderKit $services, string $pagePath, ClassLikeDoc $classLike, array $extraTemplates): TypeRenderContext
    {
        $templates = [];
        foreach (array_merge($classLike->docBlock !== null ? $classLike->docBlock->templates : [], $extraTemplates) as $template) {
            $templates[] = $template->name;
        }

        $aliases = [];
        foreach ($classLike->docBlock !== null ? $classLike->docBlock->aliases : [] as $alias) {
            $aliases[$alias->name] = '#alias.' . $alias->name;
        }

        return new TypeRenderContext($pagePath, $classLike->namespace, $classLike->useMap, $templates, $aliases, $services->model->symbolTable);
    }

    /**
     * Renders the page content of one class-like symbol.
     */
    public function content(RenderKit $services, string $pagePath, ClassLikeDoc $classLike, TypeRenderContext $context): string
    {
        $escaper = $services->escaper;
        $html = '<div class="symbol-head">';
        $html .= sprintf('<h1><span class="chip chip-kind k-%s">%s</span>%s</h1>', $classLike->kind, $classLike->kind, $escaper->e($classLike->shortName));
        $html .= '<div class="symbol-meta">';
        foreach ($services->model->layerAssignments[strtolower($classLike->fqcn)] ?? [] as $layer) {
            $html .= sprintf(
                '<a class="chip chip-layer" href="%s" title="deptrac layer">%s</a>',
                $escaper->e($services->url->href($pagePath, $services->url->layerPage($classLike->packageName, $layer))),
                $escaper->e($layer),
            );
        }

        $html .= sprintf(
            '<a class="src-link" href="%s">%s:%d</a>',
            $escaper->e($services->url->href($pagePath, $services->url->sourcePage($classLike->file)) . '#L' . $classLike->startLine),
            $escaper->e($classLike->file),
            $classLike->startLine,
        );
        $html .= '</div></div>' . "\n";
        $html .= $this->banner->render($services, $services->diff->classLikeStatus($classLike->fqcn));
        $html .= $this->signature->classSignature($services, $classLike, $context);
        $html .= $this->docText->render($services, $classLike->docBlock, $context, $classLike->shortName);
        $html .= $this->aliasSection($services, $pagePath, $classLike, $context);
        $html .= $this->member->tagExamples($services, $classLike->docBlock, $classLike->shortName);
        $html .= $this->memberSections($services, $pagePath, $classLike, $context);
        $html .= $this->privateSurface->section($services, $classLike, $context);
        $html .= $this->testCaseSection($services, $pagePath, $classLike);

        return $html . $this->relations->build($services, $pagePath, $classLike);
    }

    /**
     * Renders the test cases that exercise the whole symbol.
     */
    public function testCaseSection(RenderKit $services, string $pagePath, ClassLikeDoc $classLike): string
    {
        $testCases = $services->model->testCases->forType($classLike->fqcn);
        if ($testCases === []) {
            return '';
        }

        $dedicated = [];
        $others = [];
        foreach ($testCases as $testCase) {
            if ($this->isDedicatedTest($testCase->testClass, $classLike->shortName)) {
                $dedicated[] = $testCase;
            } else {
                $others[] = $testCase;
            }
        }

        return sprintf(
            '<section%s><h2 id="test-cases">Test cases <span class="count">%d</span><a class="anchor" href="#test-cases">§</a></h2>',
            $services->diff->unchanged(),
            count($testCases),
        )
            . '<p class="section-note">Test cases that cover or call this symbol, from the coverage report and from the analyzed test sources.</p>'
            . $this->testCaseHtml->subSection($services, $pagePath, 'Dedicated tests', $dedicated, true)
            . $this->testCaseHtml->subSection($services, $pagePath, 'Other tests reaching this symbol', $others, false)
            . '</section>' . "\n";
    }

    /**
     * Reports whether a test class is the dedicated test of a symbol.
     *
     * The convention of one test class per source class is what makes a
     * test the authoritative description of a symbol's behavior, so those
     * cases are listed before the tests that merely pass through it.
     */
    public function isDedicatedTest(string $testClass, string $shortName): bool
    {
        $position = strrpos($testClass, '\\');

        return ($position === false ? $testClass : substr($testClass, $position + 1)) === $shortName . 'Test';
    }

    /**
     * Renders the type alias definitions of a class docblock.
     */
    public function aliasSection(RenderKit $services, string $pagePath, ClassLikeDoc $classLike, TypeRenderContext $context): string
    {
        $aliases = $classLike->docBlock !== null ? $classLike->docBlock->aliases : [];
        if ($aliases === []) {
            return '';
        }

        $html = '<section' . $services->diff->header($classLike->fqcn) . '><h2 id="aliases">Type Aliases<a class="anchor" href="#aliases">§</a></h2>';
        foreach ($aliases as $alias) {
            $definition = $alias->importedFrom !== null
                ? '<span class="t-key">import from</span> ' . $services->typeHtml->className($alias->importedFrom, $context)
                : ($alias->type !== null ? $services->typeHtml->node($alias->type, $context) : '');
            $html .= sprintf(
                '<div class="member alias-def" id="alias.%s"><pre class="member-sig"><code><span class="t-alias">%s</span> = %s</code></pre></div>',
                $services->escaper->e($alias->name),
                $services->escaper->e($alias->name),
                $definition,
            );
        }

        return $html . '</section>' . "\n";
    }

    /**
     * Renders the case, constant, property, and method sections.
     */
    public function memberSections(RenderKit $services, string $pagePath, ClassLikeDoc $classLike, TypeRenderContext $context): string
    {
        $html = '';
        if ($classLike->enumCases !== []) {
            $html .= '<section' . $this->sectionMark($services, $classLike, DiffKey::ENUM_CASE, $classLike->enumCases)
                . '><h2 id="cases">Cases<a class="anchor" href="#cases">§</a></h2>';
            foreach ($classLike->enumCases as $case) {
                $html .= $this->member->enumCase($services, $pagePath, $classLike, $case, $context);
            }

            $html .= '</section>' . "\n";
        }

        $visible = static fn (string $visibility): bool => $visibility !== 'private';
        $constants = [];
        foreach ($classLike->constants as $constant) {
            if ($visible($constant->visibility)) {
                $constants[] = $constant;
            }
        }

        if ($constants !== []) {
            $html .= '<section' . $this->sectionMark($services, $classLike, DiffKey::CONSTANT, $constants)
                . '><h2 id="constants">Constants<a class="anchor" href="#constants">§</a></h2>';
            foreach ($constants as $constant) {
                $html .= $this->member->constant($services, $pagePath, $classLike, $constant, $context);
            }

            $html .= '</section>' . "\n";
        }

        $properties = [];
        foreach ($classLike->properties as $property) {
            if ($visible($property->visibility)) {
                $properties[] = $property;
            }
        }

        if ($properties !== []) {
            $html .= '<section' . $this->sectionMark($services, $classLike, DiffKey::PROPERTY, $properties)
                . '><h2 id="properties">Properties<a class="anchor" href="#properties">§</a></h2>';
            foreach ($properties as $property) {
                $html .= $this->member->property($services, $pagePath, $classLike, $property, $context);
            }

            $html .= '</section>' . "\n";
        }

        return $html . $this->methodSection($services, $pagePath, $classLike);
    }

    /**
     * Renders the method section with per-method template scopes.
     */
    public function methodSection(RenderKit $services, string $pagePath, ClassLikeDoc $classLike): string
    {
        $methods = [];
        foreach ($classLike->methods as $method) {
            if ($method->visibility !== 'private') {
                $methods[] = $method;
            }
        }

        if ($methods === []) {
            return '';
        }

        $html = '<section' . $this->sectionMark($services, $classLike, DiffKey::METHOD, $methods)
            . '><h2 id="methods">Methods<a class="anchor" href="#methods">§</a></h2>';
        foreach ($methods as $method) {
            $methodContext = $this->context(
                $services,
                $pagePath,
                $classLike,
                $method->docBlock !== null ? $method->docBlock->templates : [],
            );
            $html .= $this->member->method($services, $pagePath, $classLike, $method, $methodContext);
        }

        return $html . '</section>' . "\n";
    }

    /**
     * Returns the combined diff attribute of one member section.
     *
     * A section is as changed as the members it holds, so a section that
     * nothing touched steps aside when only changes are asked for, while a
     * section holding one new member stays and shows it.
     *
     * @param list<\PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc|\PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc|\PhpAiToolkit\DocGen\Analysis\Model\MethodDoc|\PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc> $members
     */
    public function sectionMark(RenderKit $services, ClassLikeDoc $classLike, string $kind, array $members): string
    {
        return $services->diff->mark($this->sectionStatus($services, $classLike, $kind, $members));
    }

    /**
     * Combines the state of the members of one section.
     *
     * @param list<\PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc|\PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc|\PhpAiToolkit\DocGen\Analysis\Model\MethodDoc|\PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc> $members
     */
    public function sectionStatus(RenderKit $services, ClassLikeDoc $classLike, string $kind, array $members): string
    {
        $statuses = [];
        foreach ($members as $member) {
            $statuses[] = $services->diff->memberStatus($classLike->fqcn, $kind, $member->name);
        }

        return $services->diff->combine($statuses);
    }
}
