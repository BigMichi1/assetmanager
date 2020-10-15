<?php

namespace AssetManager\Controller;

use AssetManager\Service\AssetManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ConsoleControllerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $console = $container->get('console');
        $assetManager = $container->get(AssetManager::class);
        $appConfig = $container->get('config');

        return new ConsoleController($console, $assetManager, $appConfig);
    }
}
