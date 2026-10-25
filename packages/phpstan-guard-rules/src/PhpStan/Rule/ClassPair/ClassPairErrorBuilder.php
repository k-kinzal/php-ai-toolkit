<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for classes whose paired class does not exist.
 */
final class ClassPairErrorBuilder
{
    /**
     * @param ComposerAutoloadMap $autoload PSR-4 entries that locate the file of the missing class
     */
    public function __construct(
        /** @readonly */
        private ComposerAutoloadMap $autoload,
    ) {
    }

    /**
     * Builds an error for a class whose counterpart is missing.
     */
    public function missing(string $class, string $counterpart, ClassPair $pair, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Create class "%s"%s for class "%s": every class matching "%s" needs a class matching "%s".',
            $counterpart,
            $this->location($counterpart),
            $class,
            $pair->fromPattern(),
            $pair->toPattern(),
        ))
            ->identifier('customRules.classPairMissing')
            ->line($line)
            ->build();
    }

    /**
     * Builds an error for a counterpart whose origin class is missing.
     */
    public function orphaned(string $class, string $origin, ClassPair $pair, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Create class "%s"%s for class "%s", or remove "%s": every class matching "%s" needs a class matching "%s".',
            $origin,
            $this->location($origin),
            $class,
            $class,
            $pair->toPattern(),
            $pair->fromPattern(),
        ))
            ->identifier('customRules.classPairOrphaned')
            ->line($line)
            ->build();
    }

    /**
     * Returns " in <file>" for a class PSR-4 can place, or an empty string.
     */
    public function location(string $class): string
    {
        $file = $this->autoload->fileForClass($class);

        return $file === null ? '' : sprintf(' in %s', $file);
    }
}
