<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Diagnostic\PolicyException;

/**
 * Validates extension declarations without loading or executing their classes.
 */
final class ExtensionConfigReader
{
    /**
     * Reads a class-name mapping whose values are extension-owned option mappings.
     * @return array<string, array<string, mixed>>
     * @throws PolicyException when class names or option mappings are malformed
     */
    public function read(mixed $value): array
    {
        if (!is_array($value)) {
            throw new PolicyException('guard.yaml.extensions must map extension class names to option mappings. Use {} for an extension without options.');
        }
        $extensions = [];
        foreach ($value as $class => $options) {
            if (!is_string($class) || $class === '' || trim($class) !== $class) {
                throw new PolicyException('guard.yaml.extensions must use non-empty extension class names as keys. Use a class-to-options mapping instead of a list.');
            }
            $extensions[$class] = $this->options($options, $class);
        }
        return $extensions;
    }

    /**
     * Leaves option semantics to the extension while requiring named options.
     * @return array<string, mixed>
     * @throws PolicyException when an extension's options are not a mapping
     */
    public function options(mixed $value, string $class): array
    {
        if (!is_array($value)) {
            throw new PolicyException('guard.yaml.extensions.' . $class . ' must be an option mapping. Use {} for no options.');
        }
        $options = [];
        foreach ($value as $key => $option) {
            if (!is_string($key) || $key === '') {
                throw new PolicyException('guard.yaml.extensions.' . $class . ' must use non-empty option names instead of a list.');
            }
            $options[$key] = $option;
        }
        return $options;
    }
}
