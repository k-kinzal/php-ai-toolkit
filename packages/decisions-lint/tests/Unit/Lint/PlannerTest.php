<?php

declare(strict_types=1);

namespace Tests\Unit\Lint;

use DecisionsLint\Config\Configuration;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\QuestionBuilder;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
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

/**
 * @covers \DecisionsLint\Lint\Planner
 * @uses \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\QuestionBuilder
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Source\Declaration
 * @uses \DecisionsLint\Source\DeclarationScanner
 * @uses \DecisionsLint\Source\FileFinder
 * @uses \DecisionsLint\Source\GlobMatcher
 * @uses \DecisionsLint\Source\Target
 * @uses \DecisionsLint\Source\TargetExtractor
 * @uses \DecisionsLint\Source\Tokens
 */
#[CoversClass(Planner::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(LintException::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(Plan::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(QuestionBuilder::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationScanner::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(GlobMatcher::class)]
#[UsesClass(Target::class)]
#[UsesClass(TargetExtractor::class)]
#[UsesClass(Tokens::class)]
final class PlannerTest extends TestCase
{
    public function testPlanGroupsRulesPerTargetAndContext(): void
    {
        $root = sys_get_temp_dir() . '/decisions-planner-' . uniqid();
        mkdir($root . '/src/UseCase', 0777, true);
        file_put_contents($root . '/src/Analyzer.php', "<?php\nnamespace App;\nclass Analyzer {\n    public function analyze(): void {}\n    private function helper(): void {}\n}\n");
        file_put_contents($root . '/src/UseCase/Order.php', "<?php\nnamespace App\\UseCase;\nclass Order {\n    public function handle(): void {}\n}\n");
        file_put_contents($root . '/src/notes.md', '# notes');
        $methods = new Rule('cohesion', '', 'warning', true, [], [], new TargetSelector('method', ['public']), 'class', new DecisionSpec('predicate', 'Fits {class}?'), []);
        $naming = new Rule('naming', '', 'info', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('score', 'Rate.', [], ['a', 'b']), []);
        $usecase = new Rule('ddd', '', 'error', true, ['src/UseCase/**'], [], new TargetSelector('method'), 'declaration', new DecisionSpec('predicate', 'Leak?'), []);
        $files = new Rule('docs', '', 'info', true, ['src/*.md'], [], new TargetSelector('file'), 'file', new DecisionSpec('predicate', 'Readable?'), []);
        $disabled = new Rule('off', '', 'info', false, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'X?'), []);
        $configuration = new Configuration($root . '/decisions.yaml', ProviderSettings::defaults('jev'), ['src'], [], 'error', [$methods, $naming, $usecase, $files, $disabled]);
        try {
            $plan = (new Planner(new TargetExtractor(), new QuestionBuilder(), new GlobMatcher()))->plan($configuration);
            self::assertSame(['src/Analyzer.php', 'src/UseCase/Order.php', 'src/notes.md'], $plan->files);
            self::assertCount(5, $plan->inquiries);
            self::assertSame(['cohesion', 'naming'], array_map(static fn (Question $question): string => $question->name, $plan->inquiries[0]->questions));
            self::assertSame(['naming'], array_map(static fn (Question $question): string => $question->name, $plan->inquiries[1]->questions));
            self::assertSame(['ddd'], array_map(static fn (Question $question): string => $question->name, $plan->inquiries[3]->questions));
            self::assertSame(['docs'], array_map(static fn (Question $question): string => $question->name, $plan->inquiries[4]->questions));
            self::assertSame('Fits Analyzer?', $plan->inquiries[0]->questions[0]->instructions);
            self::assertSame(7, count($plan->assignments));
            self::assertSame(4, $plan->targets());
            self::assertCount(1, (new Planner())->plan($configuration, ['src/notes.md'])->inquiries);
            self::assertSame(['ddd'], array_map(static fn (Assignment $assignment): string => $assignment->rule->id, (new Planner())->plan($configuration, [], ['ddd'])->assignments));
        } finally {
            unlink($root . '/src/Analyzer.php');
            unlink($root . '/src/UseCase/Order.php');
            unlink($root . '/src/notes.md');
            rmdir($root . '/src/UseCase');
            rmdir($root . '/src');
            rmdir($root);
        }
    }

    public function testRulesRejectsAnUnknownId(): void
    {
        $rule = new Rule('known', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $configuration = new Configuration('/p/decisions.yaml', ProviderSettings::defaults('jev'), ['src'], [], 'error', [$rule]);
        self::assertSame([$rule], (new Planner())->rules($configuration, ['known']));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Unknown rule "typo". Use one of the configured rule ids: known.');
        (new Planner())->rules($configuration, ['typo']);
    }

    public function testApplicableKeepsRulesWhosePathsSelectTheFile(): void
    {
        $everywhere = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $scoped = new Rule('b', '', 'error', true, ['src/UseCase/**'], ['src/UseCase/Legacy/**'], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $planner = new Planner();
        self::assertSame([$everywhere], $planner->applicable([$everywhere, $scoped], 'src/A.php'));
        self::assertSame([$everywhere, $scoped], $planner->applicable([$everywhere, $scoped], 'src/UseCase/B.php'));
        self::assertSame([$everywhere], $planner->applicable([$everywhere, $scoped], 'src/UseCase/Legacy/C.php'));
    }

    public function testCandidatesSkipsNonPhpFilesForDeclarationRulesAndRejectsUnreadableFiles(): void
    {
        $root = sys_get_temp_dir() . '/decisions-candidates-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/notes.md', '# notes');
        $method = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $file = new Rule('b', '', 'error', true, [], [], new TargetSelector('file'), 'file', new DecisionSpec('predicate', 'Q?'), []);
        try {
            $planner = new Planner();
            self::assertSame([], $planner->candidates('notes.md', $root, []));
            $candidates = $planner->candidates('notes.md', $root, [$method, $file]);
            self::assertCount(1, $candidates);
            self::assertSame('b', $candidates[0]['rule']->id);
            self::assertSame('file', $candidates[0]['target']->kind);
            $this->expectException(LintException::class);
            $this->expectExceptionMessage('Could not read ' . $root . '/missing.php. Check the file permissions.');
            $planner->candidates('missing.php', $root, [$file]);
        } finally {
            unlink($root . '/notes.md');
            rmdir($root);
        }
    }
}
