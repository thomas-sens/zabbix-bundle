<?php
/**
 * Configuration class.
 */
namespace Flowti\ZabbixBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('flowti_zabbix');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('zabbix_rest_endpoint')->end()
            ->scalarNode('zabbix_rest_endpoint_user')->end()
            ->scalarNode('zabbix_rest_endpoint_pass')->end()
            ->end();

        return $treeBuilder;
    }
}