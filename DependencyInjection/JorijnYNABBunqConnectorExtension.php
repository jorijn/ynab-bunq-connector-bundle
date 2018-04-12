<?php

namespace Jorijn\YNAB\BunqConnectorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class JorijnYNABBunqConnectorExtension extends Extension
{
    const YNAB_BUNQ_CONNECTOR_CONNECTIONS = 'ynab_bunq_connector.connections';
    const JORIJN_YNAB_BUNQ_CONNECTOR_CONFIGURATION = 'jorijn_ynab_bunq_connector.configuration';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter(self::YNAB_BUNQ_CONNECTOR_CONNECTIONS, $config['connections']);
        $container->getDefinition(self::JORIJN_YNAB_BUNQ_CONNECTOR_CONFIGURATION)->replaceArgument(0, $config['api_key']);
    }
}
