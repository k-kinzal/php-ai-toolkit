<?php

declare(strict_types=1);

namespace Tests\PhpStan\RequireExhaustiveDispatch;

interface Shape
{
}

final class Circle implements Shape
{
}

final class Square implements Shape
{
}

final class Triangle implements Shape
{
}
