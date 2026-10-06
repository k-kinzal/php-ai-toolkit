<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Collect\Selection;
use Guard\Config\Value\DocumentationConfig;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Comparison\HeadingStructureComparator;
use Guard\Reporting\Finding;
use Guard\Reporting\HeadingViolation;
use Guard\Reporting\HeadingViolationFactory;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use JsonException;
use RuntimeException;

/**
 * Checks declared headings and file declarations using shared structured inputs.
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
        }
        $inputs['excluded'] = new Input(new Selection('patterns', $this->config->exclude, [], '', false, ''), null);
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
            $value = $inputs->get('declared' . $index)->files[$document->path];
            if (!$value->file->entry->file) {
                $violations[] = (new HeadingViolationFactory())->missingDocument($document->path, $this->config->configName);
                continue;
            }
            if (!$value->readable) {
                throw new PolicyException('Cannot read Markdown document: ' . $value->file->path);
            }
            $list = $value->value();
            if (!$list instanceof HeadingList) {
                throw new PolicyException('HeadingStructure requires markdown.headings to return HeadingList. Register HeadingStructurer for that structure.');
            }
            $maxLevel = $document->maxLevel;
            $headings = array_values(array_filter($list->all(), static fn (Heading $heading): bool => $heading->level <= $maxLevel));
            $violations = array_merge($violations, (new HeadingStructureComparator())->compare($document, $headings, $this->config->configName));
        }
        $findings = [];
        foreach (array_merge($violations, $this->undeclared($inputs)) as $violation) {
            $findings[] = new Finding($violation->path, 'documentation.' . $violation->rule, 'required', $violation->message);
        }
        return new Plan($findings, []);
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
