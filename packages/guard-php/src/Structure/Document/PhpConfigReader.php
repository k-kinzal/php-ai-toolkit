<?php

declare(strict_types=1);

namespace Guard\Structure\Document;

use Guard\Diagnostic\PolicyException;
use PhpToken;

/**
 * Reads literal setRiskyAllowed and setRules arguments without executing PHP.
 */
final class PhpConfigReader
{
    /** Reads literals from the tokens already shared with other PHP structures.
     * @param list<PhpToken> $tokens
     * @return array<array-key, mixed>
     */
    public function fromTokens(array $tokens): array
    {
        return $this->configuration($tokens)['data'];
    }
    /**
     * Returns literal configuration values, keeping absent fields distinct from null.
     *
     * @return array<array-key, mixed>
     */
    public function read(string $source): array
    {
        return $this->fromTokens(array_values(PhpToken::tokenize($source)));
    }

    /**
     * Distinguishes a returned configuration array from PHP-CS-Fixer's fluent setters.
     * @param list<PhpToken> $tokens
     * @return array{array: bool, data: array<array-key, mixed>}
     * @throws PolicyException when the returned configuration is not a complete literal array
     */
    public function configuration(array $tokens): array
    {
        $values = [];
        foreach ($tokens as $token) {
            $values[] = $token->id < 256 ? $token->text : [$token->id, $token->text, $token->line];
        }
        $editor = new PhpConfigEditor();
        $returns = $editor->returns($tokens);
        if (count($returns) > 1) {
            throw new PolicyException('PHP configuration contains multiple top-level return expressions. Return one configuration so its values can be checked and set unambiguously.');
        }
        foreach ($tokens as $index => $token) {
            if (count($returns) === 1 && $token->pos === $returns[0][0] && ($token->text === '[' || $token->id === T_ARRAY)) {
                $parsed = $this->value($values, $index);
                $end = $tokens[$this->skip($values, $parsed['index'])] ?? null;
                if (!is_array($parsed['value']) || $end === null || $end->pos !== $returns[0][1]) {
                    throw new PolicyException('PHP configuration must return a literal array. Replace the dynamic expression with explicit configuration values.');
                }
                return ['array' => true, 'data' => $parsed['value']];
            }
        }
        $data = [];
        foreach (['riskyAllowed' => 'setRiskyAllowed', 'rules' => 'setRules'] as $field => $method) {
            if ($editor->arguments($tokens, $method) !== []) {
                $data[$field] = $this->argument($values, $method);
            }
        }
        return ['array' => false, 'data' => $data];
    }

    /**
     * Returns the first argument of a named call.
     *
     * @param list<mixed> $tokens
     * @return mixed
     * @throws PolicyException when calls are ambiguous or their arguments are not literal values
     */
    public function argument(array $tokens, string $name): mixed
    {
        $count = count($tokens);
        $values = [];
        $operator = false;
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $call = $operator;
            $operator = is_array($token) && $token[0] === T_OBJECT_OPERATOR;
            if (!$call || !is_array($token) || $token[0] !== T_STRING || strcasecmp($this->tokenText($token), $name) !== 0) {
                continue;
            }
            $open = $this->skip($tokens, $index + 1);
            if (($tokens[$open] ?? null) !== '(') {
                continue;
            }

            $parsed = $this->value($tokens, $open + 1);
            if (($tokens[$this->skip($tokens, $parsed['index'])] ?? null) !== ')') {
                throw new PolicyException('PHP ' . $name . '() must have one literal argument. Replace the dynamic expression with a literal so the same value can be checked and set.');
            }
            $values[] = $parsed['value'];
        }
        if (count($values) > 1) {
            throw new PolicyException('PHP configuration contains multiple ' . $name . '() calls. Keep one configuration call so its value can be checked and set unambiguously.');
        }
        return $values[0] ?? null;
    }

    /**
     * Parses one literal value and returns the index after it.
     *
     * @param list<mixed> $tokens
     * @return array{value: mixed, index: int}
     * @throws PolicyException when an expression cannot be read as a literal value
     */
    public function value(array $tokens, int $index): array
    {
        $index = $this->skip($tokens, $index);
        $token = $tokens[$index] ?? null;
        if ($token === '[') {
            return $this->items($tokens, $index + 1, ']');
        }
        $text = $this->tokenText($token);
        if (is_array($token) && $token[0] === T_ARRAY) {
            $open = $this->skip($tokens, $index + 1);

            return ($tokens[$open] ?? null) === '(' ? $this->items($tokens, $open + 1, ')') : ['value' => null, 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            return ['value' => $this->text($text), 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_STRING && in_array(strtolower($text), ['true', 'false', 'null'], true)) {
            return ['value' => $this->constant($text), 'index' => $index + 1];
        }
        if ($token === '-' || $token === '+') {
            return $this->signedNumber($tokens, $index + 1, $token);
        }
        if (is_array($token) && $token[0] === T_LNUMBER && is_numeric($text)) {
            return ['value' => (int) $text, 'index' => $index + 1];
        }
        if (is_array($token) && $token[0] === T_DNUMBER && is_numeric($text)) {
            return ['value' => (float) $text, 'index' => $index + 1];
        }

        throw new PolicyException('PHP configuration contains a non-literal value. Replace the dynamic expression with a literal so it can be checked and set without executing PHP.');
    }

    /**
     * Reads a signed numeric literal without treating an arbitrary expression as a value.
     * @param list<mixed> $tokens
     * @return array{value: int|float, index: int}
     * @throws PolicyException when a sign precedes a nonnumeric value
     */
    public function signedNumber(array $tokens, int $index, string $sign): array
    {
        $number = $this->value($tokens, $index);
        if (!is_int($number['value']) && !is_float($number['value'])) {
            throw new PolicyException('A sign in PHP configuration must precede a numeric literal. Replace the expression with a number.');
        }
        return ['value' => $sign === '-' ? -$number['value'] : $number['value'], 'index' => $number['index']];
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
