<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use JsonException;

/**
 * @covers \Guard\Reporting\RuleReporter
 * @uses \Guard\Reporting\RuleDescription
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Reporting\RuleReporter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\RuleDescription::class)]
final class RuleReporterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterSearchesMessagesAndPathsWithoutCaseSensitivity(): void
    {
        $a = new \Guard\Reporting\RuleDescription('a', 'app.json', 'required', true, 'Prevent drift.');
        $b = new \Guard\Reporting\RuleDescription('b', 'test.xml', 'recommended', false, 'Catch leaks.');
        $reporter = new \Guard\Reporting\RuleReporter();
        self::assertSame([$a], $reporter->filter([$a, $b], 'DRIFT'));
        self::assertSame([$b], $reporter->filter([$a, $b], 'test.xml'));
        self::assertSame([], $reporter->filter([$a, $b], 'missing'));
        self::assertSame([$a, $b], $reporter->filter([$a, $b], ''));
    }

    /**
     * @throws JsonException
     */
    public function testRenderEscapesHumanMarkupAndKeepsJsonAndAiPlain(): void
    {
        $rule = new \Guard\Reporting\RuleDescription('x', '<info>file</info>', 'required', true, '<error>literal</error>');
        $reporter = new \Guard\Reporting\RuleReporter();
        self::assertStringContainsString('<error>literal</error>', $reporter->render([$rule], 'text'));
        self::assertStringNotContainsString("\033", $reporter->render([$rule], 'ai', true));
        $json = $reporter->render([$rule], 'json', true);
        self::assertJson($json);
        self::assertStringContainsString('"count": 1', $json);
        self::assertStringContainsString('"fixable": true', $json);
    }

    public function testRowsPreservesTheSelectedOrder(): void
    {
        $rule = new \Guard\Reporting\RuleDescription('id', 'target', 'required', false, 'Remove the unsupported entry.');
        self::assertSame([$rule->toArray()], (new \Guard\Reporting\RuleReporter())->rows([$rule]));
    }

}
