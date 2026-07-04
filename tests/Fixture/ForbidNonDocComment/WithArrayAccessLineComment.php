<?php

declare(strict_types=1);

function fixtureArrayAccessLineComment(array $items): mixed
{
    return $items[
        // This comment is inside array access, not an array literal.
        'key'
    ];
}
