<?php

namespace Jorijn\YNAB\BunqConnectorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jorijn_ynab_bunq_connector');

        $rootNode->children()
            ->arrayNode('connections')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('bunq_account_id')->end()
                        ->scalarNode('ynab_budget_id')->end()
                        ->scalarNode('ynab_account_id')->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('api_key')->end()
        ->end();

        return $treeBuilder;
    }
}
