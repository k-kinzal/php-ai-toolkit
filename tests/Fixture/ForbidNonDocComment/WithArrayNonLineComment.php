<?php

declare(strict_types=1);

function fixtureArrayNonLineComment(): array
{
    return [
        /* Block comments are still prohibited inside arrays. */
        # Hash comments are still prohibited inside arrays.
        'value',
    ];
}
