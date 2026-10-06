<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\RuleEvaluator
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\Json5Reader
 * @uses \Guard\Document\PhpConfigReader
 * @uses \Guard\Document\PhpDocument
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Document\TomlEncoder
 * @uses \Guard\Document\XmlDocument
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Document\DataDocument::class)]
#[UsesClass(\Guard\Document\DocumentNode::class)]
#[UsesClass(\Guard\Document\Json5Reader::class)]
#[UsesClass(\Guard\Document\PhpConfigReader::class)]
#[UsesClass(\Guard\Document\PhpDocument::class)]
#[UsesClass(\Guard\Document\Pointer::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Document\XmlDocument::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\Constraint::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class RuleEvaluatorTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testAcceptsTreatsXmlBoundsNumerically(): void
    {
        $rule = new \Guard\Policy\Rule('count', 'x.xml', 'xml', '/x/@count', 'required', ['min' => 1], null, false);
        $document = new \Guard\Document\XmlDocument('<x count="2"/>');
        self::assertTrue((new \Guard\Policy\RuleEvaluator())->accepts($rule, $document));
    }

    /**
     * @throws JsonException
     */
    public function testFindingNamesTheFieldAndTheRepair(): void
    {
        $rule = (new \Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']]);
        $finding = (new \Guard\Policy\RuleEvaluator())->finding($rule);
        self::assertStringContainsString('/mode', $finding->message);
        self::assertStringContainsString('guard apply', $finding->message);
    }
}
