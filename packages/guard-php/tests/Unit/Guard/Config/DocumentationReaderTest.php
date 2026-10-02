<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\DocumentationReader
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Config\DocumentationReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeading::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeadingReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentListConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\DocGuard\DocGuardException::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\DocGuardPathResolver::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Heading::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class DocumentationReaderTest extends TestCase
{
    public function testReadPreservesHeadingRequirements(): void
    {
        $config = (new \Toolkit\Guard\Config\DocumentationReader())->read(['files' => ['README.md' => ['headings' => ['# Product', '## Usage']]]], '/project', 'guard.yaml');
        self::assertSame('guard.yaml', $config->configName);
        self::assertCount(2, $config->documents[0]->headings);
    }

}
