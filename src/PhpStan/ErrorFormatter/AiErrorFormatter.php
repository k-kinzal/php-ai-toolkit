<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use Toolkit\Shared\AgentDetector;
use Toolkit\Shared\FormatMode;

/**
 * Selects the PHPStan error renderer for the current execution context.
 */
final class AiErrorFormatter implements ErrorFormatter
{
    /** @readonly */
    private AiRulesHumanErrorFormatter $humanFormatter;

    /** @readonly */
    private AiRulesAiErrorFormatter $aiFormatter;

    /**
     * Creates the dual-mode formatter from path and agent detection services.
     */
    public function __construct(
        RelativePathHelper $relativePathHelper,
        /** @readonly */
        private AgentDetector $agentDetector,
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
