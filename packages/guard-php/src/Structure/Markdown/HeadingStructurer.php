<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown;

use Guard\Structure\Source;
use Guard\Structure\Structurer;

/**
 * Structures Markdown independently of file selection or heading constraints.
 */
final class HeadingStructurer implements Structurer
{
    /**
     * Parses all heading levels once for the selected content.
     */
    public function structure(Source $source): HeadingList
    {
        return new HeadingList((new HeadingParser())->parse($source->text()));
    }
}
