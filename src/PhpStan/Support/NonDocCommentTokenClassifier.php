<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

/**
 * Classifies tokenizer tokens for non-PHPDoc comment context tracking.
 */
final class NonDocCommentTokenClassifier
{
    /**
     * Reports whether a tokenizer token affects expression context.
     */
    public function isSignificant(int $tokenType): bool
    {
        return !in_array($tokenType, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG], true);
    }

    /**
     * Reports whether the token type can end a PHP expression.
     */
    public function canEndExpression(int $tokenType): bool
    {
        return in_array(
            $tokenType,
            [
                T_ARRAY,
                T_CLASS,
                T_CONSTANT_ENCAPSED_STRING,
                T_DNUMBER,
                T_DIR,
                T_FILE,
                T_FUNC_C,
                T_LINE,
                T_LNUMBER,
                T_METHOD_C,
                T_NAME_FULLY_QUALIFIED,
                T_NAME_QUALIFIED,
                T_NAME_RELATIVE,
                T_NS_C,
                T_STRING,
                T_TRAIT_C,
                T_VARIABLE,
            ],
            true
        );
    }
}
