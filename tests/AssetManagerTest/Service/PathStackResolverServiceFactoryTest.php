<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\PathStackResolver;
use AssetManager\Service\PathStackResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PathStackResolverServiceFactoryTest extends TestCase
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
                        'paths' => array(
                            'path1/',
                            'path2/',
                        ),
                    ),
                ),
            )
        );

        $factory = new PathStackResolverServiceFactory();
        /* @var $resolver PathStackResolver */
        $resolver = $factory($serviceManager, PathStackResolver::class);
        Assert::assertSame(
            array(
                'path2/',
                'path1/',
            ),
            $resolver->getPaths()->toArray()
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());

        $factory = new PathStackResolverServiceFactory();
        /* @var $resolver PathStackResolver */
        $resolver = $factory($serviceManager, PathStackResolver::class);
        Assert::assertEmpty($resolver->getPaths()->toArray());
    }
}
