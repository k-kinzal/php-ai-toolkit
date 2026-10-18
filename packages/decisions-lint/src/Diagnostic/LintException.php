<?php

declare(strict_types=1);

namespace DecisionsLint\Diagnostic;

use RuntimeException;

/**
 * A configuration, input, or provider problem that stops a run before it can report findings.
 *
 * Every message names what is wrong and what to change, so the command can
 * print it as its only diagnostic and exit with code 2.
 */
final class LintException extends RuntimeException
{
}
