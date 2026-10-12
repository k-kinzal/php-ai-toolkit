<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\PhpDocument
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Structure\Document\DocumentNode
 * @uses \Guard\Structure\Document\PhpConfigReader
 * @uses \Guard\Structure\Document\PhpConfigEditor
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Structure\Document\Pointer
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Structure\Document\PhpDocument::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Structure\Document\DocumentNode::class)]
#[UsesClass(\Guard\Structure\Document\PhpConfigReader::class)]
#[UsesClass(\Guard\Structure\Document\PhpConfigEditor::class)]
#[UsesClass(\Guard\Structure\Document\Constraint::class)]
#[UsesClass(\Guard\Structure\Document\Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class PhpDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadSelectsALiteralRule(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument("<?php (new PhpCsFixer\Config())->setRiskyAllowed(true)->setRules(['@PSR12' => true]);");
        $selected = $document->read('/rules/@PSR12');
        self::assertTrue($selected->exists);
        self::assertTrue($selected->value);
    }

    /**
     * @throws JsonException
     */
    public function testWriteRepairsTheSelectedLiteral(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php (new PhpCsFixer\Config())->setRiskyAllowed(false);');
        $document->write('/riskyAllowed', true);
        self::assertTrue($document->read('/riskyAllowed')->value);
        self::assertStringContainsString('setRiskyAllowed(true)', $document->encode());
    }

    public function testEncodeReturnsTheOriginalSource(): void
    {
        $source = '<?php (new PhpCsFixer\Config())->setRiskyAllowed(true);';
        self::assertSame($source, (new \Guard\Structure\Document\PhpDocument($source))->encode());
    }

    /**
     * @throws JsonException
     */
    public function testWriteAddsNestedRulesWithoutExecutingOrReplacingOtherSettings(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php /* keep */ return (new PhpCsFixer\Config())->setRules(["custom" => true]);');
        $document->write('/rules/array_syntax/syntax', 'short');
        self::assertSame('short', $document->read('/rules/array_syntax/syntax')->value);
        self::assertTrue($document->read('/rules/custom')->value);
        self::assertStringContainsString('/* keep */', $document->encode());
    }

    /**
     * @throws JsonException
     */
    public function testWriteCreatesMissingConfigurationCalls(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php return new PhpCsFixer\Config();');
        $document->write('/rules/@PSR12', true);
        $document->write('/riskyAllowed', true);
        self::assertTrue($document->read('/rules/@PSR12')->value);
        self::assertTrue($document->read('/riskyAllowed')->value);
    }

    public function testLiteralPreservesScalarTypesAndConvertsMaps(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php return new PhpCsFixer\Config();');
        self::assertSame(['list' => [false, -1, 2.5, null]], $document->literal((object) ['list' => [false, -1, 2.5, null]]));
    }


    /**
     * @throws JsonException
     */
    public function testWriteArrayPreservesUnselectedKeysAndNegativeNumbers(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php /* keep */ return ["custom" => true, "number" => -2];');
        $document->writeArray('/feature/enabled', true);
        self::assertTrue($document->read('/feature/enabled')->value);
        self::assertTrue($document->read('/custom')->value);
        self::assertSame(-2, $document->read('/number')->value);
        self::assertStringContainsString('/* keep */', $document->encode());
    }

    /**
     * @throws JsonException
     */
    public function testReadDistinguishesAMissingSetterFromAnExplicitNull(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php return (new Config())->setRules(null);');
        self::assertFalse($document->read('/riskyAllowed')->exists);
        self::assertTrue($document->read('/rules')->exists);
        self::assertNull($document->read('/rules')->value);
    }


    /**
     * @throws JsonException
     */
    public function testContainersAllowSettingsInsideAnEmptyPhpArrayWithoutChangingOtherLists(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php return ["feature" => [], "untouched" => []];');
        $document->write('/feature/enabled', true);
        self::assertTrue($document->read('/feature/enabled')->value);
        self::assertSame([], $document->read('/untouched')->value);
    }

}
