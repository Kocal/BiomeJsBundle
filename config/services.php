<?php

use Kocal\BiomeJsBundle\BiomeJs;
use Kocal\BiomeJsBundle\BiomeJsBinary;
use Kocal\BiomeJsBundle\Command\BiomeJsCheckCommand;
use Kocal\BiomeJsBundle\Command\BiomeJsCiCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        // Internal parameter to enable/disable TTY mode, useful for tests (if someone has a better idea, feel free to suggest it!)
        ->set('biomejs.use_tty', true)
    ;

    $container->services()
        ->set('biomejs.binary', BiomeJsBinary::class)
        ->args([
            param('kernel.project_dir'),
            param('kernel.project_dir').'/var/biomejs',
            abstract_arg('Biome.js binary version'),
        ])

        ->set('biomejs', BiomeJs::class)
        ->args([
            service('biomejs.binary'),
            param('biomejs.use_tty')
        ])

        ->set('biomejs.command.ci', BiomeJsCiCommand::class)
        ->args([
            service('biomejs')
        ])
        ->tag('console.command')

        ->set('biomejs.command.check', BiomeJsCheckCommand::class)
        ->args([
            service('biomejs'),
        ])
        ->tag('console.command');
};