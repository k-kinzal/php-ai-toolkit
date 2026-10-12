<?php

namespace RectorFixtures\SafeSet;

/** Public class. */
class Example
{
    public function run(): void
    {
        try {
            work();
        } catch (\RuntimeException $error) {
        }
    }
}

-----
<?php

namespace RectorFixtures\SafeSet;

/**
 * Public class.
 */
class Example
{
    public function run(): void
    {
        try {
            work();
        } catch (\RuntimeException $error) {
        }
    }
}
