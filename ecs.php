<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
    ])
    ->withPreparedSets(psr12: true, strict: true)
    ->withPhpCsFixerSets(symfony: true)
 ;
