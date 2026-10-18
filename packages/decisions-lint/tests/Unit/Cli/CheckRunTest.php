<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use DecisionsLint\Cli\CheckRun;
use DecisionsLint\Config\Configuration;
use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\DocumentMerger;
use DecisionsLint\Config\ImportResolver;
use DecisionsLint\Config\ProviderReader;
use DecisionsLint\Config\RuleReader;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Decision\Answer;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Decision\Verdict;
use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Diagnostic\Severity;
use DecisionsLint\Http\CurlTransport;
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Linter;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Lint\Report;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeJevTransport;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderFactory;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Reporting\AiReporter;
use DecisionsLint\Reporting\JsonReporter;
use DecisionsLint\Reporting\ReporterFactory;
use DecisionsLint\Reporting\Summary;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\QuestionBuilder;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use DecisionsLint\Rule\ViolationMatcher;
use DecisionsLint\Source\Declaration;
use DecisionsLint\Source\DeclarationScanner;
use DecisionsLint\Source\FileFinder;
use DecisionsLint\Source\GlobMatcher;
use DecisionsLint\Source\Target;
use DecisionsLint\Source\TargetExtractor;
use DecisionsLint\Source\Tokens;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \DecisionsLint\Cli\CheckRun
 * @uses \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Config\ConfigurationLoader
 * @uses \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\DocumentMerger
 * @uses \DecisionsLint\Config\ImportResolver
 * @uses \DecisionsLint\Config\ProviderReader
 * @uses \DecisionsLint\Config\RuleReader
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Decision\Answer
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Decision\Verdict
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Diagnostic\Severity
 * @uses \DecisionsLint\Http\CurlTransport
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Lint\Linter
 * @uses \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Lint\Planner
 * @uses \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeJevTransport
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Provider\Jev\JevDecision
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderFactory
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Reporting\AiReporter
 * @uses \DecisionsLint\Reporting\JsonReporter
 * @uses \DecisionsLint\Reporting\ReporterFactory
 * @uses \DecisionsLint\Reporting\Summary
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\QuestionBuilder
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 * @uses \DecisionsLint\Rule\ViolationMatcher
 * @uses \DecisionsLint\Source\Declaration
 * @uses \DecisionsLint\Source\DeclarationScanner
 * @uses \DecisionsLint\Source\FileFinder
 * @uses \DecisionsLint\Source\GlobMatcher
 * @uses \DecisionsLint\Source\Target
 * @uses \DecisionsLint\Source\TargetExtractor
 * @uses \DecisionsLint\Source\Tokens
 */
#[CoversClass(CheckRun::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DocumentLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(ProviderReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(Answer::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(Verdict::class)]
#[UsesClass(Finding::class)]
#[UsesClass(LintException::class)]
#[UsesClass(Severity::class)]
#[UsesClass(CurlTransport::class)]
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(Linter::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Planner::class)]
#[UsesClass(Report::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeJevTransport::class)]
#[UsesClass(FakeTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JevClient::class)]
#[UsesClass(JevDecision::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderFactory::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(Summary::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(QuestionBuilder::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationMatcher::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationScanner::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(GlobMatcher::class)]
#[UsesClass(Target::class)]
#[UsesClass(TargetExtractor::class)]
#[UsesClass(Tokens::class)]
final class CheckRunTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRunLintsTheProjectThroughTheInjectedTransport(): void
    {
        $root = sys_get_temp_dir() . '/decisions-run-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Analyzer.php', "<?php\nnamespace App;\nclass Analyzer {\n    public function analyze(): void {}\n    public function sendMail(): void {}\n}\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nprovider: {name: jev, api_key_env: DECISIONS_RUN_KEY}\npaths: [src]\nrules:\n  cohesion.method:\n    severity: warning\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Does {name} belong to {class}?', threshold: 0.7}\n    violations: [{answer: false, message: '{name}() is misplaced in {class}.', fix: 'Move {name}().'}]\n");
        $transport = new FakeJevTransport(new AnswerScript(static fn (Question $question, array $subject): Answer => Answer::predicate($subject['name'] === 'App\Analyzer::sendMail' ? 0.1 : 0.95)));
        $before = getenv('DECISIONS_RUN_KEY');
        $output = new BufferedOutput();
        try {
            putenv('DECISIONS_RUN_KEY=secret');
            $run = new CheckRun($transport);
            self::assertSame(0, $run->run($root . '/decisions.yaml', 'json', [], [], null, $output));
            $json = $output->fetch();
            self::assertStringContainsString('"message": "sendMail() is misplaced in Analyzer."', $json);
            self::assertStringNotContainsString('analyze()', $json);
            self::assertStringContainsString('"line": 5', $json);
            self::assertStringContainsString('"requests": 2', $json);
            self::assertStringContainsString('"success": true', $json);
            self::assertSame(1, $run->run($root . '/decisions.yaml', 'ai', ['src/Analyzer.php'], ['cohesion.method'], 'warning', $output));
            self::assertStringContainsString('warning: src/Analyzer.php:5:5 [cohesion.method] App\Analyzer::sendMail', $output->fetch());
        } finally {
            putenv($before === false ? 'DECISIONS_RUN_KEY' : 'DECISIONS_RUN_KEY=' . $before);
            unlink($root . '/src/Analyzer.php');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/src');
            rmdir($root);
        }
    }

    public function testTransportFallsBackToCurlWithTheConfiguredLimits(): void
    {
        $configuration = new Configuration('/p/decisions.yaml', new ProviderSettings('jev', 'jev-latest', 'K', 'https://a.test', 5, 2), ['src'], [], 'error', []);
        self::assertInstanceOf(CurlTransport::class, (new CheckRun())->transport($configuration));
        $transport = new FakeTransport();
        self::assertSame($transport, (new CheckRun($transport))->transport($configuration));
    }

    public function testFailOnAcceptsSeveritiesAndNever(): void
    {
        $run = new CheckRun();
        self::assertSame('warning', $run->failOn('warning'));
        self::assertSame('never', $run->failOn('never'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Unsupported --fail-on "critical". Use --fail-on=error, warning, info or never.');
        $run->failOn('critical');
    }
}
