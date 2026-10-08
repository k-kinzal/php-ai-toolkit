<?php

namespace Tests\Fixture\ForbidDependency\Project\tests;

use Tests\Fixture\ForbidDependency\Project\src\Reader;

echo 'See examples/input.json';
// Documentation: examples/input.json
/** Example: examples/input.json */
file_get_contents(__DIR__ . '/../examples-backup/input.json');
file_get_contents(__DIR__ . '/../fixtures/input.json');
file_get_contents(__DIR__ . '/../vendor/demo/examples/input.json');
file_get_contents(__DIR__ . '/../examples/../fixtures/input.json');
file_get_contents('https://example.org/examples/input.json');
file_get_contents('examples/input.json');
\Tests\Fixture\ForbidDependency\Project\src\file_get_contents(__DIR__ . '/../examples/input.json');
new Reader();
$reader = file_get_contents(...);

function unknown(string $path, string $class): void
{
    file_get_contents($path);
    new $class();
}
