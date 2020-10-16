<?php

namespace AssetManagerTest\Service;

use AssetManager\Service\AssetCacheManager;
use AssetManager\Service\AssetCacheManagerServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class AssetCacheManagerServiceFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'caching' => array(
                        'default' => array(
                            'cache' => 'Filesystem',
                        ),
                    ),
                ),
            )
        );

        $serviceFactory = new AssetCacheManagerServiceFactory();

        $service = $serviceFactory($serviceManager, AssetCacheManager::class);

        Assert::assertTrue($service instanceof AssetCacheManager);
    }
}
