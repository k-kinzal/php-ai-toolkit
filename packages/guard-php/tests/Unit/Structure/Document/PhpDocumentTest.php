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

    public function testWriteRejectsRepair(): void
    {
        $document = new \Guard\Structure\Document\PhpDocument('<?php (new PhpCsFixer\Config())->setRiskyAllowed(false);');
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('cannot be repaired');
        $document->write('/riskyAllowed', true);
    }

    public function testEncodeReturnsTheOriginalSource(): void
    {
        $source = '<?php (new PhpCsFixer\Config())->setRiskyAllowed(true);';
        self::assertSame($source, (new \Guard\Structure\Document\PhpDocument($source))->encode());
    }
}
