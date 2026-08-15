<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidBroadCatch;

use DivisionByZeroError;
use Exception;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;
use TypeError;

final class WithBroadCatch
{
    public function catchesThrowable(): int
    {
        try {
            return 1;
        } catch (Throwable $exception) {
            return $exception->getCode();
        }
    }

    public function catchesException(): int
    {
        try {
            return 1;
        } catch (Exception $exception) {
            return $exception->getCode();
        }
    }

    public function catchesLogicException(): int
    {
        try {
            return 1;
        } catch (LogicException $exception) {
            return $exception->getCode();
        }
    }

    public function catchesLogicSubclass(): int
    {
        try {
            return 1;
        } catch (InvalidArgumentException $exception) {
            return $exception->getCode();
        }
    }

    public function catchesError(): int
    {
        try {
            return 1;
        } catch (TypeError $exception) {
            return $exception->getCode();
        }
    }

    public function catchesUnionWithError(): int
    {
        try {
            return 1;
        } catch (DivisionByZeroError|RuntimeException $exception) {
            return $exception->getCode();
        }
    }
}
