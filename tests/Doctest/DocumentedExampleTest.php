<?php

declare(strict_types=1);

namespace Tests\Doctest;

use PhpAiToolkit\PhpUnit\Doctest\DoctestTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Medium;

/**
 * Runs the examples this package documents its own public API with.
 *
 * The defaults scan src from the project root, so this class configures
 * nothing: a subclass is the whole setup a project needs.
 */
#[CoversNothing]
#[Medium]
final class DocumentedExampleTest extends DoctestTestCase
{
}
