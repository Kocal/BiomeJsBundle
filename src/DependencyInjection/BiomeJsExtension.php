<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

final class BiomeJsExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        /** @var array<string> $config */
        $config = $this->processConfiguration($configuration, $configs);

        $container->getDefinition('biomejs.binary')
            ->setArgument(2, $config['binary_version']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }

    public function getAlias(): string
    {
        return 'kocal_biome_js';
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->getAlias());
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->scalarNode('binary_version')
                    ->info('Biome.js CLI version to download - null means the latest version')
                    ->example('v1.7.3')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
