<?php

declare(strict_types=1);

function fixtureCatchNonLineComment(): int
{
    try {
        throw new RuntimeException('failed');
    } catch (RuntimeException $exception) {
        /* Block comments are still prohibited inside catch blocks. */
        # Hash comments are still prohibited inside catch blocks.

        return $exception->getCode();
    }
}
