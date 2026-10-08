<?php

namespace Tests\PhpStan\ForbidDependency\Project\tests;

use Tests\PhpStan\ForbidDependency\Project\examples\ExampleService as Demo;
use function Tests\PhpStan\ForbidDependency\Project\examples\sample as demo;
use const Tests\PhpStan\ForbidDependency\Project\examples\SAMPLE;

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
