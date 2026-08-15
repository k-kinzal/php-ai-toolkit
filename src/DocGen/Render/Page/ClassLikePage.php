<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Page;

use function array_merge;
use function count;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
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
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->signature = $signature ?? new SignatureHtml();
        $this->docText = $docText ?? new DocTextHtml();
        $this->member = $member ?? new MemberHtml();
        $this->relations = $relations ?? new RelationsHtml();
        $this->testCaseHtml = $testCaseHtml ?? new TestCaseHtml();
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
     * @return list<array{id: string, label: string}>
     */
    public function sections(RenderKit $services, ClassLikeDoc $classLike): array
    {
        $candidates = [
            ['id' => 'aliases', 'label' => 'Type aliases', 'present' => ($classLike->docBlock !== null ? $classLike->docBlock->aliases : []) !== []],
            ['id' => 'cases', 'label' => 'Cases', 'present' => $classLike->enumCases !== []],
            ['id' => 'constants', 'label' => 'Constants', 'present' => $this->visibleMembers($classLike->constants) !== []],
            ['id' => 'properties', 'label' => 'Properties', 'present' => $this->visibleMembers($classLike->properties) !== []],
            ['id' => 'methods', 'label' => 'Methods', 'present' => $this->visibleMembers($classLike->methods) !== []],
            ['id' => 'private-surface', 'label' => 'Private surface', 'present' => $this->privateMembers($classLike) !== []],
            ['id' => 'test-cases', 'label' => 'Test cases', 'present' => $services->model->testCases->forType($classLike->fqcn) !== []],
            ['id' => 'relations', 'label' => 'Relations', 'present' => true],
        ];

        $sections = [];
        foreach ($candidates as $candidate) {
            if ($candidate['present']) {
                $sections[] = ['id' => $candidate['id'], 'label' => $candidate['label']];
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
     * Collects the private members of a class-like symbol.
     *
     * @return list<\PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc|\PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc|\PhpAiToolkit\DocGen\Analysis\Model\MethodDoc>
     */
    public function privateMembers(ClassLikeDoc $classLike): array
    {
        $private = [];
        foreach ([$classLike->constants, $classLike->properties, $classLike->methods] as $members) {
            foreach ($members as $member) {
                if ($member->visibility === 'private') {
                    $private[] = $member;
                }
            }
        }

        return $private;
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
        $html .= $this->signature->classSignature($services, $classLike, $context);
        $html .= $this->docText->render($services, $classLike->docBlock, $context);
        $html .= $this->aliasSection($services, $pagePath, $classLike, $context);
        $html .= $this->member->tagExamples($services, $classLike->docBlock);
        $html .= $this->memberSections($services, $pagePath, $classLike, $context);
        $html .= $this->privateSurface($services, $classLike, $context);
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
            '<section><h2 id="test-cases">Test cases <span class="count">%d</span><a class="anchor" href="#test-cases">§</a></h2>',
            count($testCases),
        )
            . '<p class="section-note">Test cases that cover or call this symbol, from the coverage report and from the analyzed test sources.</p>'
            . $this->testCaseHtml->subSection($services, $pagePath, 'Dedicated tests', $dedicated)
            . $this->testCaseHtml->subSection($services, $pagePath, 'Other tests reaching this symbol', $others)
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

        $html = '<section><h2 id="aliases">Type Aliases<a class="anchor" href="#aliases">§</a></h2>';
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
            $html .= '<section><h2 id="cases">Cases<a class="anchor" href="#cases">§</a></h2>';
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
            $html .= '<section><h2 id="constants">Constants<a class="anchor" href="#constants">§</a></h2>';
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
            $html .= '<section><h2 id="properties">Properties<a class="anchor" href="#properties">§</a></h2>';
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

        $html = '<section><h2 id="methods">Methods<a class="anchor" href="#methods">§</a></h2>';
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
     * Renders the private members as their own collapsed section.
     *
     * The section stands on its own rather than trailing the last public
     * member, so the signatures are never read as part of it.
     */
    public function privateSurface(RenderKit $services, ClassLikeDoc $classLike, TypeRenderContext $context): string
    {
        $rows = [];
        foreach ($classLike->constants as $constant) {
            if ($constant->visibility === 'private') {
                $rows[] = $this->signature->constantSignature($services, $constant, $context);
            }
        }

        foreach ($classLike->properties as $property) {
            if ($property->visibility === 'private') {
                $rows[] = $this->signature->propertySignature($services, $property, $context);
            }
        }

        foreach ($classLike->methods as $method) {
            if ($method->visibility === 'private') {
                $rows[] = $this->signature->methodSignature($services, $method, $context);
            }
        }

        if ($rows === []) {
            return '';
        }

        $html = '<section class="private-surface"><h2 id="private-surface">Private surface'
            . sprintf(' <span class="count">%d</span>', count($rows))
            . '<a class="anchor" href="#private-surface">§</a></h2>'
            . '<p class="section-note">Implementation details, listed for orientation only.</p>';
        foreach ($rows as $row) {
            $html .= '<pre class="member-sig private-sig"><code>' . $row . '</code></pre>';
        }

        return $html . '</section>' . "\n";
    }
}
