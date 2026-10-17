<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Diagnostic;

use Guard\Policy\Diagnostic\RuleMessages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Diagnostic\RuleMessages
 */
#[CoversClass(RuleMessages::class)]

final class RuleMessagesTest extends TestCase
{
    public function testForRuleNamesTheProblemAndAnActionForEachFamily(): void
    {
        $messages = new RuleMessages();
        self::assertStringContainsString('Simplify conditional branches', $messages->forRule('metrics.cyclomatic_complexity'));
        self::assertStringContainsString('Split unrelated responsibilities', $messages->forRule('metrics.file_lines'));
        self::assertStringContainsString('Rename or remove', $messages->forRule('structure.denied_dir'));
        self::assertStringContainsString('document', $messages->forRule('documentation.missing_document'));
        self::assertStringContainsString('concrete edit', $messages->forRule('custom'));
    }


    public function testDiagnosticPreservesTheSpecificProblemAndFix(): void
    {
        $text = (new RuleMessages())->diagnostic('metrics.cyclomatic_complexity', 'Method A::run has complexity 25; maximum 20. Extract independent operations.');
        self::assertStringStartsWith('Method A::run has complexity 25; maximum 20. Extract independent operations.', $text);
        self::assertStringContainsString('harder to reason about and test', $text);
    }

    /**
     * @dataProvider providerBuiltInRules
     */
    #[DataProvider('providerBuiltInRules')]
    public function testRationaleExplainsEveryBuiltInFamily(string $id): void
    {
        $messages = new RuleMessages();
        self::assertNotSame('', $messages->rationale($id), $id);
        self::assertStringContainsString($messages->rationale($id), $messages->forRule($id));
        self::assertStringNotContainsString('custom rule', $messages->forRule($id));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerBuiltInRules(): iterable
    {
        foreach (['metrics.file_lines', 'metrics.file_ncloc', 'metrics.class_lines', 'metrics.trait_lines', 'metrics.interface_lines', 'metrics.enum_lines', 'metrics.function_lines', 'metrics.method_lines', 'metrics.cyclomatic_complexity',
            'structure.max_files', 'structure.max_total_files', 'structure.max_dirs', 'structure.max_depth', 'structure.disallowed_file', 'structure.denied_file', 'structure.disallowed_dir', 'structure.denied_dir', 'structure.missing_required_file', 'structure.empty_directory', 'structure.file_case', 'structure.dir_case',
            'documentation.missing_document', 'documentation.undeclared_document', 'documentation.unexpected_heading', 'documentation.missing_heading', 'documentation.renamed_heading', 'documentation.changed_heading_level', 'documentation.moved_heading', 'documentation.unexpected_outline_heading', 'documentation.missing_outline_heading', 'documentation.missing_badges', 'documentation.unexpected_badge', 'documentation.missing_badge', 'documentation.unexpected_content'] as $id) {
            yield $id => [$id];
        }
    }
}
