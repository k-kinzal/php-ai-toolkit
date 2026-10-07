<?php

declare(strict_types=1);

namespace Guard\Cli;

use Guard\Config\ConfigurationLoader;
use Guard\Execution\AtomicWriter;
use Guard\Execution\Context;
use Guard\Execution\Pipeline;
use Guard\Extension\Registry;
use Guard\Reporting\Reporter;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks a project against its policy and, when asked, repairs it.
 */
final class PolicyRun
{
    /**
     * Creates a run with the policies an extension registry provides, or the configured ones.
     */
    public function __construct(private ?Registry $registry = null)
    {
    }

    /**
     * Evaluates the policy file, writes the repairs a repairing run may make, and reports.
     *
     * A repairing run writes nothing while a required violation blocks it,
     * and a dry run writes nothing at all.
     *
     * @return int 1 when a required rule is violated, 0 otherwise
     * @throws RuntimeException when the policy or an input document is invalid
     * @throws JsonException when a JSON document is malformed
     * @throws NeonException when a NEON document is malformed
     */
    public function run(string $path, string $format, bool $repair, bool $dryRun, OutputInterface $output): int
    {
        $config = (new ConfigurationLoader())->load($path);
        $plan = (new Pipeline($this->registry))->run(new Context($config, $path, $repair));
        $reporter = new Reporter();
        $blocked = $reporter->hasErrors($plan->blockingFindings);
        $action = $dryRun ? 'would change' : 'changed';
        if ($repair && !$dryRun && !$blocked) {
            (new AtomicWriter())->apply($plan->changes);
        }
        if ($blocked) {
            $action = 'blocked';
        }
        $output->write($reporter->render($plan->findings, $plan->changes, $format, $action), false, OutputInterface::OUTPUT_RAW);

        return $reporter->hasErrors($plan->findings) ? 1 : 0;
    }
}
