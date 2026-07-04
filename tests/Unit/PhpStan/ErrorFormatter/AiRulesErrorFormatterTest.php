<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use function dirname;

use Override;
use PhpAiToolkit\PhpStan\ErrorFormatter\AiRulesErrorFormatter;
use PhpAiToolkit\Shared\AgentDetector;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function putenv;

#[CoversClass(AiRulesErrorFormatter::class)]
final class AiRulesErrorFormatterTest extends ErrorFormatterTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        putenv('AI_AGENT');
        putenv('CLAUDE_CODE');
        putenv('CLAUDECODE');
        putenv('CURSOR_TRACE_ID');
        putenv('CURSOR_AGENT');
        putenv('GEMINI_CLI');
        putenv('CODEX_SANDBOX');
        putenv('AUGMENT_AGENT');
        putenv('OPENCODE');
        putenv('DEVIN');
        putenv('WINDSURF_SESSION_ID');
        putenv('AIDER');
        putenv('CLINE');
        putenv('CONTINUE_GLOBAL_DIR');
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('AI_AGENT');
        putenv('CLAUDE_CODE');
        putenv('CLAUDECODE');
        putenv('CURSOR_TRACE_ID');
        putenv('CURSOR_AGENT');
        putenv('GEMINI_CLI');
        putenv('CODEX_SANDBOX');
        putenv('AUGMENT_AGENT');
        putenv('OPENCODE');
        putenv('DEVIN');
        putenv('WINDSURF_SESSION_ID');
        putenv('AIDER');
        putenv('CLINE');
        putenv('CONTINUE_GLOBAL_DIR');
        parent::tearDown();
    }

    public function testFormatErrorsNoErrorsHumanMode(): void
    {
        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $result = $formatter->formatErrors(
            new AnalysisResult([], [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        self::assertSame(0, $result);
        self::assertStringContainsString('No errors', $this->getOutputContent());
    }

    public function testFormatErrorsNoErrorsAiMode(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $result = $formatter->formatErrors(
            new AnalysisResult([], [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        self::assertSame(0, $result);
        self::assertStringContainsString('No errors', $this->getOutputContent());
    }

    public function testFormatErrorsHumanModeShowsCodeContextAndIdentifier(): void
    {
        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';
        $errors = [
            new Error(
                'Test class has a property declaration.',
                $file,
                9,
                true,
                null,
                null,
                'Remove the property and use local variables in each test method.',
                null,
                null,
                'customRules.testClassProperty',
            ),
        ];
        $formatter->formatErrors(
            new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        $content = $this->getOutputContent();
        self::assertStringContainsString('SampleSource.php', $content);
        self::assertStringContainsString('private string $name', $content);
        self::assertStringContainsString('^^^^', $content);
        self::assertStringContainsString('customRules.testClassProperty', $content);
        self::assertStringContainsString('Tip:', $content);
        self::assertStringContainsString('Remove the property', $content);
    }

    public function testFormatErrorsHumanModeShowsErrorCountSummary(): void
    {
        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';
        $errors = [
            new Error('Error one.', $file, 9, true, null, null, null, null, null, 'customRules.a'),
            new Error('Error two.', $file, 11, true, null, null, null, null, null, 'customRules.b'),
        ];
        $formatter->formatErrors(
            new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        self::assertStringContainsString('Found 2 errors', $this->getOutputContent());
    }

    public function testFormatErrorsAiModeFlatFormatForFewErrors(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';
        $errors = [
            new Error(
                'Test class has a property declaration.',
                $file,
                9,
                true,
                null,
                null,
                'Remove the property and use local variables in each test method.',
                null,
                null,
                'customRules.testClassProperty',
            ),
            new Error(
                'Method getName() has no return type.',
                $file,
                11,
                true,
                null,
                null,
                null,
                null,
                null,
                'customRules.missingReturnType',
            ),
        ];
        $result = $formatter->formatErrors(
            new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        self::assertSame(1, $result);
        $content = $this->getOutputContent();
        self::assertStringContainsString('--- 2 errors in 1 file ---', $content);
        self::assertStringContainsString('[customRules.testClassProperty]', $content);
        self::assertStringContainsString('> private string $name;', $content);
        self::assertStringContainsString('Tip: Remove the property', $content);
    }

    public function testFormatErrorsAiModeDeduplicatesWhenThresholdMet(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';
        $errors = [
            new Error('Property.', $file, 9, true, null, null, 'Remove it.', null, null, 'customRules.testClassProperty'),
            new Error('Property.', $file, 10, true, null, null, 'Remove it.', null, null, 'customRules.testClassProperty'),
            new Error('Property.', $file, 11, true, null, null, 'Remove it.', null, null, 'customRules.testClassProperty'),
        ];
        $formatter->formatErrors(
            new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        self::assertStringContainsString('[customRules.testClassProperty] 3 occurrences:', $this->getOutputContent());
    }

    public function testFormatErrorsAiModeHandlesNotFileSpecificErrors(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $formatter->formatErrors(
            new AnalysisResult([], ['Autoloading error'], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        $content = $this->getOutputContent();
        self::assertStringContainsString('[general]', $content);
        self::assertStringContainsString('Autoloading error', $content);
    }

    public function testFormatErrorsHumanModeHandlesWarnings(): void
    {
        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $formatter->formatErrors(
            new AnalysisResult([], [], [], ['Deprecated config'], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        $content = $this->getOutputContent();
        self::assertStringContainsString('Warning:', $content);
        self::assertStringContainsString('Deprecated config', $content);
    }

    public function testFormatErrorsAiModeOmitsTipWhenNull(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';
        $errors = [
            new Error('Some error.', $file, 9, true, null, null, null, null, null, 'customRules.someError'),
        ];
        $formatter->formatErrors(
            new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        );
        $content = $this->getOutputContent();
        self::assertStringContainsString('Some error.', $content);
        self::assertStringNotContainsString('Tip:', $content);
    }

    public function testFormatErrorsReturnValues(): void
    {
        $formatter = new AiRulesErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new AgentDetector(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';
        $errors = [new Error('E.', $file, 9, true, null, null, null, null, null, 'customRules.a')];

        self::assertSame(1, $formatter->formatErrors(
            new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        ));
        self::assertSame(0, $formatter->formatErrors(
            new AnalysisResult([], [], [], [], [], false, null, true, 0, false, []),
            $this->getOutput(),
        ));
    }
}
