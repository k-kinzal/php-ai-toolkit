<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidDependency\Project\examples;

class ExampleService
{
    public const VALUE = 'sample';
    public string $value = 'sample';

    public function __construct() {}

    public function run(): string
    {
        return 'sample';
    }

    public static function create(): self
    {
        return new self();
    }
}

function sample(): string
{
    return 'sample';
}

const SAMPLE = 'sample';
