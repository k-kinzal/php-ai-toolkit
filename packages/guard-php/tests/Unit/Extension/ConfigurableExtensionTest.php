<?php

declare(strict_types=1);

namespace Tests\Unit\Extension;

use Guard\Config\Configuration;
use Guard\Config\Schema;
use Guard\Execution\Context;
use Guard\Extension\ConfigurableExtension;
use Guard\Extension\ExtensionLoader;
use Guard\Extension\Registry;
use Guard\Policy\PolicyException;
use Guard\Structure\TextStructurer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Schema::class)]
final class ConfigurableExtensionTest extends TestCase
{
    public function testFromOptionsConfiguresTheExtensionInsteadOfItsConstructor(): void
    {
        $class = get_class(new class ('unused') implements ConfigurableExtension {
            public function __construct(private string $name)
            {
            }

            public static function fromOptions(array $options): self
            {
                return new self((new Schema())->string((new Schema())->mapping($options, ['name'], 'options')['name'] ?? null, 'options.name'));
            }

            public function register(Registry $registry): void
            {
                $registry->addStructure('configured.' . $this->name, new TextStructurer());
            }
        });
        $registry = new Registry();

        (new ExtensionLoader())->register([$class => ['name' => 'text']], $registry);

        self::assertSame(['configured.text'], array_keys($registry->structures()));
    }

    public function testFromOptionsReportsInvalidOptionsWithTheExtensionClass(): void
    {
        $class = get_class(new class () implements ConfigurableExtension {
            public static function fromOptions(array $options): self
            {
                (new Schema())->mapping($options, [], 'options');

                return new self();
            }

            public function register(Registry $registry): void
            {
            }
        });

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Extension "' . $class . '": options: unknown key "typo"');

        (new ExtensionLoader())->register([$class => ['typo' => true]], new Registry());
    }
}
