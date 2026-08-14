<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Config;

use function array_keys;
use function in_array;
use function is_array;

use PhpAiToolkit\TreeGuard\TreeGuardException;

use function sprintf;

/**
 * Reads one tree.yaml rule block, rejecting unknown keys so that a typo
 * cannot silently disable a constraint.
 */
final class RuleConfigReader
{
    /** @var list<string> */
    private const KNOWN_KEYS = [
        'path',
        'max_files',
        'max_dirs',
        'max_total_files',
        'max_depth',
        'allow',
        'deny',
        'allow_dirs',
        'deny_dirs',
        'require',
        'forbid_empty',
        'file_case',
        'dir_case',
    ];

    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /**
     * Creates a reader from scalar and list validation.
     */
    public function __construct(
        ?ConfigScalarReader $scalarReader = null,
        ?ConfigStringListReader $stringListReader = null,
    ) {
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
    }

    /**
     * Reads one rule block at the given list index.
     *
     * @param mixed $value
     */
    public function read($value, int $index): RuleConfig
    {
        $context = sprintf('rules[%d]', $index);
        if (!is_array($value)) {
            throw new TreeGuardException(sprintf('Invalid tree.yaml: "%s" must be a mapping.', $context));
        }

        foreach (array_keys($value) as $key) {
            if (!in_array((string) $key, self::KNOWN_KEYS, true)) {
                throw new TreeGuardException(sprintf('Invalid tree.yaml: "%s" contains unsupported key "%s".', $context, $key));
            }
        }

        return new RuleConfig(
            $this->scalarReader->string($value, 'path', null, $context),
            $this->scalarReader->optionalPositiveInt($value, 'max_files', $context),
            $this->scalarReader->optionalPositiveInt($value, 'max_dirs', $context),
            $this->scalarReader->optionalPositiveInt($value, 'max_total_files', $context),
            $this->scalarReader->optionalPositiveInt($value, 'max_depth', $context),
            $this->stringListReader->readOptional($value, 'allow', $context),
            $this->stringListReader->readOptional($value, 'deny', $context),
            $this->stringListReader->readOptional($value, 'allow_dirs', $context),
            $this->stringListReader->readOptional($value, 'deny_dirs', $context),
            $this->stringListReader->readOptional($value, 'require', $context),
            $this->scalarReader->bool($value, 'forbid_empty', false, $context),
            $this->scalarReader->optionalCase($value, 'file_case', $context),
            $this->scalarReader->optionalCase($value, 'dir_case', $context),
        );
    }
}
