<?php

namespace RectorFixtures\RiskySet;

/** Public class. */
class Example
{
    public function run(): void
    {
        try {
            work();
        } catch (\RuntimeException $error) {
            throw new \InvalidArgumentException('wrapped');
        }
    }
}

-----
<?php

namespace RectorFixtures\RiskySet;

/** Public class. */
class Example
{
    /**
     * @throws \InvalidArgumentException
     */
    public function run(): void
    {
        try {
            work();
        } catch (\RuntimeException $error) {
            throw new \InvalidArgumentException('wrapped', previous: $error);
        }
    }
}
