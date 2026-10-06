<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Configuration;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Configuration\ConfigurationDocument
 * @uses \Guard\Config\Configuration
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
#[CoversClass(\Guard\Collect\Configuration\ConfigurationDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DataDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Json5Reader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Pointer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\TomlEncoder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\XmlDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigurationDocumentTest extends TestCase
{
    /**
     * @throws JsonException

     * @throws \Nette\Neon\Exception
     */
    public function testRetainsUnmodifiedSourceAlongsideItsStructuredDocument(): void
    {
        $source = "{ \"keep\": true }\n";
        $document = new \Guard\Document\DataDocument('json', $source);
        $subject = new \Guard\Collect\Configuration\ConfigurationDocument('/project/a.json', $source, 'json', $document, []);
        self::assertTrue($subject->document->read('/keep')->value);
        self::assertSame($source, $subject->source);
        self::assertSame($source, $subject->document->encode());
    }

}
