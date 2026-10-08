<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

/**
 * @covers \Guard\Cli\FormatDetector
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Cli\FormatDetector::class)]

final class FormatDetectorTest extends \PHPUnit\Framework\TestCase
{
    public function testDetectRecognizesAnAgentMarkerAndRestoresTheEnvironment(): void
    {
        $before = getenv('AI_AGENT');
        try {
            putenv('AI_AGENT=guard-test');
            self::assertSame('ai', (new \Guard\Cli\FormatDetector())->detect());
        } finally {
            putenv($before === false ? 'AI_AGENT' : 'AI_AGENT=' . $before);
        }
    }



}
