<?php

declare(strict_types=1);

if (!class_exists('PHPUnit\Framework\Attributes\CoversClass')) {
    require_once dirname(__DIR__) . '/src/Compatibility/PhpUnit/phpunit-attributes.stub';
}

if (!class_exists('PHPUnit\Framework\MockObject\Generator\Generator')) {
    require_once dirname(__DIR__) . '/src/Compatibility/PhpUnit/phpunit-mock-generator.stub';
}

if (!class_exists('PHPUnit\TextUI\Configuration\Configuration')) {
    require_once dirname(__DIR__) . '/src/Compatibility/PhpUnit/phpunit-textui-configuration.stub';
}

if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
    require_once dirname(__DIR__) . '/src/Compatibility/PhpUnit/phpunit-runner-extension.stub';
}
