<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UseMapCollector::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(PhpParserBridge::class)]
final class UseMapCollectorTest extends TestCase
{
    public function testCollectMapsPlainUseByLowercasedAlias(): void
    {
        $statements = (new AstParser())->parse('<?php use Vendor\Package\Widget;', 'uses.php');

        self::assertSame(['widget' => 'Vendor\Package\Widget'], (new UseMapCollector())->collect($statements));
    }

    public function testCollectMapsAliasedUse(): void
    {
        $statements = (new AstParser())->parse('<?php use Vendor\Package\Widget as Gadget;', 'uses.php');

        self::assertSame(['gadget' => 'Vendor\Package\Widget'], (new UseMapCollector())->collect($statements));
    }

    public function testCollectExpandsGroupUse(): void
    {
        $statements = (new AstParser())->parse('<?php use Vendor\Package\{First, Second as Alt, function helper};', 'uses.php');

        self::assertSame(['first' => 'Vendor\Package\First', 'alt' => 'Vendor\Package\Second'], (new UseMapCollector())->collect($statements));
    }

    public function testCollectSkipsFunctionAndConstUses(): void
    {
        $statements = (new AstParser())->parse('<?php use function strlen; use const SORT_ASC; use Countable;', 'uses.php');

        self::assertSame(['countable' => 'Countable'], (new UseMapCollector())->collect($statements));
    }
}
