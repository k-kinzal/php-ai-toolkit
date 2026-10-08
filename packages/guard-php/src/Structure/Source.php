<?php

declare(strict_types=1);

namespace Guard\Structure;

use Guard\Diagnostic\PolicyException;
use JsonException;
use RuntimeException;

/**
 * Shares bytes and parsed structures across all demands for one physical file.
 */
final class Source
{
    /** @var array<string, Subject|RuntimeException|JsonException|\Nette\Neon\Exception> */
    private array $values = [];
    /** @var array<string, true> */
    private array $active = [];
    /**
     * @param array<string, Structurer> $structurers
     */
    public function __construct(private string $text, private array $structurers)
    {
    }
    /**
     * Returns the bytes already read by the collector.
     */
    public function text(): string
    {
        return $this->text;
    }
    /** Computes each requested structure at most once, including shared dependencies.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function structure(string $id): Subject
    {
        if (isset($this->active[$id])) {
            throw new PolicyException('Structure "' . $id . '" has a circular dependency. Remove the cycle from its structurer.');
        }
        if (!isset($this->values[$id])) {
            if (!isset($this->structurers[$id])) {
                throw new PolicyException('Structure "' . $id . '" is not registered. Register its structurer before running policies.');
            }
            $this->active[$id] = true;
            try {
                $this->values[$id] = $this->structurers[$id]->structure($this);
            } catch (RuntimeException|JsonException|\Nette\Neon\Exception $error) {
                $this->values[$id] = $error;
            } finally {
                unset($this->active[$id]);
            }
        }
        $value = $this->values[$id];
        if ($value instanceof Subject) {
            return $value;
        }
        throw $value;
    }
}
