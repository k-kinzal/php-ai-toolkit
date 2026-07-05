<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

use function is_array;

use PhpAiToolkit\LocGuard\LocGuardException;

/**
 * Reads LocGuard numeric limits from loc.yaml.
 */
final class LimitConfigReader
{
    /**
     * Creates a reader from scalar validation.
     */
    public function __construct(
        private readonly ConfigScalarReader $scalarReader = new ConfigScalarReader(),
    ) {
    }

    /**
     * Reads line-count and complexity limits.
     *
     * @param mixed $value
     */
    public function read($value): LimitConfig
    {
        if (!is_array($value)) {
            throw new LocGuardException('Invalid loc.yaml: "limits" must be a mapping.');
        }

        return new LimitConfig(
            $this->scalarReader->positiveInt($value, 'max_file_lines', 500),
            $this->scalarReader->positiveInt($value, 'max_file_ncloc', 350),
            $this->scalarReader->positiveInt($value, 'max_class_lines', 400),
            $this->scalarReader->positiveInt($value, 'max_trait_lines', 300),
            $this->scalarReader->positiveInt($value, 'max_interface_lines', 200),
            $this->scalarReader->positiveInt($value, 'max_enum_lines', 200),
            $this->scalarReader->positiveInt($value, 'max_function_lines', 50),
            $this->scalarReader->positiveInt($value, 'max_method_lines', 50),
            $this->scalarReader->positiveInt($value, 'max_cyclomatic_complexity', 20),
        );
    }
}
