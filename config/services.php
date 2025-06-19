<?php

declare(strict_types=1);

use Kocal\BiomeJsBundle\Command\BiomeJsDownloadCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('biomejs.command.download', BiomeJsDownloadCommand::class)
        ->args([
            abstract_arg('Biome.js binary version'),
            service('filesystem'),
        ])
        ->tag('console.command');
};
