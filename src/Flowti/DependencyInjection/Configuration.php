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
        ->arrayNode('client')->addDefaultsIfNotSet()->info('Client related information for accessing the Zabbix API')
        ->children()
        ->scalarNode('host')->cannotBeEmpty()->defaultValue('https://your-zabbix-server/zabbix/api_jsonrpc.php')->info('Endpoint for the Zabbix Server API')->example('https://example.com/zabbix/api_jsonrpc.php')->end()
        ->scalarNode('username')->cannotBeEmpty()->defaultValue('guest')->info('Username of user with access to zabbix server')->example('guest')->end()
        ->scalarNode('password')->defaultValue('')->info('Password of user with access to zabbix server')->example('p@ssw0rd!')->end()
        ->end()
        ->end() // client
        ->end();

        return $treeBuilder;
    }
}