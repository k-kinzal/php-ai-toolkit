<?php

declare(strict_types=1);

namespace Tests\Unit\Extension;

use Guard\Config\Configuration;
use Guard\Execution\Context;
use Guard\Extension\ConfigurableExtension;
use Guard\Extension\ExtensionLoader;
use Guard\Extension\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\XmlSchemaExample;

/**
 * @covers \Guard\Extension\ExtensionLoader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Structure\Source
 */
#[CoversClass(ExtensionLoader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Registry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
final class ConfigurableExtensionTest extends TestCase
{
    public function testFromOptionsConfiguresPolicyInputsWithoutFileAccess(): void
    {
        XmlSchemaExample::load();
        $extension = (new ExtensionLoader())->create('Example\\Guard\\XmlSchemaExtension', ['schema' => 'schema.xsd']);
        self::assertInstanceOf(ConfigurableExtension::class, $extension);
        $registry = new Registry();
        $extension->register($registry);
        $context = new Context(new Configuration('/does-not-exist', []), '/does-not-exist/guard.yaml', false);
        $inputs = $registry->policies()[0]->policy->inputs($context);
        self::assertSame(['**/*.xml'], $inputs['documents']->selection->paths);
        self::assertSame('xml', $inputs['documents']->structure);
        self::assertSame(['schema.xsd'], $inputs['schema']->selection->paths);
    }
}
