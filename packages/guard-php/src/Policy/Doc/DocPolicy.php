<?php

declare(strict_types=1);

namespace Guard\Policy\Doc;

use Guard\Collect\Markdown\MarkdownDocuments;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Subject;
use Guard\Config\Doc\DocumentationConfig;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Policy;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;

/**
 * Applies heading and declaration policies to a collected Markdown snapshot.
 */
final class DocPolicy implements Policy
{
    /**
     * Evaluates declared files first and undeclared files in scan-pattern order.
     * @throws PolicyException
     */
    public function evaluate(Subject $information, Context $context): Plan
    {
        if (!$information instanceof MarkdownDocuments) {
            throw new PolicyException('DocPolicy requires MarkdownDocuments. Register it for that information type.');
        }
        $config = $context->configuration->documentation;
        if ($config === null) {
            return new Plan([], []);
        }
        $violations = [];
        foreach ($config->documents as $document) {
            $headings = $information->headings[$document->path];
            if ($headings === null) {
                $violations[] = (new ViolationFactory())->missingDocument($document->path, $config->configName);
                continue;
            }
            $maxLevel = $document->maxLevel;
            $headings = array_values(array_filter($headings, static fn (Heading $heading): bool => $heading->level <= $maxLevel));
            $violations = array_merge($violations, (new HeadingStructureComparator())->compare($document, $headings, $config->configName));
        }
        $findings = [];
        foreach (array_merge($violations, $this->undeclared($information, $config)) as $violation) {
            $findings[] = new Finding($violation->path, 'documentation.' . $violation->rule, 'required', $violation->message);
        }
        return new Plan($findings, []);
    }
    /**
     * @return list<Violation>
     */
    public function undeclared(MarkdownDocuments $information, DocumentationConfig $config): array
    {
        $reported = array_fill_keys($information->excluded, true);
        foreach ($config->documents as $document) {
            $reported[$document->path] = true;
        }
        $violations = [];
        foreach ($information->discovered as $scan) {
            foreach ($scan['paths'] as $path) {
                if (!isset($reported[$path])) {
                    $reported[$path] = true;
                    $violations[] = (new ViolationFactory())->undeclaredDocument($path, $scan['pattern'], $config->configName);
                }
            }
        }
        return $violations;
    }
}
