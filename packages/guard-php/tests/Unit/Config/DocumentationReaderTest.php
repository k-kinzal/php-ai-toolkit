<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\DocumentationReader
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Config\Schema
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Config\DocumentationReader::class)]
#[UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Doc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DocumentationReaderTest extends TestCase
{
    public function testReadPreservesHeadingRequirements(): void
    {
        $config = (new \Guard\Config\DocumentationReader())->read(['files' => ['README.md' => ['headings' => ['# Product', '## Usage']]]], '/project', 'guard.yaml');
        self::assertSame('guard.yaml', $config->configName);
        self::assertCount(2, $config->documents[0]->headings);
    }

}
