<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\MapResolver;
use AssetManager\Service\MapResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class MapResolverServiceFactoryTest extends TestCase
{
    /**
     * Mainly to avoid regressions
     */
    public function testCreateService()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolver_configs' => array(
                        'map' => array(
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ),
                    ),
                ),
            )
        );

        $factory = new MapResolverServiceFactory();
        /* @var MapResolver */
        $mapResolver = $factory($serviceManager, MapResolver::class);
        $this->assertSame(
            array(
                'key1' => 'value1',
                'key2' => 'value2',
            ),
            $mapResolver->getMap()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());

        $factory = new MapResolverServiceFactory();
        /* @var MapResolver */
        $mapResolver = $factory($serviceManager, MapResolver::class);
        $this->assertEmpty($mapResolver->getMap());
    }
}
