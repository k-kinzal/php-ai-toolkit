<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page;

use function sprintf;

use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Render\Diff\DiffBanner;
use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SignatureHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolDescription;
use Toolkit\DocGen\Render\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Render\Page\Component\UsageListHtml;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * Renders the documentation page of one top-level function.
 */
final class FunctionPage
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
    private UsageListHtml $usageList;

    /** @readonly */
    private TestCaseHtml $testCaseHtml;

    /** @readonly */
    private DiffBanner $banner;

    /** @readonly */
    private SymbolDescription $descriptions;

    /**
     * Creates a function page renderer from its section collaborators.
     */
    public function __construct(
        ?PageChrome $chrome = null,
        ?SidebarHtml $sidebar = null,
        ?BreadcrumbHtml $breadcrumb = null,
        ?SignatureHtml $signature = null,
        ?DocTextHtml $docText = null,
        ?MemberHtml $member = null,
        ?UsageListHtml $usageList = null,
        ?TestCaseHtml $testCaseHtml = null,
        ?DiffBanner $banner = null,
        ?SymbolDescription $descriptions = null,
    ) {
        $this->chrome = $chrome ?? new PageChrome();
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->breadcrumb = $breadcrumb ?? new BreadcrumbHtml();
        $this->signature = $signature ?? new SignatureHtml();
        $this->docText = $docText ?? new DocTextHtml();
        $this->member = $member ?? new MemberHtml();
        $this->usageList = $usageList ?? new UsageListHtml();
        $this->testCaseHtml = $testCaseHtml ?? new TestCaseHtml();
        $this->banner = $banner ?? new DiffBanner();
        $this->descriptions = $descriptions ?? new SymbolDescription();
    }

    /**
     * Renders one complete function page document.
     */
    public function render(RenderKit $services, FunctionDoc $function): string
    {
        $pagePath = $services->url->functionPage($function);
        $templates = [];
        foreach ($function->docBlock !== null ? $function->docBlock->templates : [] as $template) {
            $templates[] = $template->name;
        }

        $context = new TypeRenderContext($pagePath, $function->namespace, $function->useMap, $templates, [], $services->model->symbolTable);
        $crumbs = [['label' => $function->packageName, 'path' => $services->url->packagePage($function->packageName)]];
        if ($function->namespace !== '') {
            $crumbs[] = ['label' => $function->namespace, 'path' => $services->url->namespacePage($function->packageName, $function->namespace)];
        }

        $crumbs[] = ['label' => $function->shortName . '()', 'path' => null];

        return $this->chrome->page(
            $services,
            $pagePath,
            $function->shortName,
            $this->descriptions->ofFunction($function),
            $this->breadcrumb->build($services, $pagePath, $crumbs),
            $this->sidebar->build($services, $pagePath, new SidebarScope($function->packageName, $function->namespace, $function->fqn, [])),
            $this->content($services, $pagePath, $function, $context),
        );
    }

    /**
     * Renders the page content of one function.
     */
    public function content(RenderKit $services, string $pagePath, FunctionDoc $function, TypeRenderContext $context): string
    {
        $escaper = $services->escaper;
        $html = '<div class="symbol-head">';
        $html .= sprintf('<h1><span class="chip chip-kind k-function">function</span>%s</h1>', $escaper->e($function->shortName));
        $html .= sprintf(
            '<div class="symbol-meta"><a class="src-link" href="%s">%s:%d</a></div>',
            $escaper->e($services->url->href($pagePath, $services->url->sourcePage($function->file)) . '#L' . $function->startLine),
            $escaper->e($function->file),
            $function->startLine,
        );
        $html .= '</div>' . "\n";
        $key = $services->diff->functionKey($function->fqn);
        $html .= $this->banner->render($services, $services->diff->statusOf($key));
        $html .= '<pre class="signature"' . $services->diff->attribute($key) . '><code>'
            . $this->signature->functionSignature($services, $function, $context, $key) . '</code></pre>' . "\n";
        $html .= $this->docText->render($services, $function->docBlock, $context, $function->shortName . '()');
        $html .= $this->member->signatureTable($services, $function->parameters, $function->returnType, $function->docBlock, $context, $key);
        $html .= $this->member->tagExamples($services, $function->docBlock, $function->shortName . '()');
        $html .= $this->testCaseHtml->section($services, $pagePath, $services->model->testCases->forType($function->fqn));

        return $html . $this->usageList->section($services, $pagePath, 'Called from', $services->model->usages->forType($function->fqn, false), false);
    }
}
