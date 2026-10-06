<?php

declare(strict_types=1);

namespace Toolkit\Guard\Execution;

use Toolkit\DocGuard\Analysis\DocGuardAnalyzer;
use Toolkit\Guard\Config\Configuration;
use Toolkit\Guard\Reporting\Finding;
use Toolkit\LocGuard\Analysis\LocGuardAnalyzer;
use Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer;

/**
 * Runs the unchanged metric, directory and Markdown inspectors.
 */
final class SourceChecks
{
    /**
     * @return list<Finding>
     */
    public function check(Configuration $configuration): array
    {
        $findings = [];
        if ($configuration->metrics !== null) {
            foreach ((new LocGuardAnalyzer())->analyze($configuration->metrics)->violations as $violation) {
                $findings[] = new Finding($violation->path, 'metrics.' . $violation->rule, 'required', $violation->message);
            }
        }
        if ($configuration->structure !== null) {
            foreach ((new TreeGuardAnalyzer())->analyze($configuration->structure)->violations as $violation) {
                $findings[] = new Finding($violation->path, 'structure.' . $violation->rule, 'required', $violation->message);
            }
        }
        if ($configuration->documentation !== null) {
            foreach ((new DocGuardAnalyzer())->analyze($configuration->documentation)->violations as $violation) {
                $findings[] = new Finding($violation->path, 'documentation.' . $violation->rule, 'required', $violation->message);
            }
        }
        return $findings;
    }
}
