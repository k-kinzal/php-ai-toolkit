<?php

declare(strict_types=1);

namespace Guard\Structure;

/**
 * Exposes already-read bytes unchanged.
 */
final class TextStructurer implements Structurer
{
    /**
     * Wraps the source bytes without parsing them.
     */
    public function structure(Source $source): Text
    {
        return new Text($source->text());
    }
}
