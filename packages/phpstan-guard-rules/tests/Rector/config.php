<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Toolkit\Rector\RiskyRules;
use Toolkit\Rector\SafeRules;

return static function (RectorConfig $config): void {
    $config->disableParallel();
    $config->phpVersion(80000);
    $cache = getenv('TOOLKIT_RECTOR_CACHE');
    if (is_string($cache) && $cache !== '') {
        $config->cacheDirectory($cache);
    }
    $set = getenv('TOOLKIT_RECTOR_SET');
    $group = match ($set) {
        'safe-rules' => SafeRules::rules(),
        'risky-rules' => RiskyRules::rules(),
        'all-rules' => [...SafeRules::rules(), ...RiskyRules::rules()],
        default => null,
    };
    if ($group !== null) {
        $builder = RectorConfig::configure()->withRules($group);
        $builder($config);

        return;
    }
    if (is_string($set) && $set !== '') {
        $config->sets([dirname(__DIR__, 2) . '/rector/' . $set . '.php']);
    } else {
        $config->rule('Toolkit\\Rector\\' . getenv('TOOLKIT_RECTOR_RULE'));
    }
};
