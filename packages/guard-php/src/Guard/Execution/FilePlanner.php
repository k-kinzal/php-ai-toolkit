<?php

declare(strict_types=1);

namespace Toolkit\Guard\Execution;

use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;
use Toolkit\Guard\Document\DataDocument;
use Toolkit\Guard\Document\XmlDocument;
use Toolkit\Guard\Policy\PolicyException;
use Toolkit\Guard\Policy\Rule;
use Toolkit\Guard\Policy\RuleEvaluator;

/**
 * Plans all field edits in one document and rechecks overlapping constraints.
 */
final class FilePlanner
{
    /**
     * @param list<Rule> $rules
     * @throws PolicyException when a document cannot be read or rule formats conflict
     * @throws JsonException
     * @throws NeonException
     */
    public function plan(string $path, array $rules, bool $repair): Plan
    {
        try {
            if ($rules === []) {
                throw new PolicyException('A file plan requires at least one configuration rule.');
            }
            $source = file_get_contents($path);
            if ($source === false) {
                throw new PolicyException('Cannot read ' . $path . '. Check file permissions.');
            }
            $format = $rules[0]->format;
            $document = $format === 'xml' ? new XmlDocument($source) : new DataDocument($format, $source);
            $evaluator = new RuleEvaluator();
            $changed = false;
            foreach ($rules as $rule) {
                if ($rule->format !== $format) {
                    throw new PolicyException('Conflicting formats for ' . $rule->file . '. Use one format for all its rules.');
                }
                if ($repair && $rule->repairable && !$evaluator->accepts($rule, $document)) {
                    $document->write($rule->select, $rule->repair);
                    $changed = true;
                }
            }
            $findings = [];
            foreach ($rules as $rule) {
                if (!$evaluator->accepts($rule, $document)) {
                    $findings[] = $evaluator->finding($rule);
                }
            }
            return new Plan($findings, $changed ? [new FileChange($path, $source, $document->encode())] : []);
        } catch (JsonException $exception) {
            throw new JsonException('Invalid JSON value in ' . $path . '. Correct the document or repair value: ' . $exception->getMessage(), 0, $exception);
        } catch (NeonException $exception) {
            throw new NeonException('Invalid NEON value in ' . $path . '. Correct the document or repair value: ' . $exception->getMessage(), 0, $exception);
        } catch (RuntimeException $exception) {
            throw new PolicyException('Cannot process ' . $path . ': ' . $exception->getMessage(), 0, $exception);
        }
    }
}
