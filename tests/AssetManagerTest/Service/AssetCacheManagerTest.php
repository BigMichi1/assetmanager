<?php

namespace AssetManagerTest\Service;

use Assetic\Cache\FilesystemCache;
use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Asset\AssetCache;
use AssetManager\Asset\FileAsset;
use AssetManager\Cache\FilePathCache;
use AssetManager\Service\AssetCacheManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Test file for the Asset Cache Manager
 *
 * @package AssetManagerTest\Service
 */
class AssetCacheManagerTest extends TestCase
{
    public function testSetCache()
    {
        $serviceManager = new ServiceManager();

        $config = array(
            'my/path' => array(
                'cache' => 'Filesystem',
            ),
        );

        /** @var FileAsset $mockAsset */
        $mockAsset = $this->getMockBuilder(FileAsset::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAsset->setMimeType('image/png');

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $assetCache = $assetManager->setCache('my/path', $mockAsset);

        Assert::assertTrue($assetCache instanceof AssetCache);
        Assert::assertEquals($mockAsset->getMimeType(), $assetCache->getMimeType());
    }

    public function testSetCacheNoProviderFound()
    {
        $serviceManager = new ServiceManager();
        $config = array(
            'my/path' => array(
                'cache' => 'Filesystem',
            ),
        );

        /** @var FileAsset $mockAsset */
        $mockAsset = $this->getMockBuilder(FileAsset::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAsset->setMimeType('image/png');

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $assetCache = $assetManager->setCache('not/defined', $mockAsset);

        Assert::assertFalse($assetCache instanceof AssetCache);
    }

    public function testGetProvider()
    {
        $serviceManager = new ServiceManager();

        $config = array(
            'my/path' => array(
                'cache' => 'Filesystem',
            ),
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getProvider'
        );
        $reflectionMethod->setAccessible(true);

        $provider = $reflectionMethod->invoke($assetManager, 'my/path');

        Assert::assertTrue($provider instanceof CacheInterface);
    }

    public function testGetProviderUsingDefaultConfiguration()
    {
        $serviceManager = new ServiceManager();
        $config = array(
            'default' => array(
                'cache' => 'Filesystem',
            ),
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getProvider'
        );
        $reflectionMethod->setAccessible(true);

        $provider = $reflectionMethod->invoke($assetManager, 'no/path');

        Assert::assertTrue($provider instanceof CacheInterface);
    }

    public function testGetProviderWithDefinedService()
    {
        $serviceManager = new ServiceManager();

        $config = array(
            'default' => array(
                'cache' => 'myZf2Service',
            ),
        );

        $serviceManager->setFactory(
            'myZf2Service',
            function (): FilePathCache {
                return new FilePathCache('somewhere', 'somfile');
            }
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getProvider'
        );
        $reflectionMethod->setAccessible(true);

        $provider = $reflectionMethod->invoke($assetManager, 'no/path');

        Assert::assertTrue($provider instanceof FilePathCache);
    }

    public function testGetProviderWithCacheOptions()
    {
        $serviceManager = new ServiceManager();

        $config = array(
            'my_provided_class.tmp' => array(
                'cache' => FilePathCache::class,
                'options' => array(
                    'dir' => 'somewhere',
                )
            ),
        );

        $serviceManager->setFactory(
            'myZf2Service',
            function (): FilePathCache {
                return new FilePathCache('somewhere', 'somfile');
            }
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getProvider'
        );
        $reflectionMethod->setAccessible(true);

        $provider = $reflectionMethod->invoke($assetManager, 'my_provided_class.tmp');
        Assert::assertInstanceOf(FilePathCache::class, $provider);

        $reflectionProperty = new ReflectionProperty(FilePathCache::class, 'dir');
        $reflectionProperty->setAccessible(true);

        Assert::assertTrue($reflectionProperty->getValue($provider) == 'somewhere');
    }

    public function testGetProviderWithMultipleDefinition()
    {
        $serviceManager = new ServiceManager();
        $config = array(
            'default' => array(
                'cache' => 'myZf2Service',
            ),

            'my_callback.tmp' => array(
                'cache' => function (): FilePathCache {
                    return new FilePathCache('somewhere', 'somefile');
                },
            ),

            'my_provided_class.tmp' => array(
                'cache' => FilePathCache::class,
                'options' => array(
                    'dir' => 'somewhere',
                )
            ),
        );

        $serviceManager->setFactory(
            'myZf2Service',
            function (): FilePathCache {
                return new FilePathCache('somewhere', 'somfile');
            }
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);

        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getProvider'
        );
        $reflectionMethod->setAccessible(true);

        $provider = $reflectionMethod->invoke($assetManager, 'no/path');
        Assert::assertTrue($provider instanceof FilePathCache);

        $provider = $reflectionMethod->invoke($assetManager, 'my_callback.tmp');
        Assert::assertTrue($provider instanceof FilePathCache);

        $provider = $reflectionMethod->invoke($assetManager, 'my_provided_class.tmp');
        Assert::assertTrue($provider instanceof FilePathCache);
    }

    public function testGetProviderWithNoCacheConfig()
    {
        $serviceManager = new ServiceManager();

        $assetManager = new AssetCacheManager($serviceManager, array());
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getProvider'
        );
        $reflectionMethod->setAccessible(true);

        $provider = $reflectionMethod->invoke($assetManager, 'no/path');
        Assert::assertNull($provider);
    }

    public function testGetCacheProviderConfig()
    {
        $expected = array(
            'cache' => FilePathCache::class,
            'options' => array(
                'dir' => 'somewhere',
            ),
        );

        $serviceManager = new ServiceManager();
        $config = array(
            'my_provided_class.tmp' => $expected,
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getCacheProviderConfig'
        );
        $reflectionMethod->setAccessible(true);

        $providerConfig = $reflectionMethod->invoke($assetManager, 'my_provided_class.tmp');
        Assert::assertEquals($expected, $providerConfig);
    }

    public function testGetCacheProviderConfigReturnsDefaultCache()
    {
        $expected = array(
            'cache' => FilePathCache::class,
            'options' => array(
                'dir' => 'somewhere',
            ),
        );

        $serviceManager = new ServiceManager();
        $config = array(
            'default' => $expected,
            'some_other_definition' => array(
                'cache' => FilePathCache::class,
            )
        );

        $assetManager = new AssetCacheManager($serviceManager, $config);
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'getCacheProviderConfig'
        );
        $reflectionMethod->setAccessible(true);

        $providerConfig = $reflectionMethod->invoke($assetManager, 'my_provided_class.tmp');
        Assert::assertEquals($expected, $providerConfig);
    }

    public function testClassMapperResolvesFilesystemCache()
    {
        $serviceManager = new ServiceManager();

        $assetManager = new AssetCacheManager($serviceManager, array());
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'classMapper'
        );
        $reflectionMethod->setAccessible(true);

        $class = $reflectionMethod->invoke($assetManager, 'FilesystemCache');
        Assert::assertEquals(FilesystemCache::class, $class);
    }

    public function testClassMapperResolvesFilePathCache()
    {
        $serviceManager = new ServiceManager();

        $assetManager = new AssetCacheManager($serviceManager, array());

        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'classMapper'
        );
        $reflectionMethod->setAccessible(true);

        $class = $reflectionMethod->invoke($assetManager, 'FilePathCache');
        Assert::assertEquals(FilePathCache::class, $class);
    }

    public function testClassMapperResolvesShorthandClassAlias()
    {
        $serviceManager = new ServiceManager();


        $assetManager = new AssetCacheManager($serviceManager, array());
        $reflectionMethod = new ReflectionMethod(
            AssetCacheManager::class,
            'classMapper'
        );
        $reflectionMethod->setAccessible(true);

        $class = $reflectionMethod->invoke($assetManager, 'FilePath');
        Assert::assertEquals(FilePathCache::class, $class);
    }
}
