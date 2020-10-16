<?php

namespace AssetManager\Service;

use AssetManager\Resolver\AggregateResolver;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory class for AssetManagerService
 *
 * @category   AssetManager
 * @package    AssetManager
 */
class AssetManagerServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $assetManagerConfig = array();

        if (isset($config['asset_manager'])
            && is_array($config['asset_manager'])
            && count($config['asset_manager']) > 0
        ) {
            $assetManagerConfig = $config['asset_manager'];
        }

        $assetManager = new AssetManager(
            $container->get(AggregateResolver::class),
            $assetManagerConfig
        );

        $assetManager->setAssetFilterManager(
            $container->get(AssetFilterManager::class)
        );

        $assetManager->setAssetCacheManager(
            $container->get(AssetCacheManager::class)
        );

        return $assetManager;
    }
}
