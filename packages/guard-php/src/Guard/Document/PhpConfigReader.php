<?php

declare(strict_types=1);

namespace Toolkit\Guard\Document;

/**
 * Reads literal setRiskyAllowed and setRules arguments without executing PHP.
 */
final class PhpConfigReader
{
    /**
     * Returns the literal risky flag and rule map. Dynamic arguments become null.
     *
     * @return array{riskyAllowed: mixed, rules: mixed}
     */
    public function read(string $source): array
    {
        $tokens = token_get_all($source);

        return [
            'riskyAllowed' => $this->argument($tokens, 'setRiskyAllowed'),
            'rules' => $this->argument($tokens, 'setRules'),
        ];
    }

    /**
     * Returns the first argument of a named call.
     *
     * @param list<mixed> $tokens
     * @return mixed
     */
    public function argument(array $tokens, string $name): mixed
    {
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (!is_array($token) || $token[0] !== T_STRING || $token[1] !== $name) {
                continue;
            }
            $open = $this->skip($tokens, $index + 1);
            if (($tokens[$open] ?? null) !== '(') {
                continue;
            }

            return $this->value($tokens, $open + 1)['value'];
        }

        return null;
    }

    /**
     * Parses one literal value and returns the index after it.
     *
     * @param list<mixed> $tokens
     * @return array{value: mixed, index: int}
     */
    public function value(array $tokens, int $index): array
    {
        $index = $this->skip($tokens, $index);
        $token = $tokens[$index] ?? null;
        if ($token === '[') {
            return $this->items($tokens, $index + 1, ']');
        }
        $text = $this->tokenText($token);
        if (is_array($token) && $token[0] === T_STRING && strcasecmp($text, 'array') === 0) {
            $open = $this->skip($tokens, $index + 1);

            return ($tokens[$open] ?? null) === '(' ? $this->items($tokens, $open + 1, ')') : ['value' => null, 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            return ['value' => $this->text($text), 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_STRING) {
            return ['value' => $this->constant($text), 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_LNUMBER && is_numeric($text)) {
            return ['value' => (int) $text, 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_DNUMBER && is_numeric($text)) {
            return ['value' => (float) $text, 'index' => $index + 1];
        }

        return ['value' => null, 'index' => $index + 1];
    }

    /**
     * Parses a literal array until its closing token.
     *
     * @param list<mixed> $tokens
     * @return array{value: mixed, index: int}
     */
    public function items(array $tokens, int $index, string $end): array
    {
        $values = [];
        $list = true;
        $count = count($tokens);
        while ($index < $count) {
            $index = $this->skip($tokens, $index);
            if (($tokens[$index] ?? null) === $end) {
                return ['value' => $list ? array_values($values) : $values, 'index' => $index + 1];
            }
            if (($tokens[$index] ?? null) === ',') {
                $index++;
                continue;
            }
            $parsed = $this->entry($tokens, $index);
            $index = $parsed['index'];
            if ($parsed['keyed']) {
                $values[$parsed['key']] = $parsed['value'];
                $list = false;
                continue;
            }
            $values[] = $parsed['value'];
        }

        return ['value' => null, 'index' => $index];
    }

    /**
     * Parses one array entry, including a string key.
     *
     * @param list<mixed> $tokens
     * @return array{value: mixed, index: int, keyed: bool, key: string}
     */
    public function entry(array $tokens, int $index): array
    {
        $item = $this->value($tokens, $index);
        $arrow = $this->skip($tokens, $item['index']);
        $token = $tokens[$arrow] ?? null;
        if (!is_array($token) || $token[0] !== T_DOUBLE_ARROW) {
            return ['value' => $item['value'], 'index' => $item['index'], 'keyed' => false, 'key' => ''];
        }
        $next = $this->value($tokens, $arrow + 1);

        return ['value' => $next['value'], 'index' => $next['index'], 'keyed' => true, 'key' => $this->key($item['value'])];
    }

    /**
     * Skips whitespace and comments.
     *
     * @param list<mixed> $tokens
     */
    public function skip(array $tokens, int $index): int
    {
        $count = count($tokens);
        while ($index < $count) {
            $token = $tokens[$index];
            if (!is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $index;
            }
            $index++;
        }

        return $index;
    }

    /**
     * Returns the text of a token, or an empty string for a character token.
     *
     * @param mixed $token
     */
    public function tokenText($token): string
    {
        if (!is_array($token)) {
            return '';
        }
        $text = $token[1] ?? null;

        return is_string($text) ? $text : '';
    }

    /**
     * Unquotes a PHP string token.
     */
    public function text(string $token): string
    {
        $quote = $token[0];
        $body = substr($token, 1, -1);
        if ($quote === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $body);
        }

        return stripcslashes($body);
    }

    /**
     * Maps literal true, false, and null. Other names are not evaluated.
     */
    public function constant(string $name): mixed
    {
        return match (strtolower($name)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => null,
        };
    }

    /**
     * Uses a string or integer as an array key.
     *
     * @param mixed $value
     */
    public function key($value): string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return '';
    }
}
