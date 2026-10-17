<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Converts a visibility violation into a PHPStan rule error.
 */
final class VisibilityRuleErrorBuilder
{
    /**
     * Builds a file-specific PHPStan error from one visibility violation.
     *
     * @param array{file: string, line: int, identifier: string, symbol: string, message: string} $violation
     * @return IdentifierRuleError&FileRuleError&LineRuleError&MetadataRuleError
     */
    public function build(array $violation): IdentifierRuleError
    {
        return RuleErrorBuilder::message($violation['message'])
            ->file($violation['file'])
            ->line($violation['line'])
            ->identifier($violation['identifier'])
            ->metadata(['symbol' => $violation['symbol']])
            ->build();
    }
}
