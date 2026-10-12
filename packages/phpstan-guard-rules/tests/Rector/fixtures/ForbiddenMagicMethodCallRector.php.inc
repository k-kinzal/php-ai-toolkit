<?php

namespace RectorFixtures\Magic;

function run(object $value): void
{
    $text = $value->__toString();
    $result = $value->__invoke(1, name: 'value');
    $closure = $value->__invoke(...);
    $property = $value->__get('name');
    $nullable = $value?->__toString();
    $value->__toString(extra());
}

-----
<?php

namespace RectorFixtures\Magic;

function run(object $value): void
{
    $text = (string) $value;
    $result = $value(1, name: 'value');
    $closure = $value->__invoke(...);
    $property = $value->__get('name');
    $nullable = $value?->__toString();
    $value->__toString(extra());
}
