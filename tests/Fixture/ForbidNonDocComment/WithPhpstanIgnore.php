<?php

declare(strict_types=1);

echo strlen(123); // @phpstan-ignore argument.type

// @phpstan-ignore-next-line
echo strlen(123);

/* @infection-ignore-all */
function fixturePhpstanIgnore(): int
{
    return 1;
}
