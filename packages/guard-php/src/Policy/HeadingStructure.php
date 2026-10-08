<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Collect\Selection;
use Guard\Config\Value\DocumentationConfig;
use Guard\Config\Value\DocumentConfig;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Comparison\BadgeComparator;
use Guard\Policy\Comparison\HeadingStructureComparator;
use Guard\Policy\Comparison\OutlineComparator;
use Guard\Reporting\DocumentViolationFactory;
use Guard\Reporting\Finding;
use Guard\Reporting\HeadingViolation;
use Guard\Reporting\HeadingViolationFactory;
use Guard\Structure\Markdown\Badge\BadgeBlock;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use Guard\Structure\Text;
use JsonException;
use RuntimeException;

/**
 * Checks declared headings, outlines, badges, exact contents, and file declarations using shared structured inputs.
 */
final class HeadingStructure implements Policy
{
    /**
     * Creates the HeadingStructure with its declared dependencies.
     */
    public function __construct(private DocumentationConfig $config)
    {
    }
    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        $inputs = [];
        foreach ($this->config->documents as $index => $document) {
            $inputs['declared' . $index] = new Input(new Selection('files', [$document->path], [], '', false, ''), 'markdown.headings');
            if ($document->badges !== null) {
                $inputs['badges' . $index] = new Input(new Selection('files', [$document->path], [], '', false, ''), 'markdown.badges');
            }
            if ($document->content !== null) {
                $inputs['text' . $index] = new Input(new Selection('files', [$document->path], [], '', false, ''), 'text');
            }
        }
        $inputs['excluded'] = new Input(new Selection('patterns', $context->configuration->scope === null ? $this->config->exclude : [], [], '', false, ''), null);
        foreach ($this->config->scan as $index => $pattern) {
            $inputs['scan' . $index] = new Input(new Selection('patterns', [$pattern], [], '', false, ''), null);
        }
        return $inputs;
    }
    /** Checks declared files first, followed by undeclared files in pattern order.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $violations = [];
        foreach ($this->config->documents as $index => $document) {
            $violations = array_merge($violations, $this->document($index, $document, $inputs));
        }
        $findings = [];
        foreach (array_merge($violations, $this->undeclared($inputs)) as $violation) {
            $findings[] = new Finding($violation->path, 'documentation.' . $violation->rule, 'required', (new \Guard\Reporting\RuleMessages())->diagnostic('documentation.' . $violation->rule, $violation->message));
        }
        return new Plan($findings, []);
    }
    /** Checks one declared document against every check it declares.
     * @return list<HeadingViolation>
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function document(int $index, DocumentConfig $document, InputSet $inputs): array
    {
        $value = $inputs->get('declared' . $index)->files[$document->path] ?? null;
        if ($value === null) {
            return [];
        }
        if (!$value->file->entry->file) {
            return [(new HeadingViolationFactory())->missingDocument($document->path, $this->config->configName)];
        }
        if (!$value->readable) {
            throw new PolicyException('Cannot read Markdown document: ' . $value->file->path);
        }
        $list = $value->value();
        if (!$list instanceof HeadingList) {
            throw new PolicyException('HeadingStructure requires markdown.headings to return HeadingList. Register HeadingStructurer for that structure.');
        }
        $violations = [];
        if ($document->headings !== null) {
            $maxLevel = $document->maxLevel;
            $headings = array_values(array_filter($list->all(), static fn (Heading $heading): bool => $heading->level <= $maxLevel));
            $violations = (new HeadingStructureComparator())->compare($document, $headings, $this->config->configName);
        }
        if ($document->outlines !== []) {
            $violations = array_merge($violations, (new OutlineComparator())->compare($document, $list->all(), $this->config->configName));
        }
        if ($document->badges !== null) {
            $block = $inputs->get('badges' . $index)->files[$document->path]->value();
            if (!$block instanceof BadgeBlock) {
                throw new PolicyException('HeadingStructure requires markdown.badges to return BadgeBlock. Register BadgeStructurer for that structure.');
            }
            $violations = array_merge($violations, (new BadgeComparator())->compare($document->path, $document->badges, $block, $this->config->configName));
        }
        if ($document->content !== null) {
            $text = $inputs->get('text' . $index)->files[$document->path]->value();
            if (!$text instanceof Text) {
                throw new PolicyException('HeadingStructure requires text to return Text. Register TextStructurer for that structure.');
            }
            if ($text->content() !== $document->content) {
                $violations[] = (new DocumentViolationFactory())->unexpectedContent($document->path, $document->content, $this->config->configName);
            }
        }
        return $violations;
    }
    /**
     * @return list<HeadingViolation>
     */
    public function undeclared(InputSet $inputs): array
    {
        $reported = array_fill_keys(array_keys($inputs->get('excluded')->files), true);
        foreach ($this->config->documents as $document) {
            $reported[$document->path] = true;
        }
        $violations = [];
        foreach ($this->config->scan as $index => $pattern) {
            foreach ($inputs->get('scan' . $index)->files as $file) {
                $path = $file->file->relativePath;
                if (!isset($reported[$path])) {
                    $reported[$path] = true;
                    $violations[] = (new HeadingViolationFactory())->undeclaredDocument($path, $pattern, $this->config->configName);
                }
            }
        }
        return $violations;
    }
}
