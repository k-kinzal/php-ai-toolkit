<?php

namespace Tests\Fixture\ForbidDependency\Project\examples;

new \Tests\Fixture\ForbidDependency\Project\src\Reader();
file_get_contents(__DIR__ . '/input.json');
file_get_contents(__DIR__ . '/../example/local.php');
file_get_contents(__DIR__ . '/../tests/input.json');
