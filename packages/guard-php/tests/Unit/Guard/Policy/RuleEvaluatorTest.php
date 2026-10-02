<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Policy;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Policy\RuleEvaluator
 * @uses \Toolkit\Guard\Config\RuleReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Document\DataDocument
 * @uses \Toolkit\Guard\Document\Pointer
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Document\TomlEncoder
 * @uses \Toolkit\Guard\Document\XmlDocument
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\Guard\Reporting\Finding
 */
#[CoversClass(\Toolkit\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Toolkit\Guard\Config\RuleReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Document\DataDocument::class)]
#[UsesClass(\Toolkit\Guard\Document\Pointer::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Toolkit\Guard\Document\XmlDocument::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\Guard\Reporting\Finding::class)]
final class RuleEvaluatorTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testAcceptsTreatsXmlBoundsNumerically(): void
    {
        $rule = new \Toolkit\Guard\Policy\Rule('count', 'x.xml', 'xml', '/x/@count', 'required', ['min' => 1], null, false);
        $document = new \Toolkit\Guard\Document\XmlDocument('<x count="2"/>');
        self::assertTrue((new \Toolkit\Guard\Policy\RuleEvaluator())->accepts($rule, $document));
    }

    /**
     * @throws JsonException
     */
    public function testFindingNamesTheFieldAndTheRepair(): void
    {
        $rule = (new \Toolkit\Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']]);
        $finding = (new \Toolkit\Guard\Policy\RuleEvaluator())->finding($rule);
        self::assertStringContainsString('/mode', $finding->message);
        self::assertStringContainsString('guard apply', $finding->message);
    }
}
