<?php

declare(strict_types=1);

namespace Guard\Document;

use JsonException;

/**
 * Reads the JSON5 subset used by toolkit configuration: comments, trailing commas, and quoted keys.
 */
final class Json5Reader
{
    /**
     * Decodes a JSON5 document after removing comments and trailing commas.
     *
     * @return mixed
     * @throws JsonException when the remaining text is not JSON
     */
    public function decode(string $source): mixed
    {
        return json_decode($this->strip($source), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Removes comments and trailing commas outside strings.
     */
    public function strip(string $source): string
    {
        $length = strlen($source);
        $out = '';
        $string = false;
        $quote = '';
        $escaped = false;
        for ($index = 0; $index < $length; $index++) {
            $char = $source[$index];
            if ($string) {
                $out .= $char;
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                $escaped = $char === '\\';
                $string = $escaped || $char !== $quote;
                continue;
            }
            if ($char === '"' || $char === "'") {
                $string = true;
                $quote = $char;
                $out .= $char;
                continue;
            }
            if ($char === '/' && ($source[$index + 1] ?? '') === '/') {
                $index = $this->line($source, $index);
                continue;
            }
            if ($char === '/' && ($source[$index + 1] ?? '') === '*') {
                $index = $this->block($source, $index);
                continue;
            }
            if ($char === ',' && $this->closer($source, $index + 1)) {
                continue;
            }
            $out .= $char;
        }

        return $out;
    }

    /**
     * Returns the index of the line ending that closes a // comment.
     */
    public function line(string $source, int $index): int
    {
        $end = strpos($source, "\n", $index);

        return $end === false ? strlen($source) : $end;
    }

    /**
     * Returns the index of the last character of a block comment.
     */
    public function block(string $source, int $index): int
    {
        $end = strpos($source, '*/', $index + 2);

        return $end === false ? strlen($source) : $end + 1;
    }

    /**
     * Reports whether the next non-space character closes an object or a list.
     */
    public function closer(string $source, int $index): bool
    {
        $length = strlen($source);
        while ($index < $length && ctype_space($source[$index])) {
            $index++;
        }
        $next = $source[$index] ?? '';

        return $next === '}' || $next === ']';
    }
}
