<?php

declare(strict_types=1);

namespace Tests\Doctest\Legacy;

use PhpAiToolkit\PhpUnit\Doctest\Legacy\LegacyDoctestTestCase;

/**
 * Runs the documented examples through the PHPUnit 9 test case.
 *
 * The PHPUnit 10+ configuration excludes this directory, so the same examples
 * are checked through whichever test case the installed PHPUnit can read.
 *
 * @medium
 */
final class LegacyDocumentedExampleTest extends LegacyDoctestTestCase
{
}
