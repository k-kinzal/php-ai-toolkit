<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Guard\Cli\FormatDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Cli\FormatDetector
 */
#[CoversClass(FormatDetector::class)]

final class FormatDetectorTest extends TestCase
{
    public function testDetectRecognizesAnAgentMarkerAndRestoresTheEnvironment(): void
    {
        $before = getenv('AI_AGENT');
        try {
            putenv('AI_AGENT=guard-test');
            self::assertSame('ai', (new FormatDetector())->detect());
        } finally {
            putenv($before === false ? 'AI_AGENT' : 'AI_AGENT=' . $before);
        }
    }



}
