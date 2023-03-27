<?php

namespace Compass\DatadogBundle\DependencyInjection;

use Compass\DatadogBundle\EventListener\LoginFailureEventListener;
use Compass\DatadogBundle\EventListener\LoginSuccessEventListener;
use Compass\DatadogBundle\EventListener\RequestEventListener;
use Compass\DatadogBundle\Service\DatadogService;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CompassDatadogExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $this->configureTrace($config, $container);
        $this->configureAppsec($config, $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return void
     */
    public function configureTrace(array $config, ContainerBuilder $container): void
    {
        if (false === $config['trace']['enabled']) {
            return;
        }

        if (false === extension_loaded('ddtrace')) {
            throw new InvalidConfigurationException("Extension 'ddtrace' must be loaded to enable DataDog tracing.");
        }

        if (false === class_exists($config['trace']['user_entity'])) {
            throw new InvalidConfigurationException("Could not locate the user_entity class '" . $config['user_entity'] . "'.");
        }

        foreach ($config['trace']['user_properties'] as $property) {
            assert(property_exists($config['trace']['user_entity'], $property), "Could not locate property '" . $property . "' on '" . $config['trace']['user_entity'] . "' entity.");
        }

        $container->register(RequestEventListener::class, RequestEventListener::class)
            ->addArgument(new Reference(DatadogService::class))
            ->addArgument(new Reference('security.token_storage'))
            ->addArgument(new Reference('event_dispatcher'))
            ->addArgument($config['trace']['user_properties'])
            ->addTag('kernel.event_subscriber');
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return void
     */
    public function configureAppsec(array $config, ContainerBuilder $container): void
    {
        if (false === $config['appsec']['enabled']) {
            return;
        }

        if (false === extension_loaded('ddappsec')) {
            throw new InvalidConfigurationException("Extension 'ddappsec' must be loaded to enable DataDog ASM.");
        }

        if (false === $config['trace']['enabled']) {
            throw new InvalidConfigurationException("Tracing must be enabled to enable Datadog ASM.");
        }

        $container->register(LoginFailureEventListener::class, LoginFailureEventListener::class)
            ->addArgument(new Reference(DatadogService::class))
            ->addArgument(new Reference('event_dispatcher'))
            ->addTag('kernel.event_subscriber');

        $container->register(LoginSuccessEventListener::class, LoginSuccessEventListener::class)
            ->addArgument(new Reference(DatadogService::class))
            ->addArgument(new Reference('event_dispatcher'))
            ->addTag('kernel.event_subscriber');
    }
}
