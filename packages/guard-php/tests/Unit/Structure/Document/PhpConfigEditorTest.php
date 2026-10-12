<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Diagnostic\PolicyException;
use Guard\Structure\Document\PhpConfigEditor;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\PhpConfigEditor
 * @uses \Guard\Diagnostic\PolicyException
 */
#[CoversClass(PhpConfigEditor::class)]
#[UsesClass(PolicyException::class)]
final class PhpConfigEditorTest extends TestCase
{
    public function testWriteReplacesAnArgumentAndPreservesSurroundingCode(): void
    {
        $source = '<?php /* keep */ return $config->setRiskyAllowed(false)->setFinder($finder);';
        self::assertSame('<?php /* keep */ return $config->setRiskyAllowed(true)->setFinder($finder);', (new PhpConfigEditor())->write($source, 'setRiskyAllowed', 'true'));
    }

    public function testWriteAddsAMissingSetterToTheReturnedObject(): void
    {
        $source = '<?php return new PhpCsFixer\Config();';
        self::assertSame('<?php return (new PhpCsFixer\Config())->setRules([]);', (new PhpConfigEditor())->write($source, 'setRules', '[]'));
    }

    public function testWriteRejectsAmbiguousCalls(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('multiple setRules() calls');
        (new PhpConfigEditor())->write('<?php return $a->setRules([])->setRules([]);', 'setRules', '[]');
    }

    public function testArgumentsIgnoreMethodNamesInsideStringsAndComments(): void
    {
        $source = '<?php /* ->setRules([]) */ $note = "setRules([])"; return $config->setRules(["x" => [1, 2]]);';
        $ranges = (new PhpConfigEditor())->arguments(array_values(PhpToken::tokenize($source)), 'setRules');
        self::assertCount(1, $ranges);
        self::assertSame('["x" => [1, 2]]', substr($source, $ranges[0][0], $ranges[0][1] - $ranges[0][0]));
    }

    public function testReturnedExpressionIgnoresNestedClosureReturns(): void
    {
        $source = '<?php $factory = function () { return false; }; return $factory();';
        [$start, $end] = (new PhpConfigEditor())->returnedExpression(array_values(PhpToken::tokenize($source)));
        self::assertSame('$factory()', substr($source, $start, $end - $start));
    }

    public function testClosingSkipsNestedDelimitersAndQuotedDelimiters(): void
    {
        $source = '<?php [")", [1]]);';
        self::assertSame(strlen($source) - 2, (new PhpConfigEditor())->closing(array_values(PhpToken::tokenize($source)), 1, ')'));
    }

    public function testNextSkipsCommentsBeforeAnArgument(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php /* keep */ true;'));
        self::assertSame('true', $tokens[(new PhpConfigEditor())->next($tokens, 1)]->text);
    }

    public function testReturnsDoesNotInventAReturnForAStatement(): void
    {
        self::assertSame([], (new PhpConfigEditor())->returns(array_values(PhpToken::tokenize('<?php $config->setRules([]);'))));
    }

}
