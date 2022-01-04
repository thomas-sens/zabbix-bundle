<?php
namespace Flowti\ZabbixBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * ZabbixExtension
 */
class ZabbixExtension extends ConfigurableExtension
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    
        $this->recursiveSettingContainerParameters($container, ['flowti_zabbix'], $mergedConfig);
        /*
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        foreach ($config as $key => $value) {
            $container->setParameter('flowti_zabbix.' . $key, $value);
        }
        */
    }

    /**
     * This function is providing the configuration parameters
     * to the container for retrieving the variables by calling $container->getParamter('')
     *
     * @param       $container
     * @param array $pathArray
     * @param array $array
     */
    protected function recursiveSettingContainerParameters(&$container, array $pathArray, array $array)
    {
        foreach ($array AS $key => $value) {
            /**
             * this step could cause problems when we have a configuration schema which
             * requires to set an array as values
             */
            if (is_array($value)) {
                $pathArray[] = $key;
                $this->recursiveSettingContainerParameters($container, $pathArray, $value);
            } else {
                $container->setParameter(implode('.', $pathArray) . '.' . $key, $value);
            }
        }
    }
}