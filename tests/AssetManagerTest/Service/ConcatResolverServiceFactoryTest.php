<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\ConcatResolver;
use AssetManager\Service\ConcatResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ConcatResolverServiceFactoryTest extends TestCase
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
                        'concat' => array(
                            'key1' => __FILE__,
                            'key2' => __FILE__,
                        ),
                    ),
                ),
            )
        );

        $factory = new ConcatResolverServiceFactory();
        /* @var ConcatResolver */
        $concatResolver = $factory($serviceManager, ConcatResolver::class);
        Assert::assertSame(
            array(
                'key1' => __FILE__,
                'key2' => __FILE__,
            ),
            $concatResolver->getConcats()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());

        $factory = new ConcatResolverServiceFactory();
        /* @var ConcatResolver */
        $concatResolver = $factory($serviceManager, ConcatResolver::class);
        Assert::assertEmpty($concatResolver->getConcats());
    }
}
