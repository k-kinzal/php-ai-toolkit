<?php

namespace Tests\Fixture\ForbidDependency\Project\bench;

new \Tests\Fixture\ForbidDependency\Project\src\Reader();
file_get_contents(__DIR__ . '/input.json');
file_get_contents(__DIR__ . '/../tests/input.json');
file_get_contents(__DIR__ . '/../examples/input.json');
