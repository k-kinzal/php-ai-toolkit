<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DocumentationConfig;
use Guard\Config\Doc\DocumentConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DocumentConfig
 */
#[CoversClass(DocumentationConfig::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
final class DocumentationConfigTest extends TestCase
{
    public function testGetExposesResolvedConfiguration(): void
    {
        $documents = [new DocumentConfig('README.md', [], 6)];
        $config = new DocumentationConfig('/project', 'doc-guard.yaml', $documents, ['*.md']);

        self::assertSame('/project', $config->root);
        self::assertSame('doc-guard.yaml', $config->configName);
        self::assertSame($documents, $config->documents);
        self::assertSame(['*.md'], $config->scan);
    }
}
