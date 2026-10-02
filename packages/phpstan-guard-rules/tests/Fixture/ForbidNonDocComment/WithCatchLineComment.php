<?php

declare(strict_types=1);

function fixtureCatchLineComment(): int
{
    try {
        throw new RuntimeException('failed');
    } catch (RuntimeException $exception) {
        // This catch comment is allowed.
        return $exception->getCode();
    }
}
