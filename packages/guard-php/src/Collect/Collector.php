<?php

declare(strict_types=1);

namespace Guard\Collect;

use Guard\Execution\Context;
use JsonException;

/**
 * Reads and structures inputs without evaluating constraints or writing files.
 */
interface Collector
{
    /**
     * @return iterable<Subject>
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     * @throws \Guard\Policy\PolicyException
     */
    public function collect(Context $context): iterable;
}
