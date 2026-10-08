<?php

namespace Tests\PhpStan\ForbidDependency\Project\examples;

trait ExampleTrait
{
    public function exampleInput(): string
    {
        return file_get_contents(__DIR__ . '/input.json');
    }
}
