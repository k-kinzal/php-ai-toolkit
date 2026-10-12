<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Toolkit\Rector\RiskyRules;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules(RiskyRules::rules());
};
