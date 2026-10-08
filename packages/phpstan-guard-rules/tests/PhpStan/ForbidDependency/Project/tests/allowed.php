<?php

namespace Tests\PhpStan\ForbidDependency\Project\tests;

use Tests\PhpStan\ForbidDependency\Project\src\Reader;

echo 'See examples/input.json';
// Documentation: examples/input.json
/** Example: examples/input.json */
file_get_contents(__DIR__ . '/data/input.json');
file_get_contents(__DIR__ . '/Unit/input.json');
file_get_contents(__DIR__ . '/../vendor/demo/examples/input.json');
file_get_contents(__DIR__ . '/../examples/../src/Reader.php');
file_get_contents('https://example.org/examples/input.json');
file_get_contents('examples/input.json');
\Tests\PhpStan\ForbidDependency\Project\src\file_get_contents(__DIR__ . '/../examples/input.json');
new Reader();
$reader = file_get_contents(...);
file_get_contents(dirname(__DIR__) . '/composer.json');

function unknown(string $path, string $class): void
{
    file_get_contents($path);
    new $class();
}
