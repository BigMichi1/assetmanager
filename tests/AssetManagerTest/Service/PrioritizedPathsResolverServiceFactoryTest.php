<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\PrioritizedPathsResolver;
use AssetManager\Service\PrioritizedPathsResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PrioritizedPathsResolverServiceFactoryTest extends TestCase
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
                        'prioritized_paths' => array(
                            array(
                                'path' => 'dir3',
                                'priority' => 750,
                            ),
                            array(
                                'path' => 'dir2',
                                'priority' => 1000,
                            ),
                            array(
                                'path' => 'dir1',
                                'priority' => 500,
                            ),
                        ),
                    ),
                ),
            )
        );

        $factory = new PrioritizedPathsResolverServiceFactory();
        /* @var $resolver PrioritizedPathsResolver */
        $resolver = $factory($serviceManager, PrioritizedPathsResolver::class);

        $fetched = array();

        foreach ($resolver->getPaths() as $path) {
            $fetched[] = $path;
        }

        Assert::assertSame(
            array('dir2' . DIRECTORY_SEPARATOR, 'dir3' . DIRECTORY_SEPARATOR, 'dir1' . DIRECTORY_SEPARATOR),
            $fetched
        );
    }

    /**
     * Mainly to avoid regressions
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());

        $factory = new PrioritizedPathsResolverServiceFactory();
        /* @var $resolver PrioritizedPathsResolver */
        $resolver = $factory($serviceManager, PrioritizedPathsResolver::class);
        Assert::assertEmpty($resolver->getPaths()->toArray());
    }
}
