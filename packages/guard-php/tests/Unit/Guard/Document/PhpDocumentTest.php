<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Document\PhpDocument
 * @uses \Toolkit\Guard\Document\PhpConfigReader
 * @uses \Toolkit\Guard\Document\Pointer
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Document\PhpDocument::class)]
#[UsesClass(\Toolkit\Guard\Document\PhpConfigReader::class)]
#[UsesClass(\Toolkit\Guard\Document\Pointer::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class PhpDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadSelectsALiteralRule(): void
    {
        $document = new \Toolkit\Guard\Document\PhpDocument("<?php (new PhpCsFixer\Config())->setRiskyAllowed(true)->setRules(['@PSR12' => true]);");
        $selected = $document->read('/rules/@PSR12');
        self::assertTrue($selected->exists);
        self::assertTrue($selected->value);
    }

    public function testWriteRejectsRepair(): void
    {
        $document = new \Toolkit\Guard\Document\PhpDocument('<?php (new PhpCsFixer\Config())->setRiskyAllowed(false);');
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('cannot be repaired');
        $document->write('/riskyAllowed', true);
    }

    public function testEncodeReturnsTheOriginalSource(): void
    {
        $source = '<?php (new PhpCsFixer\Config())->setRiskyAllowed(true);';
        self::assertSame($source, (new \Toolkit\Guard\Document\PhpDocument($source))->encode());
    }
}
