<?php

namespace RectorFixtures\EmptyCatch;

function run(): void
{
    try {
        work();
    } catch (\RuntimeException $error) {
    }
    try {
        work();
    } catch (\RuntimeException) {
    }
    try {
        work();
    } catch (\RuntimeException $handled) {
        report($handled);
    }
}

-----
<?php

namespace RectorFixtures\EmptyCatch;

function run(): void
{
    try {
        work();
    } catch (\RuntimeException $error) {
        throw $error;
    }
    try {
        work();
    } catch (\RuntimeException) {
    }
    try {
        work();
    } catch (\RuntimeException $handled) {
        report($handled);
    }
}
