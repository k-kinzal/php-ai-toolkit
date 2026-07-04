<?php

declare(strict_types=1);

function fixtureCatchBoundaryLineComment(): int
{
    try {
        throw new RuntimeException('failed');
    } catch (RuntimeException $exception) {
        return $exception->getCode();
    } // This comment is outside the catch body.
}
