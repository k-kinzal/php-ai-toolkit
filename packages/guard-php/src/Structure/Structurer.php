<?php

declare(strict_types=1);

namespace Guard\Structure;

use JsonException;
use RuntimeException;

/**
 * Produces a reusable structure from already-read content, without filesystem access.
 */
interface Structurer
{
    /** Builds the requested structure; dependencies use Source::structure().
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function structure(Source $source): Subject;
}
