<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Cli;

use function sprintf;

use Toolkit\LocGuard\Analysis\FilePolicyAssigner;
use Toolkit\LocGuard\Config\ConfigLoader;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\LocGuardException;

/**
 * Explains which policy and limits apply to one configured source file.
 */
final class LocGuardExplainRunner
{
    /** @readonly */
    private LocGuardConfigPathResolver $configPathResolver;

    /** @readonly */
    private LocGuardPathResolver $pathResolver;

    /** @readonly */
    private PhpFileFinder $fileFinder;

    /** @readonly */
    private FilePolicyAssigner $policyAssigner;

    /** @readonly */
    private PolicyExplanationFormatter $formatter;

    /**
     * Creates an explain runner from configuration, discovery, and policy services.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        /** @readonly */
        private ConfigLoader $configLoader,
        /** @readonly */
        private LocGuardOutputWriter $writer,
        ?LocGuardConfigPathResolver $configPathResolver = null,
        ?LocGuardPathResolver $pathResolver = null,
        ?PhpFileFinder $fileFinder = null,
        ?FilePolicyAssigner $policyAssigner = null,
        ?PolicyExplanationFormatter $formatter = null,
    ) {
        $this->configPathResolver = $configPathResolver ?? new LocGuardConfigPathResolver();
        $this->pathResolver = $pathResolver ?? new LocGuardPathResolver();
        $this->fileFinder = $fileFinder ?? new PhpFileFinder();
        $this->policyAssigner = $policyAssigner ?? new FilePolicyAssigner();
        $this->formatter = $formatter ?? new PolicyExplanationFormatter();
    }

    /**
     * Loads configuration and prints the effective policy for one file.
     */
    public function run(string $configPath, string $explainPath): int
    {
        try {
            $config = $this->configLoader->load($this->configPathResolver->resolve($this->workingDirectory, $configPath));
            $target = $this->pathResolver->relative(
                $config->root,
                $this->pathResolver->absolute($config->root, $explainPath),
            );
            $assignments = $this->policyAssigner->assign($config, $this->fileFinder->find($config));
            foreach ($assignments as $assignment) {
                if ($assignment->relativePath === $target) {
                    $this->writer->write($this->formatter->format($assignment));

                    return 0;
                }
            }

            throw new LocGuardException(sprintf(
                'Explain path is not a scanned PHP file: %s. Check scan.roots and scan.exclude.',
                $explainPath,
            ));
        } catch (LocGuardException $exception) {
            $this->writer->writeError(sprintf("LocGuard error: %s\n", $exception->getMessage()));

            return 2;
        }
    }
}
