<?php

namespace AssetManager\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for the Asset Cache Manager Service
 *
 * @package AssetManager\Service
 */
class AssetCacheManagerServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = array();

        $globalConfig = $container->get('config');

        if (isset($globalConfig['asset_manager']['caching'])
            && is_array($globalConfig['asset_manager']['caching'])
            && count($globalConfig['asset_manager']['caching']) > 0) {
            $config = $globalConfig['asset_manager']['caching'];
        }

        return new AssetCacheManager($container, $config);
    }
}
