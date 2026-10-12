<?php

namespace RectorFixtures\Comments;

// Remove this explanation.
function run(): array
{
    # Remove hash comments.
    $data = [
        // Keep array notes.
        'first',
        /* Remove block comments even in arrays. */
        'second',
    ];
    try {
        work();
    } catch (\RuntimeException $error) {
        // Keep recovery notes.
        report($error);
    }
    // @phpstan-ignore-next-line
    /** @var array<int, string> $data */
    return $data;
}

-----
<?php

namespace RectorFixtures\Comments;

function run(): array
{
    $data = [
        // Keep array notes.
        'first',
        'second',
    ];
    try {
        work();
    } catch (\RuntimeException $error) {
        // Keep recovery notes.
        report($error);
    }
    // @phpstan-ignore-next-line
    /** @var array<int, string> $data */
    return $data;
}
