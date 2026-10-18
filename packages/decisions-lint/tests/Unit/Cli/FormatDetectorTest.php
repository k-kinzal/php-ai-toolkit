<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use DecisionsLint\Cli\FormatDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Cli\FormatDetector
 */
#[CoversClass(FormatDetector::class)]
final class FormatDetectorTest extends TestCase
{
    public function testDetectRecognisesAnAgentMarkerAndRestoresTheEnvironment(): void
    {
        $before = getenv('AI_AGENT');
        try {
            putenv('AI_AGENT=decisions-test');
            self::assertSame('ai', (new FormatDetector())->detect());
        } finally {
            putenv($before === false ? 'AI_AGENT' : 'AI_AGENT=' . $before);
        }
    }

    public function testDetectIgnoresAnEmptyGenericMarker(): void
    {
        $before = getenv('AI_AGENT');
        try {
            putenv('AI_AGENT=');
            self::assertContains((new FormatDetector())->detect(), ['ai', 'text']);
        } finally {
            putenv($before === false ? 'AI_AGENT' : 'AI_AGENT=' . $before);
        }
    }
}
