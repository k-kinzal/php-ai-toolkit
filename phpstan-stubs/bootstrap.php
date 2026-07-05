<?php

declare(strict_types=1);

if (!class_exists('PHPUnit\Framework\Attributes\CoversClass')) {
    require_once __DIR__ . '/phpunit-attributes.stub';
}

if (!class_exists('PHPUnit\Framework\MockObject\Generator\Generator')) {
    require_once __DIR__ . '/phpunit-mock-generator.stub';
}

if (!interface_exists('PHPUnit\Framework\TestListener') || !class_exists('PHPUnit\Framework\Warning')) {
    require_once __DIR__ . '/phpunit-test-listener.stub';
}

if (!class_exists('PHPUnit\Metadata\MetadataCollection')) {
    require_once __DIR__ . '/phpunit-metadata.stub';
}

if (!class_exists('PHPUnit\TextUI\Configuration\Configuration')) {
    require_once __DIR__ . '/phpunit-textui-configuration.stub';
}

if (!class_exists('PHPUnit\Event\Code\Test') && !interface_exists('PHPUnit\Event\Code\Test')) {
    require_once __DIR__ . '/phpunit-events.stub';
}

if (!class_exists('PHPUnit\Event\Telemetry\CpuTime')) {
    require_once __DIR__ . '/phpunit-telemetry-cputime.stub';
}

if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
    require_once __DIR__ . '/phpunit-runner-extension.stub';
}
