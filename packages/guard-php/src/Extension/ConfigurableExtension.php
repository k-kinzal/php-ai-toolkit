<?php

declare(strict_types=1);

namespace Guard\Extension;

/**
 * Creates an extension from the options declared under its class in guard.yaml.
 */
interface ConfigurableExtension extends Extension
{
    /**
     * Validates extension-owned options and returns a configured instance.
     * @param array<string, mixed> $options
     * @throws \Guard\Policy\PolicyException when options are invalid
     */
    public static function fromOptions(array $options): self;
}
