<?php

namespace AssetManagerTest\Service;

use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetFilterManagerServiceFactory;
use AssetManager\Service\MimeResolver;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class AssetFilterManagerServiceFactoryTest extends TestCase
{
    public function testConstruct()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'filters' => array(
                        'css' => array(
                            'filter' => 'Lessphp',
                        ),
                    ),
                ),
            )
        );

        $serviceManager->setService(MimeResolver::class, new MimeResolver);

        $serviceFactory = new AssetFilterManagerServiceFactory();

        $service = $serviceFactory($serviceManager, AssetFilterManager::class);

        Assert::assertTrue($service instanceof AssetFilterManager);
    }
}
