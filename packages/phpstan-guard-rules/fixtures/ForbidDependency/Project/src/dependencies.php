<?php

namespace Tests\Fixture\ForbidDependency\Project\src;

new Reader();
\file_get_contents(__DIR__ . '/data.json');
\file_get_contents(__DIR__ . '/../vendor/package/data.json');
\file_get_contents(__DIR__ . '/../tests/input.json');
\file_get_contents(__DIR__ . '/../examples/input.json');
