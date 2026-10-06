<?php

declare(strict_types=1);

namespace Example\Guard;

use Guard\Config\Schema;
use Guard\Extension\ConfigurableExtension;
use Guard\Extension\Registry;

/** An external extension using the same collection and XML structure as built-in policies. */
final class XmlSchemaExtension implements ConfigurableExtension
{
    public function __construct(private XmlSchemaPolicy $policy)
    {
    }

    public static function fromOptions(array $options): self
    {
        $reader = new Schema();
        $options = $reader->mapping($options, ['files', 'schema'], self::class);
        return new self(new XmlSchemaPolicy(
            $reader->strings($options['files'] ?? ['**/*.xml'], self::class . '.files'),
            $reader->string($options['schema'] ?? null, self::class . '.schema'),
        ));
    }

    public function register(Registry $registry): void
    {
        $registry->addPolicy('example.xml-schema', $this->policy);
    }
}
