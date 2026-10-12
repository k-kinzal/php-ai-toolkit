<?php

namespace RectorFixtures\Throws;

use RuntimeException as Failure;

class Example
{
    public function fail(): void
    {
        if (rand(0, 1)) {
            throw new Failure('one');
        }
        throw new Failure('two');
    }

    /**
     * Existing description.
     * @throws \RuntimeException
     */
    public function documented(): void
    {
        throw new Failure();
    }

    public function caught(): void
    {
        try {
            throw new Failure();
        } catch (\RuntimeException $error) {
            report($error);
        }
        $later = static function () { throw new \LogicException(); };
    }

    public function generic(): void
    {
        throw new \Exception();
    }
}

-----
<?php

namespace RectorFixtures\Throws;

use RuntimeException as Failure;

class Example
{
    /**
     * @throws \RuntimeException
     */
    public function fail(): void
    {
        if (rand(0, 1)) {
            throw new Failure('one');
        }
        throw new Failure('two');
    }

    /**
     * Existing description.
     * @throws \RuntimeException
     */
    public function documented(): void
    {
        throw new Failure();
    }

    public function caught(): void
    {
        try {
            throw new Failure();
        } catch (\RuntimeException $error) {
            report($error);
        }
        $later = static function () { throw new \LogicException(); };
    }

    public function generic(): void
    {
        throw new \Exception();
    }
}
