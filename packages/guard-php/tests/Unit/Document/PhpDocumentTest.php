<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\PhpDocument
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\PhpConfigReader
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Document\PhpDocument::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Document\DocumentNode::class)]
#[UsesClass(\Guard\Document\PhpConfigReader::class)]
#[UsesClass(\Guard\Document\Pointer::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PhpDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadSelectsALiteralRule(): void
    {
        $document = new \Guard\Document\PhpDocument("<?php (new PhpCsFixer\Config())->setRiskyAllowed(true)->setRules(['@PSR12' => true]);");
        $selected = $document->read('/rules/@PSR12');
        self::assertTrue($selected->exists);
        self::assertTrue($selected->value);
    }

    public function testWriteRejectsRepair(): void
    {
        $document = new \Guard\Document\PhpDocument('<?php (new PhpCsFixer\Config())->setRiskyAllowed(false);');
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('cannot be repaired');
        $document->write('/riskyAllowed', true);
    }

    public function testEncodeReturnsTheOriginalSource(): void
    {
        $source = '<?php (new PhpCsFixer\Config())->setRiskyAllowed(true);';
        self::assertSame($source, (new \Guard\Document\PhpDocument($source))->encode());
    }
}
