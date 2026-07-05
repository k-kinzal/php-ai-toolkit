<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use PhpAiToolkit\Shared\AgentDetector;
use PhpAiToolkit\Shared\FormatMode;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

/**
 * Selects the PHPStan error renderer for the current execution context.
 */
final class AiRulesErrorFormatter implements ErrorFormatter
{
    private readonly AiRulesHumanErrorFormatter $humanFormatter;

    private readonly AiRulesAiErrorFormatter $aiFormatter;

    /**
     * Creates the dual-mode formatter from path and agent detection services.
     */
    public function __construct(
        RelativePathHelper $relativePathHelper,
        private readonly AgentDetector $agentDetector,
        ?AiRulesHumanErrorFormatter $humanFormatter = null,
        ?AiRulesAiErrorFormatter $aiFormatter = null,
    ) {
        $sourceReader = new ErrorSourceReader();
        $gutter = new ErrorGutter();
        $grouping = new ErrorGrouping();
        $summary = new ErrorCollectionSummary();

        $this->humanFormatter = $humanFormatter ?? new AiRulesHumanErrorFormatter($relativePathHelper, $sourceReader, $gutter, $grouping, $summary);
        $this->aiFormatter = $aiFormatter ?? new AiRulesAiErrorFormatter($relativePathHelper, $sourceReader, $grouping, $summary);
    }

    /**
     * Formats analysis errors for either human or AI consumption.
     *
     * @return int 0 when no errors, 1 when errors exist
     */
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        if ($this->agentDetector->resolveMode() === FormatMode::AI) {
            return $this->aiFormatter->format($analysisResult, $output);
        }

        return $this->humanFormatter->format($analysisResult, $output);
    }
}
