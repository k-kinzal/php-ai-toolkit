<?php

namespace Tests\Fixture\ForbidDependency\Project\tests;

use Tests\Fixture\ForbidDependency\Project\examples\ExampleService as Demo;
use function Tests\Fixture\ForbidDependency\Project\examples\sample as demo;
use const Tests\Fixture\ForbidDependency\Project\examples\SAMPLE;

new Demo();
Demo::create();
echo Demo::VALUE;
echo Demo::class;
demo();
echo SAMPLE;

function consume(Demo $example): Demo
{
    $example->run();
    echo $example->value;
    $example instanceof Demo;
    $class = Demo::class;
    new $class();
    return $example;
}
