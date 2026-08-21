<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function get_class;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;
use function var_export;

/**
 * Renders a runtime value the way a failure report should show it.
 *
 * @visibility public
 *
 * @example Rendering a value for a report
 *     (new ValueFormatter())->format([1, 2]) // => "array (\n  0 => 1,\n  1 => 2,\n)"
 */
final class ValueFormatter
{
    /**
     * Returns a short readable rendering of any value.
     *
     * @param mixed $value the value to render
     *
     * @example Rendering the values a report shows
     *     $formatter = new ValueFormatter();
     *     $formatter->format('done') // => "'done'"
     *     $formatter->format(true) // => 'true'
     *     $formatter->format(null) // => 'null'
     */
    public function format($value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return var_export($value, true);
        }

        if (is_array($value)) {
            return var_export($value, true);
        }

        return is_object($value) ? sprintf('object(%s)', get_class($value)) : var_export($value, true);
    }
}
