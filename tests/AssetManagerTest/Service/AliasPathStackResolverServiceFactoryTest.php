<?php

namespace AssetManagerTest\Service;

use AssetManager\Resolver\AliasPathStackResolver;
use AssetManager\Service\AliasPathStackResolverServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests the factory for the Alias Path Stack Resolver
 */
class AliasPathStackResolverServiceFactoryTest extends TestCase
{
    /**
     * Mainly to avoid regressions
     * @throws \ReflectionException
     */
    public function testCreateService()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolver_configs' => array(
                        'aliases' => array(
                            'alias1/' => 'path1',
                            'alias2/' => 'path2',
                        ),
                    ),
                ),
            )
        );

        $factory = new AliasPathStackResolverServiceFactory();

        /* @var $resolver AliasPathStackResolver */
        $resolver = $factory($serviceManager, AliasPathStackResolver::class);

        $reflectionClass = new \ReflectionClass(AliasPathStackResolver::class);
        $property = $reflectionClass->getProperty('aliases');
        $property->setAccessible(true);

        Assert::assertSame(
            array(
                'alias1/' => 'path1' . DIRECTORY_SEPARATOR,
                'alias2/' => 'path2' . DIRECTORY_SEPARATOR,
            ),
            $property->getValue($resolver)
        );
    }

    /**
     * Mainly to avoid regressions
     *
     * @throws \ReflectionException
     */
    public function testCreateServiceWithNoConfig()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());

        $factory = new AliasPathStackResolverServiceFactory();
        /* @var $resolver AliasPathStackResolver */
        $resolver = $factory($serviceManager, AliasPathStackResolver::class);

        $reflectionClass = new \ReflectionClass(AliasPathStackResolver::class);
        $property = $reflectionClass->getProperty('aliases');
        $property->setAccessible(true);

        Assert::assertEmpty($property->getValue($resolver));
    }
}
