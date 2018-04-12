<?php

namespace Mapado\RestClientSdkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MapadoRestClientSdkExtension extends Extension
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
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        // store global informations
        $this->debug = $config['debug'];
        $this->cacheDir = $config['cache_dir'];

        // load entity managers
        if (!empty($config['entity_managers'])) {
            $this->loadEntityManagerList($config['entity_managers'], $container);
        }

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration(
            $container->getParameter('kernel.debug'),
            $container->getParameter('kernel.cache_dir')
        );
    }

    /**
     * loadEntityManagerList
     *
     * @param array $entityManagerList
     * @param ContainerBuilder $container
     * @access private
     * @return void
     */
    private function loadEntityManagerList(array $entityManagerList, ContainerBuilder $container)
    {
        $allClients = [];
        foreach ($entityManagerList as $key => $entityManager) {
            $clientName = $this->loadEntityManager($key, $entityManager, $container);
            $allClients[] = new Reference($clientName);
        }

        $definition = new Definition('SplFixedArray', [$allClients]);
        $definition->setFactory(['SplFixedArray', 'fromArray']);

        $mapping = new Definition(
            'Mapado\RestClientSdkBundle\DataCollector\RestClientSdkDataCollector',
            [
                $definition
            ]
        );
        $mapping->setPublic(false);
        $mapping->addTag(
            'data_collector',
            ['template' => '@MapadoRestClientSdk/Collector/rest_client_sdk.html.twig', 'id' => 'mapado_rest_client_sdk']
        );

        $container->setDefinition('mapado.rest_client_sdk.data_collector', $mapping);
    }

    /**
     * loadEntityManager
     *
     * @param string $key
     * @param array $config
     * @param ContainerBuilder $container
     * @access private
     * @return void
     */
    private function loadEntityManager($key, array $config, ContainerBuilder $container)
    {
        // create http client
        $guzzleServiceName = sprintf('mapado.rest_client_sdk.%s_http_client', $key);
        $args = [];
        if (!empty($config['request_headers'])) {
            $args = [
                ['headers' => $config['request_headers']],
            ];
        }
        $guzzle = new Definition('GuzzleHttp\Client', $args);
        $guzzle->setPublic(false);
        $container->setDefinition($guzzleServiceName, $guzzle);

        $restClient = new Definition(
            'Mapado\RestClientSdkBundle\RequestAwareRestClient',
            [
                new Reference($guzzleServiceName),
                $config['server_url']
            ]
        );
        $restClient->addMethodCall('setRequestStack', [new Reference('request_stack')]);
        $restClient->setPublic(false);

        $container->setDefinition(
            sprintf('mapado.rest_client_sdk.%s_rest_client', $key),
            $restClient
        )->addMethodCall('setLogHistory', [$this->debug]);

        $annotationDriver = new Definition(
            'Mapado\RestClientSdk\Mapping\Driver\AnnotationDriver',
            [
                $this->cacheDir,
                $this->debug,
            ]
        );

        $entityListMapping = new Definition(
            'array',
            [$config['mappings']['dir']]
        );
        $entityListMapping->setFactory([
            $annotationDriver,
            'loadDirectory'
        ]);

        $mapping = new Definition(
            'Mapado\RestClientSdk\Mapping',
            [
                $config['mappings']['prefix'],
                $config['mappings']['configuration'],
            ]
        );
        $mapping->setPublic(false);

        $container->setDefinition(
            sprintf('mapado.rest_client_sdk.%s_mapping', $key),
            $mapping
        )->addMethodCall('setMapping', [$entityListMapping]);

        $unitOfWork = new Definition(
            'Mapado\RestClientSdk\UnitOfWork',
            [
                new Reference(sprintf('mapado.rest_client_sdk.%s_mapping', $key)),
            ]
        );
        $unitOfWork->setPublic(false);
        $container->setDefinition(
            sprintf('mapado.rest_client_sdk.%s_unit_of_work', $key),
            $unitOfWork
        );

        $sdkDefinition = new Definition(
            'Mapado\RestClientSdk\SdkClient',
            [
                new Reference(sprintf('mapado.rest_client_sdk.%s_rest_client', $key)),
                new Reference(sprintf('mapado.rest_client_sdk.%s_mapping', $key)),
                new Reference(sprintf('mapado.rest_client_sdk.%s_unit_of_work', $key)),
            ]
        );
        $sdkDefinition->addMethodCall('setFileCachePath', [ $this->cacheDir ]);

        if (!empty($config['cache']['cache_item_pool'])) {
            $sdkDefinition->addMethodCall(
                'setCacheItemPool',
                [
                    new Reference($config['cache']['cache_item_pool']),
                    $config['cache']['cache_prefix']
                ]
            );
        }

        $sdkClientName = sprintf('mapado.rest_client_sdk.%s', $key);
        $container->setDefinition(
            $sdkClientName,
            $sdkDefinition
        );

        return $sdkClientName;
    }
}
