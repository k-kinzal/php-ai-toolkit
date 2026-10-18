<?php

declare(strict_types=1);

namespace Tests\Integration;

use DecisionsLint\Cli\Application;
use DecisionsLint\Cli\CheckRun;
use DecisionsLint\Cli\ClosureOutput;
use DecisionsLint\Cli\Command\CheckCommand;
use DecisionsLint\Cli\Command\InitCommand;
use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\Command\PlanCommand;
use DecisionsLint\Cli\Command\RulesCommand;
use DecisionsLint\Cli\Command\ValidateCommand;
use DecisionsLint\Cli\DecisionsConsole;
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
use DecisionsLint\Http\Transport;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Linter;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Lint\Report;
use DecisionsLint\Provider\Chat\ChatAnswerReader;
use DecisionsLint\Provider\Chat\ChatPrompt;
use DecisionsLint\Provider\Fake\AnswerScript;
use DecisionsLint\Provider\Fake\ChatRequestReader;
use DecisionsLint\Provider\Fake\FakeChatCompletionsTransport;
use DecisionsLint\Provider\Fake\FakeJevTransport;
use DecisionsLint\Provider\Fake\FakeOllamaTransport;
use DecisionsLint\Provider\Fake\FakeOpenAiTransport;
use DecisionsLint\Provider\Fake\FakeTransport;
use DecisionsLint\Provider\Fake\RequestBody;
use DecisionsLint\Provider\Jev\JevClient;
use DecisionsLint\Provider\Jev\JevDecision;
use DecisionsLint\Provider\JsonEndpoint;
use DecisionsLint\Provider\Ollama\OllamaClient;
use DecisionsLint\Provider\Ollama\OllamaDecision;
use DecisionsLint\Provider\OpenAi\InputRenderer;
use DecisionsLint\Provider\OpenAi\OpenAiClient;
use DecisionsLint\Provider\OpenAi\OpenAiDecision;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient;
use DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision;
use DecisionsLint\Provider\Payload;
use DecisionsLint\Provider\ProviderFactory;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Reporting\AiReporter;
use DecisionsLint\Reporting\CheckstyleReporter;
use DecisionsLint\Reporting\GithubReporter;
use DecisionsLint\Reporting\JsonReporter;
use DecisionsLint\Reporting\PlanReporter;
use DecisionsLint\Reporting\ReporterFactory;
use DecisionsLint\Reporting\RuleCatalogReporter;
use DecisionsLint\Reporting\Summary;
use DecisionsLint\Reporting\TextReporter;
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
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @covers \DecisionsLint\Cli\Application
 * @medium
 * @uses \DecisionsLint\Cli\CheckRun
 * @uses \DecisionsLint\Cli\ClosureOutput
 * @uses \DecisionsLint\Cli\Command\CheckCommand
 * @uses \DecisionsLint\Cli\Command\InitCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\Command\PlanCommand
 * @uses \DecisionsLint\Cli\Command\RulesCommand
 * @uses \DecisionsLint\Cli\Command\ValidateCommand
 * @uses \DecisionsLint\Cli\DecisionsConsole
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
 * @uses \DecisionsLint\Provider\Chat\ChatAnswerReader
 * @uses \DecisionsLint\Provider\Chat\ChatPrompt
 * @uses \DecisionsLint\Provider\Fake\AnswerScript
 * @uses \DecisionsLint\Provider\Fake\ChatRequestReader
 * @uses \DecisionsLint\Provider\Fake\FakeChatCompletionsTransport
 * @uses \DecisionsLint\Provider\Fake\FakeJevTransport
 * @uses \DecisionsLint\Provider\Fake\FakeOllamaTransport
 * @uses \DecisionsLint\Provider\Fake\FakeOpenAiTransport
 * @uses \DecisionsLint\Provider\Fake\FakeTransport
 * @uses \DecisionsLint\Provider\Fake\RequestBody
 * @uses \DecisionsLint\Provider\Jev\JevClient
 * @uses \DecisionsLint\Provider\Jev\JevDecision
 * @uses \DecisionsLint\Provider\JsonEndpoint
 * @uses \DecisionsLint\Provider\Ollama\OllamaClient
 * @uses \DecisionsLint\Provider\Ollama\OllamaDecision
 * @uses \DecisionsLint\Provider\OpenAi\InputRenderer
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiClient
 * @uses \DecisionsLint\Provider\OpenAi\OpenAiDecision
 * @uses \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsClient
 * @uses \DecisionsLint\Provider\OpenAiCompatible\ChatCompletionsDecision
 * @uses \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Provider\ProviderFactory
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Reporting\AiReporter
 * @uses \DecisionsLint\Reporting\CheckstyleReporter
 * @uses \DecisionsLint\Reporting\GithubReporter
 * @uses \DecisionsLint\Reporting\JsonReporter
 * @uses \DecisionsLint\Reporting\PlanReporter
 * @uses \DecisionsLint\Reporting\ReporterFactory
 * @uses \DecisionsLint\Reporting\RuleCatalogReporter
 * @uses \DecisionsLint\Reporting\Summary
 * @uses \DecisionsLint\Reporting\TextReporter
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
#[CoversClass(Application::class)]
#[Medium]
#[UsesClass(CheckRun::class)]
#[UsesClass(ClosureOutput::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(InitCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(PlanCommand::class)]
#[UsesClass(RulesCommand::class)]
#[UsesClass(ValidateCommand::class)]
#[UsesClass(DecisionsConsole::class)]
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
#[UsesClass(ChatAnswerReader::class)]
#[UsesClass(ChatPrompt::class)]
#[UsesClass(AnswerScript::class)]
#[UsesClass(ChatRequestReader::class)]
#[UsesClass(FakeChatCompletionsTransport::class)]
#[UsesClass(FakeJevTransport::class)]
#[UsesClass(FakeOllamaTransport::class)]
#[UsesClass(FakeOpenAiTransport::class)]
#[UsesClass(FakeTransport::class)]
#[UsesClass(RequestBody::class)]
#[UsesClass(JevClient::class)]
#[UsesClass(JevDecision::class)]
#[UsesClass(JsonEndpoint::class)]
#[UsesClass(OllamaClient::class)]
#[UsesClass(OllamaDecision::class)]
#[UsesClass(InputRenderer::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(OpenAiDecision::class)]
#[UsesClass(ChatCompletionsClient::class)]
#[UsesClass(ChatCompletionsDecision::class)]
#[UsesClass(Payload::class)]
#[UsesClass(ProviderFactory::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(CheckstyleReporter::class)]
#[UsesClass(GithubReporter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(PlanReporter::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(RuleCatalogReporter::class)]
#[UsesClass(Summary::class)]
#[UsesClass(TextReporter::class)]
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
final class BehaviorTest extends TestCase
{
    public function testRunLintsAProjectWithTheShippedRulesAgainstAFakeJev(): void
    {
        $project = new class ([
            'src/Analysis/Analyzer.php' => "<?php\nnamespace App\\Analysis;\n\nfinal class Analyzer\n{\n    public function analyze(string \$input): array\n    {\n        return [];\n    }\n\n    public function sendReport(string \$to): void\n    {\n    }\n}\n",
            'src/UseCase/PlaceOrder.php' => "<?php\nnamespace App\\UseCase;\n\nfinal class PlaceOrder\n{\n    public function handle(int \$total): bool\n    {\n        return \$total > 10000;\n    }\n}\n",
            'decisions.yaml' => "version: 1\nprovider:\n  name: jev\n  api_key_env: DECISIONS_BEHAVIOR_KEY\npaths: [src]\nimports:\n  - " . dirname(__DIR__, 2) . "/rules/cohesion.yaml\n  - " . dirname(__DIR__, 2) . "/rules/ddd.yaml\nrules:\n  cohesion.method-belongs-to-class: {severity: error}\n  cohesion.property-belongs-to-class: {enabled: false}\n",
        ]) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/decisions-behavior-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }
            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $transport = new FakeJevTransport(new AnswerScript(static function (Question $question, array $subject): Answer {
            if ($question->type === Question::PREDICATE) {
                return Answer::predicate(str_contains($subject['name'], 'sendReport') ? 0.05 : 0.97);
            }
            $choice = str_contains($subject['name'], 'handle') ? 'domain_rule' : 'orchestration';

            return Answer::choice($choice, 0.88, [$choice => 0.9, 'infrastructure' => 0.1]);
        }));
        $before = getenv('DECISIONS_BEHAVIOR_KEY');
        $output = '';
        $app = new Application($project->root, static function (string $text) use (&$output): void {
            $output .= $text;
        }, $transport);
        try {
            putenv('DECISIONS_BEHAVIOR_KEY=secret');
            self::assertSame(0, $app->run(['validate']));
            self::assertStringContainsString('decisions.yaml is valid: 4 rules, 3 enabled, provider jev (jev-latest).', $output);
            $output = '';
            self::assertSame(0, $app->run(['plan']));
            self::assertStringContainsString('src/Analysis/Analyzer.php:6:5 method App\Analysis\Analyzer::analyze (class) <- cohesion.method-belongs-to-class', $output);
            self::assertStringContainsString('src/UseCase/PlaceOrder.php:6:5 method App\UseCase\PlaceOrder::handle (class) <- cohesion.method-belongs-to-class, ddd.usecase-domain-leak', $output);
            self::assertStringContainsString('2 files, 3 targets, 3 requests, 4 questions. Nothing was sent.', $output);
            self::assertSame([], $transport->requests);
            $output = '';
            self::assertSame(1, $app->run(['check', '--format=json']));
            self::assertStringContainsString("\"path\": \"src/Analysis/Analyzer.php\",\n            \"line\": 11,\n            \"column\": 5,\n            \"rule\": \"cohesion.method-belongs-to-class\",\n            \"severity\": \"error\"", $output);
            self::assertStringContainsString("\"path\": \"src/UseCase/PlaceOrder.php\",\n            \"line\": 6,\n            \"column\": 5,\n            \"rule\": \"ddd.usecase-domain-leak\",\n            \"severity\": \"error\"", $output);
            self::assertStringContainsString('"message": "Method sendReport() does not belong to Analyzer: it is not part of the responsibility the class name promises."', $output);
            self::assertStringContainsString('"fix": "Move the rule into the entity, value object or domain service it describes, and have handle() call that method so the use case only orchestrates."', $output);
            self::assertStringContainsString('"requests": 3', $output);
            self::assertStringContainsString('"jev-1.13.0"', $output);
            self::assertCount(3, $transport->requests);
            self::assertStringContainsString('"class_source":"', $transport->requests[0]->body);
            $output = '';
            self::assertSame(0, $app->run(['check', '--format=checkstyle', '--rule=cohesion.method-belongs-to-class', 'src/UseCase']));
            self::assertStringContainsString('<checkstyle version="decisions-lint">', $output);
            self::assertStringNotContainsString('<error', $output);
            $output = '';
            self::assertSame(1, $app->run(['check', '--format=github', '--fail-on=warning']));
            self::assertStringContainsString('::error file=src/Analysis/Analyzer.php,line=11,col=5,title=cohesion.method-belongs-to-class::', $output);
            $output = '';
            self::assertSame(0, $app->run(['rules', '--format=json']));
            self::assertStringContainsString('"count": 4', $output);
            $output = '';
            $project->write('decisions.yaml', "version: 1\nrules:\n  ghost: {severity: error}\n");
            self::assertSame(2, $app->run(['check']));
            self::assertStringStartsWith('Decisions error: Rule "ghost" (decisions.yaml) has no target.', $output);
        } finally {
            putenv($before === false ? 'DECISIONS_BEHAVIOR_KEY' : 'DECISIONS_BEHAVIOR_KEY=' . $before);
            $project->remove();
        }
    }

    /**
     * @dataProvider providerFakeProviders
     */
    #[DataProvider('providerFakeProviders')]
    public function testRunReportsTheSameFindingWhicheverProviderAnswers(string $provider, Transport $transport, string $model): void
    {
        $root = sys_get_temp_dir() . '/decisions-provider-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/A.php', "<?php\nclass A {\n    public function doIt(): void {}\n    public function calculateTotal(): int { return 1; }\n}\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nprovider: {name: $provider, api_key_env: DECISIONS_PROVIDER_KEY}\nrules:\n  naming:\n    target: {kind: method}\n    context: declaration\n    decision: {type: score, instructions: 'Rate {name}.', levels: [bad, ok, good]}\n    violations: [{max_score: 0.5, message: 'Bad {name} ({score}).', fix: 'Rename {name}.'}]\n  leak:\n    target: {kind: method}\n    context: declaration\n    decision: {type: choice, instructions: 'Classify {name}.', choices: {fine: ok, leak: bad}}\n    violations: [{answer: leak, message: 'Leak in {name}.', fix: 'Move {name}.'}]\n");
        $before = getenv('DECISIONS_PROVIDER_KEY');
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        }, $transport);
        try {
            putenv('DECISIONS_PROVIDER_KEY=secret');
            self::assertSame(1, $app->run(['check', '--format=ai']));
            self::assertStringContainsString('error: src/A.php:3:5 [naming] A::doIt confidence=0.90 Bad doIt (0.00). Fix: Rename doIt.', $output);
            self::assertStringContainsString('error: src/A.php:3:5 [leak] A::doIt confidence=0.80 Leak in doIt. Fix: Move doIt.', $output);
            self::assertStringNotContainsString('calculateTotal', $output);
            self::assertStringContainsString('2 requests to ' . $model . '.', $output);
        } finally {
            putenv($before === false ? 'DECISIONS_PROVIDER_KEY' : 'DECISIONS_PROVIDER_KEY=' . $before);
            unlink($root . '/src/A.php');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/src');
            rmdir($root);
        }
    }

    /**
     * @return iterable<string, array{string, Transport, string}>
     */
    public static function providerFakeProviders(): iterable
    {
        $script = new AnswerScript(static function (Question $question, array $subject): ?Answer {
            $text = implode("\n", $subject);
            if (!str_contains($text, 'doIt')) {
                return null;
            }

            return $question->type === Question::SCORE ? Answer::score(0.0, 0.9, ['0' => 0.9]) : Answer::choice('leak', 0.8, ['leak' => 0.8]);
        });
        yield 'jev' => ['jev', new FakeJevTransport($script, 'jev-1.13.0'), 'jev-1.13.0'];
        yield 'openai' => ['openai', new FakeOpenAiTransport($script, 'gpt-6-luna'), 'gpt-6-luna'];
        yield 'ollama' => ['ollama', new FakeOllamaTransport($script, 'llama3.1:8b'), 'llama3.1:8b'];
        yield 'localai' => ['localai', new FakeChatCompletionsTransport($script, 'gpt-4'), 'gpt-4'];
        yield 'openai-compatible' => ['openai-compatible', new FakeChatCompletionsTransport($script, 'qwen'), 'qwen'];
    }

    public function testRunReportsProviderFailuresAndMissingKeys(): void
    {
        $root = sys_get_temp_dir() . '/decisions-openai-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/A.php', "<?php\nclass A {\n    public function f(): void {}\n}\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nprovider: {name: openai, api_key_env: DECISIONS_OPENAI_KEY}\nrules:\n  r:\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Fits {name}?'}\n    violations: [{answer: false, message: 'Bad {name}.', fix: 'Improve {name}.'}]\n");
        $transport = new FakeTransport([new HttpResponse(429, '{"error":{"message":"Rate limit reached"}}')]);
        $before = getenv('DECISIONS_OPENAI_KEY');
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        }, $transport);
        try {
            putenv('DECISIONS_OPENAI_KEY=secret');
            self::assertSame(2, $app->run(['check']));
            self::assertSame("Decisions error: openai returned HTTP 429 for POST https://api.openai.com/v1/decisions: Rate limit reached. Lower provider.concurrency in decisions.yaml or retry later.\n", $output);
            putenv('DECISIONS_OPENAI_KEY');
            $output = '';
            self::assertSame(2, $app->run(['check']));
            self::assertStringStartsWith('Decisions error: The DECISIONS_OPENAI_KEY environment variable is not set.', $output);
        } finally {
            putenv($before === false ? 'DECISIONS_OPENAI_KEY' : 'DECISIONS_OPENAI_KEY=' . $before);
            unlink($root . '/src/A.php');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/src');
            rmdir($root);
        }
    }

    public function testRunInitialisesAProjectThatThenValidates(): void
    {
        $root = sys_get_temp_dir() . '/decisions-init-flow-' . uniqid();
        mkdir($root . '/src', 0777, true);
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(0, $app->run(['init', '--provider=ollama']));
            self::assertStringContainsString('Wrote ' . $root . '/decisions.yaml.', $output);
            $output = '';
            self::assertSame(0, $app->run(['validate']));
            self::assertStringContainsString('decisions.yaml is valid: 5 rules, 5 enabled, provider ollama (llama3.1).', $output);
            $output = '';
            self::assertSame(0, $app->run(['plan', '--format=json']));
            self::assertStringContainsString('"requests": []', $output);
        } finally {
            unlink($root . '/decisions.yaml');
            rmdir($root . '/src');
            rmdir($root);
        }
    }
}
