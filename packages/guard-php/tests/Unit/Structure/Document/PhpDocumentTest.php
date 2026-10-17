<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Document\Constraint;
use Guard\Structure\Document\DocumentNode;
use Guard\Structure\Document\PhpConfigEditor;
use Guard\Structure\Document\PhpConfigReader;
use Guard\Structure\Document\PhpDocument;
use Guard\Structure\Document\Pointer;
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
#[CoversClass(PhpDocument::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(DocumentNode::class)]
#[UsesClass(PhpConfigReader::class)]
#[UsesClass(PhpConfigEditor::class)]
#[UsesClass(Constraint::class)]
#[UsesClass(Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class PhpDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadSelectsALiteralRule(): void
    {
        $document = new PhpDocument("<?php (new PhpCsFixer\Config())->setRiskyAllowed(true)->setRules(['@PSR12' => true]);");
        $selected = $document->read('/rules/@PSR12');
        self::assertTrue($selected->exists);
        self::assertTrue($selected->value);
    }

    /**
     * @throws JsonException
     */
    public function testWriteRepairsTheSelectedLiteral(): void
    {
        $document = new PhpDocument('<?php (new PhpCsFixer\Config())->setRiskyAllowed(false);');
        $document->write('/riskyAllowed', true);
        self::assertTrue($document->read('/riskyAllowed')->value);
        self::assertStringContainsString('setRiskyAllowed(true)', $document->encode());
    }

    public function testEncodeReturnsTheOriginalSource(): void
    {
        $source = '<?php (new PhpCsFixer\Config())->setRiskyAllowed(true);';
        self::assertSame($source, (new PhpDocument($source))->encode());
    }

    /**
     * @throws JsonException
     */
    public function testWriteAddsNestedRulesWithoutExecutingOrReplacingOtherSettings(): void
    {
        $document = new PhpDocument('<?php /* keep */ return (new PhpCsFixer\Config())->setRules(["custom" => true]);');
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
        $document = new PhpDocument('<?php return new PhpCsFixer\Config();');
        $document->write('/rules/@PSR12', true);
        $document->write('/riskyAllowed', true);
        self::assertTrue($document->read('/rules/@PSR12')->value);
        self::assertTrue($document->read('/riskyAllowed')->value);
    }

    public function testLiteralPreservesScalarTypesAndConvertsMaps(): void
    {
        $document = new PhpDocument('<?php return new PhpCsFixer\Config();');
        self::assertSame(['list' => [false, -1, 2.5, null]], $document->literal((object) ['list' => [false, -1, 2.5, null]]));
    }


    /**
     * @throws JsonException
     */
    public function testWriteArrayPreservesUnselectedKeysAndNegativeNumbers(): void
    {
        $document = new PhpDocument('<?php /* keep */ return ["custom" => true, "number" => -2];');
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
        $document = new PhpDocument('<?php return (new Config())->setRules(null);');
        self::assertFalse($document->read('/riskyAllowed')->exists);
        self::assertTrue($document->read('/rules')->exists);
        self::assertNull($document->read('/rules')->value);
    }


    /**
     * @throws JsonException
     */
    public function testContainersAllowSettingsInsideAnEmptyPhpArrayWithoutChangingOtherLists(): void
    {
        $document = new PhpDocument('<?php return ["feature" => [], "untouched" => []];');
        $document->write('/feature/enabled', true);
        self::assertTrue($document->read('/feature/enabled')->value);
        self::assertSame([], $document->read('/untouched')->value);
    }

}
