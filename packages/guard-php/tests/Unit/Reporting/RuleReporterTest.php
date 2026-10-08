<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use JsonException;

/**
 * @covers \Guard\Reporting\RuleReporter
 * @uses \Guard\Policy\Diagnostic\RuleDescription
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Reporting\RuleReporter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\RuleDescription::class)]
final class RuleReporterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilterSearchesMessagesAndPathsWithoutCaseSensitivity(): void
    {
        $a = new \Guard\Policy\Diagnostic\RuleDescription('a', 'app.json', 'required', true, 'Prevent drift.');
        $b = new \Guard\Policy\Diagnostic\RuleDescription('b', 'test.xml', 'recommended', false, 'Catch leaks.');
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
        $rule = new \Guard\Policy\Diagnostic\RuleDescription('x', '<info>file</info>', 'required', true, '<error>literal</error>');
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
        $rule = new \Guard\Policy\Diagnostic\RuleDescription('id', 'target', 'required', false, 'Remove the unsupported entry.');
        self::assertSame([$rule->toArray()], (new \Guard\Reporting\RuleReporter())->rows([$rule]));
    }

}
