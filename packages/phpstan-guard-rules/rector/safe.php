<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Toolkit\Rector\SafeRules;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules(SafeRules::rules());
};
