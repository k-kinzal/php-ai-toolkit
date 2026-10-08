<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Input\Input;
use Guard\Input\InputSet;
use JsonException;
use RuntimeException;

/**
 * Declares inputs and evaluates their collected structures without filesystem access.
 */
interface Policy
{
    /** Returns named selections and the structures they require.
     * @return array<string, Input>
     */
    public function inputs(Context $context): array;
    /** Evaluates prepared inputs and proposes changes without writing files.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function evaluate(InputSet $inputs, Context $context): Plan;
}
