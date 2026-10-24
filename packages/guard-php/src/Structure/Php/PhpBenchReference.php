<?php

declare(strict_types=1);

namespace Guard\Structure\Php;

/**
 * Finds PHPBench namespace references in PHP syntax, excluding comments and strings.
 */
final class PhpBenchReference
{
    /**
     * Accepts imports, group imports and qualified names, but not namespace declarations.
     */
    public function exists(Tokens $tokens): bool
    {
        $namespace = false;
        $all = $tokens->all();
        foreach ($all as $index => $token) {
            if ($token->is(T_NAMESPACE)) {
                $namespace = true;
            }
            if ($namespace) {
                if ($token->is([';', '{'])) {
                    $namespace = false;
                }
                continue;
            }
            if (!$token->is([T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING])) {
                continue;
            }
            $name = strtolower(ltrim($token->text, '\\'));
            if (str_starts_with($name, 'phpbench\\')) {
                return true;
            }
            if ($name === 'phpbench' && isset($all[$index + 1]) && $all[$index + 1]->is(T_NS_SEPARATOR)) {
                return true;
            }
        }
        return false;
    }
}
