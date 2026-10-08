<?php

namespace Tests\PhpStan\ForbidDependency\Project\src;

class Reader
{
    public static function load(string $path): string
    {
        return $path;
    }

    public function read(string $path): string
    {
        return $path;
    }
}

function read_config(string $path): string
{
    return $path;
}

function file_get_contents(string $filename): string
{
    return $filename;
}
