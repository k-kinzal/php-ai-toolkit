<?php

namespace RectorFixtures\Suppression;

/**
 * Preserve this description.
 * @phpstan-ignore missingType.return
 * @return string
 */
function name()
{
    // @phpstan-ignore-next-line
    $name = 'ok';
    // An ordinary explanation.
    return $name;
}

/** @infection-ignore-all */
function ignored(): void {}

-----
<?php

namespace RectorFixtures\Suppression;

/**
 * Preserve this description.
 * @return string
 */
function name()
{
    $name = 'ok';
    // An ordinary explanation.
    return $name;
}

function ignored(): void {}
