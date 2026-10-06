<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Cli\Arguments
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Cli\Arguments::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ArgumentsTest extends TestCase
{
    public function testParsesDryRunWithoutApplyingAnything(): void
    {
        $arguments = (new \Guard\Cli\Arguments())->parse(['apply', '--dry-run', '--config=team.yaml', '--format=json']);
        self::assertTrue($arguments['dryRun']);
        self::assertSame('team.yaml', $arguments['config']);
    }

    public function testRejectsUnknownOptions(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Cli\Arguments())->parse(['check', '--ignore-errors']);
    }

    public function testParsesRepeatedAndCommaSeparatedImports(): void
    {
        $arguments = (new \Guard\Cli\Arguments())->parse(['init', '--import=metrics,structure', '--import', 'composer']);
        self::assertSame(['metrics', 'structure', 'composer'], $arguments['imports']);
    }

    public function testRejectsImportOnCheck(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Cli\Arguments())->parse(['check', '--import=metrics']);
    }

    public function testOptionsReadsInitImports(): void
    {
        $arguments = (new \Guard\Cli\Arguments())->options('init', ['--import=metrics']);
        self::assertSame(['metrics'], $arguments['imports']);
    }

    public function testImportNamesRejectsAnEmptyName(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Provide one or more preset names');
        (new \Guard\Cli\Arguments())->importNames('metrics,', []);
    }

}
