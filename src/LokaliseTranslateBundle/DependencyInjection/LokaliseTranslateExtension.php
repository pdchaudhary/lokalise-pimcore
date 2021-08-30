<?php

namespace Pdchaudhary\LokaliseTranslateBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class LokaliseTranslateExtension  extends ConfigurableExtension implements PrependExtensionInterface
{

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine_migrations')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../Resources/config')
            );

            $loader->load('doctrine_migrations.yml');
        }
    }

  

     /**
     * {@inheritdoc}
     */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $container->setParameter('lokalise_translate', $config);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
    }
}
