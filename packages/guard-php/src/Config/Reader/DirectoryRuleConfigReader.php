<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use function array_keys;

use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DirectoryRuleConfig;

use function in_array;
use function is_array;
use function sprintf;

/**
 * Reads one tree.yaml rule block, rejecting unknown keys so that a typo
 * cannot silently disable a constraint.
 */
final class DirectoryRuleConfigReader
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
    private DirectoryConfigScalarReader $scalarReader;

    /** @readonly */
    private DirectoryConfigStringListReader $stringListReader;

    /**
     * Creates a reader from scalar and list validation.
     */
    public function __construct(
        ?DirectoryConfigScalarReader $scalarReader = null,
        ?DirectoryConfigStringListReader $stringListReader = null,
    ) {
        $this->scalarReader = $scalarReader ?? new DirectoryConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new DirectoryConfigStringListReader();
    }

    /**
     * Reads one rule block at the given list index.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the rule block is not a mapping or contains unsupported keys
     */
    public function read($value, int $index): DirectoryRuleConfig
    {
        $context = sprintf('rules[%d]', $index);
        if (!is_array($value)) {
            throw new PolicyException(sprintf('Invalid tree.yaml: "%s" must be a mapping.', $context));
        }

        foreach (array_keys($value) as $key) {
            if (!in_array((string) $key, self::KNOWN_KEYS, true)) {
                throw new PolicyException(sprintf('Invalid tree.yaml: "%s" contains unsupported key "%s".', $context, $key));
            }
        }

        return new DirectoryRuleConfig(
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
