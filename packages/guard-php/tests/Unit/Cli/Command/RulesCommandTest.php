<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

/**
 * @covers \Guard\Cli\Command\RulesCommand
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Document\Selection
 * @uses \Guard\Reporting\RuleDescription
 * @uses \Guard\Reporting\RuleMessages
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\FormatDetector
 * @uses \Guard\Config\RuleCatalog
 * @uses \Guard\Reporting\RuleReporter
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\FieldMessage
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Cli\Command\RulesCommand::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ImportResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\DocumentMerger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\RuleReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FieldConstraints::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\RuleDescription::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\RuleMessages::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Cli\Command\GuardCommand::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Cli\FormatDetector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\RuleCatalog::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\RuleReporter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\FieldMessage::class)]
final class RulesCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunFiltersTheResolvedRulesAndRejectsEmptyQueries(): void
    {
        $project = new \Tests\Support\Project(['guard.yaml' => "version: 1\nconfiguration:\n  - {id: workers, file: app.json, select: /workers, message: Bound concurrency. Set /workers to 2., assert: {equals: 2}}\n"]);
        try {
            $tester = new \Symfony\Component\Console\Tester\CommandTester(new \Guard\Cli\Command\RulesCommand($project->root));
            self::assertSame(0, $tester->execute(['--query' => 'CONCURRENCY', '--format' => 'text']));
            self::assertStringContainsString('workers', $tester->getDisplay());
            self::assertStringContainsString('Bound concurrency', $tester->getDisplay());
            self::assertSame(0, $tester->execute(['--query' => 'missing', '--format' => 'json']));
            self::assertStringContainsString('"rules": []', $tester->getDisplay());
            self::assertSame(2, $tester->execute(['--query' => '']));
        } finally {
            $project->remove();
        }
    }

}
