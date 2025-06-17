<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\DependencyInjection;

use Kocal\BiomeJsBundle\BiomeJsBinary;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

final class BiomeJsExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        /** @var array<string> $config */
        $config = $this->processConfiguration($configuration, $configs);

        if (in_array($config['binary_version'], [BiomeJsBinary::LATEST_STABLE_VERSION, BiomeJsBinary::LATEST_NIGHTLY_VERSION], true)) {
            trigger_deprecation('kocal/biome-js-bundle', '1.5', 'Using "%s" version is deprecated and will be removed in the next major version, configure "kocal_biome_js.binary_version" to use a specific version instead (e.g.: "v1.9.4").', $config['binary_version']);
        }

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
                    ->info('Biome.js CLI version to download, can be either a specific version, "latest_stable" (deprecated) or "latest_nightly" (deprecated).')
                    ->example([
                        'v1.9.4',
                        'latest_stable',
                        'latest_nightly',
                    ])
                    ->defaultValue('latest_stable')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
