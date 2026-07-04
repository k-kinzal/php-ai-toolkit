<?php

declare(strict_types=1);

function fixtureArrayLineComment(): array
{
    return [
        // This list item comment is allowed.
        'list-item',
        'key' => [
            // This associative array comment is allowed.
            'value',
        ],
    ];
}
