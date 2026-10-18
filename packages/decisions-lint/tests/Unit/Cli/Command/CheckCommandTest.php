<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use DecisionsLint\Cli\CheckRun;
use DecisionsLint\Cli\Command\CheckCommand;
use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\FormatDetector;
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
use DecisionsLint\Http\HttpRequest;
use DecisionsLint\Http\HttpResponse;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Linter;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Lint\Report;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\FakeJevTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderFactory;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Reporting\JsonReporter;
use DecisionsLint\Reporting\ReporterFactory;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \DecisionsLint\Cli\Command\CheckCommand
 * @uses \DecisionsLint\Cli\CheckRun
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\FormatDetector
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
 * @uses \DecisionsLint\Http\HttpRequest
 * @uses \DecisionsLint\Http\HttpResponse
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Lint\Linter
 * @uses \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Lint\Planner
 * @uses \DecisionsLint\Lint\Report
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\FakeJevTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Provider\Jev\JevDecision
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderFactory
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Reporting\JsonReporter
 * @uses \DecisionsLint\Reporting\ReporterFactory
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
#[CoversClass(CheckCommand::class)]
#[UsesClass(CheckRun::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(FormatDetector::class)]
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
#[UsesClass(HttpRequest::class)]
#[UsesClass(HttpResponse::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(Linter::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Planner::class)]
#[UsesClass(Report::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(FakeJevTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JevClient::class)]
#[UsesClass(JevDecision::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderFactory::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(ReporterFactory::class)]
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
final class CheckCommandTest extends TestCase
{
    public function testConfigureDeclaresTheOptionsAndArguments(): void
    {
        $command = new CheckCommand('/project');
        self::assertSame('check', $command->getName());
        self::assertSame('c', $command->getDefinition()->getOption('config')->getShortcut());
        self::assertTrue($command->getDefinition()->getOption('rule')->isArray());
        self::assertTrue($command->getDefinition()->hasOption('fail-on'));
        self::assertTrue($command->getDefinition()->getArgument('paths')->isArray());
    }

    public function testExecuteRunsTheCheckAndRejectsAnEmptyFailOn(): void
    {
        $root = sys_get_temp_dir() . '/decisions-check-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/A.php', "<?php\nclass A {\n    public function f(): void {}\n}\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nprovider: {api_key_env: DECISIONS_CHECK_KEY}\nrules:\n  r:\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Q?'}\n    violations: [{answer: false, message: 'Bad {name}.', fix: 'Fix {name}.'}]\n");
        $transport = new FakeJevTransport(new AnswerScript(static fn (): Answer => Answer::predicate(0.0)));
        $before = getenv('DECISIONS_CHECK_KEY');
        try {
            putenv('DECISIONS_CHECK_KEY=secret');
            $tester = new CommandTester(new CheckCommand($root, $transport));
            self::assertSame(1, $tester->execute(['--format' => 'json']));
            self::assertStringContainsString('"message": "Bad f."', $tester->getDisplay());
            self::assertSame(0, $tester->execute(['--format' => 'json', '--fail-on' => 'never']));
            self::assertSame(LintCommand::INVALID, $tester->execute(['--fail-on' => '']));
            self::assertStringContainsString('Decisions error: Provide a severity for --fail-on', $tester->getDisplay());
        } finally {
            putenv($before === false ? 'DECISIONS_CHECK_KEY' : 'DECISIONS_CHECK_KEY=' . $before);
            unlink($root . '/src/A.php');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/src');
            rmdir($root);
        }
    }
}
