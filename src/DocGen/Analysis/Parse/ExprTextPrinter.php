<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse;

use function mb_strlen;
use function mb_substr;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\PrettyPrinter\Standard;

/**
 * Prints constant and default value expressions as short source text.
 *
 * Constant fetches are printed by their short names because the resolved
 * AST would otherwise render them fully qualified, which reads poorly in
 * signatures.
 */
final class ExprTextPrinter
{
    /**
     * Maximum printed length before the text is truncated with an ellipsis.
     */
    public const MAX_LENGTH = 80;

    /** @readonly */
    private Standard $printer;

    /**
     * Creates an expression printer.
     */
    public function __construct(?Standard $printer = null)
    {
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Prints one expression, truncated to a display-friendly length.
     */
    public function print(?Expr $expr): ?string
    {
        if ($expr === null) {
            return null;
        }

        if ($expr instanceof ConstFetch) {
            return $expr->name->toString();
        }

        $text = $expr instanceof ClassConstFetch && $expr->class instanceof Name && $expr->name instanceof Identifier
            ? $expr->class->getLast() . '::' . $expr->name->toString()
            : $this->printer->prettyPrintExpr($expr);
        if (mb_strlen($text) > self::MAX_LENGTH) {
            return mb_substr($text, 0, self::MAX_LENGTH - 1) . '…';
        }

        return $text;
    }
}
