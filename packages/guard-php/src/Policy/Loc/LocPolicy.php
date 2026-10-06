<?php

declare(strict_types=1);

namespace Guard\Policy\Loc;

use Guard\Collect\Php\PhpSources;
use Guard\Collect\Php\SourceMetrics;
use Guard\Collect\Subject;
use Guard\Config\Loc\LimitConfig;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Loc\Assignment\FilePolicyAssigner;
use Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder;
use Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder;
use Guard\Policy\Policy;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;

/**
 * Applies metric profiles to collected PHP metrics without accessing source files.
 */
final class LocPolicy implements Policy
{
    /**
     * Evaluates assignments and metric limits in the established file and rule order.
     * @throws PolicyException
     */
    public function evaluate(Subject $information, Context $context): Plan
    {
        if (!$information instanceof PhpSources) {
            throw new PolicyException('LocPolicy requires PhpSources. Register it for that information type.');
        }
        $config = $context->configuration->metrics;
        if ($config === null) {
            return new Plan([], []);
        }
        $findings = [];
        foreach ((new FilePolicyAssigner())->assign($config, $information->paths) as $assignment) {
            foreach ($this->violations($information->metrics[$assignment->path], $assignment->policy->limits, $assignment->policy->name) as $violation) {
                $findings[] = new Finding($violation->path, 'metrics.' . $violation->rule, 'required', $violation->message);
            }
        }
        return new Plan($findings, []);
    }
    /**
     * @return list<Violation>
     */
    public function violations(SourceMetrics $metrics, LimitConfig $limits, string $profile = 'standard'): array
    {
        if (!$metrics->readable) {
            return [];
        }
        return array_merge(
            (new FileMetricViolationBuilder())->violations($metrics->file, $limits, $profile),
            (new ClassLikeMetricViolationBuilder())->violations($metrics->file->path, $metrics->classes, $limits, $profile),
            (new FunctionMetricViolationBuilder())->violations($metrics->file->path, $metrics->functions, $limits, $profile),
        );
    }
}
