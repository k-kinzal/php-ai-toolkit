<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Cli\Arguments
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Cli\Arguments::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class ArgumentsTest extends TestCase
{
    public function testParsesDryRunWithoutApplyingAnything(): void
    {
        $arguments = (new \Toolkit\Guard\Cli\Arguments())->parse(['apply', '--dry-run', '--config=team.yaml', '--format=json']);
        self::assertTrue($arguments['dryRun']);
        self::assertSame('team.yaml', $arguments['config']);
    }

    public function testRejectsUnknownOptions(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Cli\Arguments())->parse(['check', '--ignore-errors']);
    }

    public function testParsesRepeatedAndCommaSeparatedImports(): void
    {
        $arguments = (new \Toolkit\Guard\Cli\Arguments())->parse(['init', '--import=quality,structure', '--import', 'composer']);
        self::assertSame(['quality', 'structure', 'composer'], $arguments['imports']);
    }

    public function testRejectsImportOnCheck(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Cli\Arguments())->parse(['check', '--import=quality']);
    }

    public function testOptionsReadsInitImports(): void
    {
        $arguments = (new \Toolkit\Guard\Cli\Arguments())->options('init', ['--import=quality']);
        self::assertSame(['quality'], $arguments['imports']);
    }

    public function testImportNamesRejectsAnEmptyName(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Provide one or more preset names');
        (new \Toolkit\Guard\Cli\Arguments())->importNames('quality,', []);
    }

}
