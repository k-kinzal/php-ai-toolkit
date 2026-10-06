<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Collect\Subject;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use JsonException;

/**
 * Evaluates collected information and proposes changes without writing files.
 */
interface Policy
{
    /**
     * Returns findings and proposed changes for the registered information type.
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     * @throws PolicyException
     */
    public function evaluate(Subject $information, Context $context): Plan;
}
