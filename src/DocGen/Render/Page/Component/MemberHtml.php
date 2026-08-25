<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function in_array;
use function sprintf;

use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\EnumCaseDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\PropertyDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * Renders one member section of a class-like page.
 *
 * A member states what it is (signature and documented types), what
 * guarantees it (test cases), what depends on it (incoming calls) and what
 * it depends on (outgoing calls), in that order.
 */
final class MemberHtml
{
    /**
     * Reference kinds that count as a call of the member.
     *
     * @var list<string>
     */
    public const CALL_KINDS = ['method-call', 'static-call'];

    /** @readonly */
    private SignatureHtml $signature;

    /** @readonly */
    private DocTextHtml $docText;

    /** @readonly */
    private ExampleHtml $example;

    /** @readonly */
    private UsageListHtml $usageList;

    /** @readonly */
    private TestCaseHtml $testCases;

    /**
     * Creates a member renderer from its section collaborators.
     */
    public function __construct(
        ?SignatureHtml $signature = null,
        ?DocTextHtml $docText = null,
        ?ExampleHtml $example = null,
        ?UsageListHtml $usageList = null,
        ?TestCaseHtml $testCases = null,
    ) {
        $this->signature = $signature ?? new SignatureHtml();
        $this->docText = $docText ?? new DocTextHtml();
        $this->example = $example ?? new ExampleHtml();
        $this->usageList = $usageList ?? new UsageListHtml();
        $this->testCases = $testCases ?? new TestCaseHtml();
    }

    /**
     * Renders one method section with docs, guarantees, and call sites.
     */
    public function method(RenderKit $services, string $pagePath, ClassLikeDoc $owner, MethodDoc $method, TypeRenderContext $context): string
    {
        $anchor = 'method.' . $method->name;
        $key = $services->diff->methodKey($owner->fqcn, $method->name);
        $html = sprintf('<div class="member"%s id="%s">', $services->diff->attribute($key), $services->escaper->e($anchor));
        $html .= '<div class="member-head">';
        $html .= '<pre class="member-sig"><code>' . $this->signature->methodSignature($services, $method, $context, $key) . '</code></pre>';
        $html .= $this->meta($services, $pagePath, $owner->file, $method->startLine, $method->endLine, $anchor);
        $html .= '</div>';
        $html .= '<div class="member-body">';
        $html .= $this->docText->render($services, $method->docBlock, $context, sprintf('%s::%s()', $owner->shortName, $method->name));
        $html .= $this->paramTable($services, $method, $context, $key);
        $html .= $this->tagExamples($services, $method->docBlock, sprintf('%s::%s()', $owner->shortName, $method->name));
        $html .= $this->testCases->section($services, $pagePath, $services->model->testCases->forMember($owner->fqcn, $method->name));
        $html .= $this->usageList->section($services, $pagePath, 'Called from', $this->callers($services, $owner, $method), false);
        $html .= $this->usageList->callSection($services, $pagePath, 'Calls', $services->model->usages->callsFrom($owner->fqcn, $method->name));

        return $html . '</div></div>' . "\n";
    }

    /**
     * Collects the production call sites of one method.
     *
     * @return list<Usage>
     */
    public function callers(RenderKit $services, ClassLikeDoc $owner, MethodDoc $method): array
    {
        $callers = [];
        foreach ($services->model->usages->forMember($owner->fqcn, $method->name, false) as $usage) {
            if (in_array($usage->kind, self::CALL_KINDS, true)) {
                $callers[] = $usage;
            }
        }

        return $callers;
    }

    /**
     * Renders one property section.
     */
    public function property(RenderKit $services, string $pagePath, ClassLikeDoc $owner, PropertyDoc $property, TypeRenderContext $context): string
    {
        $anchor = 'property.' . $property->name;
        $html = sprintf('<div class="member"%s id="%s">', $services->diff->property($owner->fqcn, $property->name), $services->escaper->e($anchor));
        $html .= '<div class="member-head">';
        $html .= '<pre class="member-sig"><code>' . $this->signature->propertySignature($services, $property, $context) . '</code></pre>';
        $html .= $this->meta($services, $pagePath, $owner->file, $property->line, $property->line, $anchor);
        $html .= '</div><div class="member-body">';
        $html .= $this->docText->render($services, $property->docBlock, $context);

        return $html . '</div></div>' . "\n";
    }

    /**
     * Renders one constant section.
     */
    public function constant(RenderKit $services, string $pagePath, ClassLikeDoc $owner, ConstantDoc $constant, TypeRenderContext $context): string
    {
        $anchor = 'constant.' . $constant->name;
        $html = sprintf('<div class="member"%s id="%s">', $services->diff->constant($owner->fqcn, $constant->name), $services->escaper->e($anchor));
        $html .= '<div class="member-head">';
        $html .= '<pre class="member-sig"><code>' . $this->signature->constantSignature($services, $constant, $context) . '</code></pre>';
        $html .= $this->meta($services, $pagePath, $owner->file, $constant->line, $constant->line, $anchor);
        $html .= '</div><div class="member-body">';
        $html .= $this->docText->render($services, $constant->docBlock, $context);

        return $html . '</div></div>' . "\n";
    }

    /**
     * Renders one enum case section.
     */
    public function enumCase(RenderKit $services, string $pagePath, ClassLikeDoc $owner, EnumCaseDoc $case, TypeRenderContext $context): string
    {
        $anchor = 'case.' . $case->name;
        $html = sprintf('<div class="member"%s id="%s">', $services->diff->enumCase($owner->fqcn, $case->name), $services->escaper->e($anchor));
        $html .= '<div class="member-head">';
        $html .= '<pre class="member-sig"><code>' . $this->signature->caseSignature($services, $case) . '</code></pre>';
        $html .= $this->meta($services, $pagePath, $owner->file, $case->line, $case->line, $anchor);
        $html .= '</div><div class="member-body">';
        $html .= $this->docText->render($services, $case->docBlock, $context);

        return $html . '</div></div>' . "\n";
    }

    /**
     * Renders the coverage figure, source link, and anchor of a member.
     *
     * The coverage chip is omitted when no coverage report was loaded, so a
     * missing report never reads as untested code.
     */
    public function meta(RenderKit $services, string $pagePath, string $file, int $startLine, int $endLine, string $anchor): string
    {
        $html = '<div class="member-meta">';
        $coverage = $services->model->coverage;
        if ($coverage !== null) {
            $method = $coverage->methodAt($file, $startLine, $endLine);
            if ($method !== null) {
                $level = $method->percent >= 90.0 ? 'high' : ($method->percent >= 50.0 ? 'mid' : 'low');
                $html .= sprintf(
                    '<span class="chip chip-sm chip-cov-%s" title="%d of %d executable lines executed by the test suite">%.0f%%</span>',
                    $level,
                    $method->executed,
                    $method->executable,
                    $method->percent,
                );
            }
        }

        $html .= sprintf(
            '<a class="src-link" href="%s">source</a><a class="anchor" href="#%s">§</a>',
            $services->escaper->e($services->url->href($pagePath, $services->url->sourcePage($file)) . '#L' . $startLine),
            $services->escaper->e($anchor),
        );

        return $html . '</div>';
    }

    /**
     * Renders the parameter and return type table of one method.
     *
     * Every parameter is listed with its documented type, whether or not the
     * PHPDoc adds prose, because the type itself is documentation.
     *
     * @param string $ownerKey the diff key the parameter states are under
     */
    public function paramTable(RenderKit $services, MethodDoc $method, TypeRenderContext $context, string $ownerKey = ''): string
    {
        return $this->signatureTable($services, $method->parameters, $method->returnType, $method->docBlock, $context, $ownerKey);
    }

    /**
     * Renders the parameters, return, and throws of a callable declaration.
     *
     * Each of the three carries a different question, so each gets its own
     * labeled block instead of one merged table. Functions and methods
     * share the rendering, so both read the same way.
     *
     * @param list<ParameterDoc> $parameters
     * @param string $ownerKey the diff key the parameter states are under
     */
    public function signatureTable(RenderKit $services, array $parameters, TypeSignature $returnType, ?DocBlock $docBlock, TypeRenderContext $context, string $ownerKey = ''): string
    {
        return $this->parameterSection($services, $parameters, $context, $ownerKey)
            . $this->returnSection($services, $returnType, $context, $ownerKey)
            . $this->throwsSection($services, $docBlock, $context, $ownerKey);
    }

    /**
     * Renders the parameter block of a callable declaration.
     *
     * @param list<ParameterDoc> $parameters
     * @param string $ownerKey the diff key the parameter states are under
     */
    public function parameterSection(RenderKit $services, array $parameters, TypeRenderContext $context, string $ownerKey = ''): string
    {
        if ($parameters === []) {
            return '';
        }

        $rows = '';
        $statuses = [];
        foreach ($parameters as $parameter) {
            $annotated = $parameter->type->annotated;
            $statuses[] = $ownerKey === '' ? DiffStatus::SAME : $services->diff->parameterStatus($ownerKey, $parameter->name);
            $rows .= sprintf(
                '<tr%s><td><code class="t-var">$%s</code></td><td><code>%s</code></td><td>%s</td></tr>',
                $ownerKey === '' ? '' : $services->diff->parameter($ownerKey, $parameter->name),
                $services->escaper->e($parameter->name),
                $services->typeHtml->render($annotated !== null ? $annotated->type : null, $parameter->type->native, $context),
                $services->escaper->e($parameter->description),
            );
        }

        return $this->block(
            'Parameters',
            '<div class="table-wrap"><table class="param-table">' . $rows . '</table></div>',
            $ownerKey === '' ? '' : $services->diff->combined($statuses),
        );
    }

    /**
     * Renders the return block, or nothing for a void declaration.
     *
     * @param string $ownerKey the diff key of the declaration
     */
    public function returnSection(RenderKit $services, TypeSignature $returnType, TypeRenderContext $context, string $ownerKey = ''): string
    {
        $annotated = $returnType->annotated;
        $type = $services->typeHtml->render($annotated !== null ? $annotated->type : null, $returnType->native, $context);
        if ($type === '' || $returnType->native === 'void') {
            return '';
        }

        $description = $annotated !== null ? $annotated->description : '';

        return $this->block('Returns', sprintf(
            '<div class="type-row"><code>%s</code>%s</div>',
            $type,
            $description !== '' ? ' <span class="type-note">' . $services->escaper->e($description) . '</span>' : '',
        ), $ownerKey === '' ? '' : $services->diff->returnType($ownerKey));
    }

    /**
     * Renders the throws block of a member.
     *
     * @param string $ownerKey the diff key of the declaration
     */
    public function throwsSection(RenderKit $services, ?DocBlock $docBlock, TypeRenderContext $context, string $ownerKey = ''): string
    {
        if ($docBlock === null || $docBlock->throws === []) {
            return '';
        }

        $rows = '';
        foreach ($docBlock->throws as $tag) {
            $rows .= sprintf(
                '<div class="type-row"><code>%s</code>%s</div>',
                $tag->type !== null ? $services->typeHtml->node($tag->type, $context) : '',
                $tag->description !== '' ? ' <span class="type-note">' . $services->escaper->e($tag->description) . '</span>' : '',
            );
        }

        return $this->block('Throws', $rows, $ownerKey === '' ? '' : $services->diff->throwsTags($ownerKey));
    }

    /**
     * Wraps one labeled block of a member body.
     *
     * @param string $attribute the diff attribute of the block, if any
     */
    public function block(string $label, string $body, string $attribute = ''): string
    {
        return sprintf('<div class="member-block"%s><h4>%s</h4>%s</div>', $attribute, (new HtmlText())->e($label), $body) . "\n";
    }

    /**
     * Renders the at-example doctests of a member docblock.
     *
     * @param string $symbol the unqualified target name doctest names the examples after, empty when unknown
     */
    public function tagExamples(RenderKit $services, ?DocBlock $docBlock, string $symbol = ''): string
    {
        if ($docBlock === null) {
            return '';
        }

        $html = '';
        foreach ($services->doctest->extract($docBlock->raw) as $example) {
            if ($example->source !== 'fence') {
                $name = $symbol === '' ? '' : $this->example->exampleName($symbol, $example->description, $example->index);
                $html .= $this->example->figure($services, $example->description, $example->code, $example->source === 'tag', $name);
            }
        }

        return $html;
    }
}
