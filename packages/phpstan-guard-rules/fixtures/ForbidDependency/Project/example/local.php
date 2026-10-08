<?php

namespace Tests\Fixture\ForbidDependency\Project\example;

require __DIR__ . '/../examples/ExampleService.php';
echo file_get_contents(__DIR__ . '/../examples/input.json');
new \Tests\Fixture\ForbidDependency\Project\examples\ExampleService();
