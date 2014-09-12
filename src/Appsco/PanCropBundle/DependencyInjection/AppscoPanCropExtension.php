<?php

namespace Appsco\PanCropBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AppscoPanCropExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        $yamlLoader = new YamlFileLoader($container, $locator);

        $yamlLoader->load('services.yml');
    }
} 