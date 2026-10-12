<?php

namespace RectorFixtures\Chaining;

use RuntimeException as Failure;

function run(): void
{
    try {
        work();
    } catch (\RuntimeException $error) {
        if (rand(0, 1)) {
            throw new Failure('failed', 1);
        }
        throw new \InvalidArgumentException(message: 'bad');
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        throw new Failure('chained', 0, $error);
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        throw new Failure('explicit', previous: null);
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        throw new CustomFailure('custom');
    }
    try {
        work();
    } catch (\RuntimeException) {
        throw new Failure('unbound');
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        $later = static function () { throw new Failure('later'); };
        throw new Failure(...arguments());
    }
}

-----
<?php

namespace RectorFixtures\Chaining;

use RuntimeException as Failure;

function run(): void
{
    try {
        work();
    } catch (\RuntimeException $error) {
        if (rand(0, 1)) {
            throw new Failure('failed', 1, previous: $error);
        }
        throw new \InvalidArgumentException(message: 'bad', previous: $error);
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        throw new Failure('chained', 0, $error);
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        throw new Failure('explicit', previous: null);
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        throw new CustomFailure('custom');
    }
    try {
        work();
    } catch (\RuntimeException) {
        throw new Failure('unbound');
    }
    try {
        work();
    } catch (\RuntimeException $error) {
        $later = static function () { throw new Failure('later'); };
        throw new Failure(...arguments());
    }
}
