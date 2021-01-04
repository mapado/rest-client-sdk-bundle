<?php

namespace Mapado\RestClientSdkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * debug
     *
     * @var boolean
     * @access private
     */
    private $debug;

    /**
     * cacheDir
     *
     * @var string
     * @access private
     */
    private $cacheDir;

    /**
     * __construct
     *
     * @param boolean $debug
     * @param string $cacheDir
     * @access public
     */
    public function __construct($debug, $cacheDir)
    {
        $this->debug = (bool) $debug;
        $this->cacheDir = $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('mapado_rest_client_sdk');

        if (method_exists($treeBuilder, 'root')) {
            // for Symfony 2 & 3
            $rootNode = $treeBuilder->root('mapado_rest_client_sdk');
        }

        $rootNode
            ->children()
                ->booleanNode('debug')
                    ->defaultValue($this->debug)
                ->end()
                ->scalarNode('cache_dir')
                    ->defaultValue(sprintf('%s/mapado/rest_client_sdk_bundle/', $this->cacheDir))
                ->end()

                ->arrayNode('entity_managers')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('server_url')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('mappings')
                                ->children()
                                    ->scalarNode('prefix')->end()
                                    ->arrayNode('configuration')
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->scalarNode('dir')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('cache')
                                ->children()
                                    ->scalarNode('cache_item_pool')->end()
                                    ->scalarNode('cache_prefix')->end()
                                ->end()
                            ->end()
                            ->arrayNode('request_headers')
                                ->useAttributeAsKey('name')
                                    ->prototype('scalar')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
