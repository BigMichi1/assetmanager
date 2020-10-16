<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\CollectionResolver;
use AssetManager\Service\CollectionResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class CollectionResolverServiceFactoryTest extends TestCase
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
                        'collections' => array(
                            'key1' => 'value1',
                            'key2' => 'value2',
                        ),
                    ),
                ),
            )
        );

        $factory = new CollectionResolverServiceFactory();
        /* @var CollectionResolver */
        $collectionsResolver = $factory($serviceManager, CollectionResolver::class);
        Assert::assertSame(
            array(
                'key1' => 'value1',
                'key2' => 'value2',
            ),
            $collectionsResolver->getCollections()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());

        $factory = new CollectionResolverServiceFactory();
        /* @var CollectionResolver */
        $collectionsResolver = $factory($serviceManager, CollectionResolver::class);
        Assert::assertEmpty($collectionsResolver->getCollections());
    }
}
