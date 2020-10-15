<?php

namespace AssetManagerTest\Resolver;

use ArrayObject;
use Assetic\Cache\CacheInterface;
use AssetManager\Asset\AssetCache;
use AssetManager\Asset\AssetCollection;
use AssetManager\Asset\FileAsset;
use AssetManager\Asset\StringAsset;
use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Exception\RuntimeException;
use AssetManager\Resolver\AggregateResolverAwareInterface;
use AssetManager\Resolver\CollectionResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\MimeResolver;
use AssetManagerTest\Service\CollectionsIterable;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

class CollectionResolverTest extends TestCase
{
    public function getResolverMock()
    {
        $resolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with('bacon')
            ->will($this->returnValue(new FileAsset(__FILE__)));

        return $resolver;
    }

    public function testConstructor()
    {
        $resolver = new CollectionResolver();

        // Check if valid instance
        $this->assertTrue($resolver instanceof ResolverInterface);
        $this->assertTrue($resolver instanceof AggregateResolverAwareInterface);

        // Check if set to empty (null argument)
        $this->assertSame(array(), $resolver->getCollections());

        $resolver = new CollectionResolver(array(
            'key1' => array('value1'),
            'key2' => array('value2'),
        ));
        $this->assertSame(
            array(
                'key1' => array('value1'),
                'key2' => array('value2'),
            ),
            $resolver->getCollections()
        );
    }

    public function testSetCollections()
    {
        $resolver = new CollectionResolver();
        $collArr = array(
            'key1' => array('value1'),
            'key2' => array('value2'),
        );

        $resolver->setCollections($collArr);

        $this->assertSame(
            $collArr,
            $resolver->getCollections()
        );

        // overwrite
        $collArr = array(
            'key3' => array('value3'),
            'key4' => array('value4'),
        );

        $resolver->setCollections($collArr);

        $this->assertSame(
            $collArr,
            $resolver->getCollections()
        );


        // Overwrite with traversable
        $resolver->setCollections(new CollectionsIterable());

        $collArr = array(
            'collectionName1' => array(
                'collection 1.1',
                'collection 1.2',
                'collection 1.3',
                'collection 1.4',
            ),
            'collectionName2' => array(
                'collection 2.1',
                'collection 2.2',
                'collection 2.3',
                'collection 2.4',
            ),
            'collectionName3' => array(
                'collection 3.1',
                'collection 3.2',
                'collection 3.3',
                'collection 3.4',
            )
        );

        $this->assertEquals($collArr, $resolver->getCollections());
    }

    public function testSetCollectionFailsObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $resolver = new CollectionResolver;

        $resolver->setCollections(new stdClass());
    }

    public function testSetCollectionFailsString()
    {
        $this->expectException(InvalidArgumentException::class);
        $resolver = new CollectionResolver;

        $resolver->setCollections('invalid');
    }

    public function testSetGetAggregateResolver()
    {
        $resolver = new CollectionResolver;

        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('say')
            ->will($this->returnValue('world'));

        $resolver->setAggregateResolver($aggregateResolver);

        $this->assertEquals('world', $resolver->getAggregateResolver()->resolve('say'));
    }

    public function testSetAggregateResolverFails()
    {
        $this->expectException(TypeError::class);

        $resolver = new CollectionResolver;

        $resolver->setAggregateResolver(new stdClass());
    }

    /**
     * Resolve
     */
    public function testResolveNoArgsEqualsNull()
    {
        $resolver = new CollectionResolver;

        $this->assertNull($resolver->resolve('bacon'));
    }

    public function testResolveNonArrayCollectionException()
    {
        $this->expectException(RuntimeException::class);
        $resolver = new CollectionResolver(array('bacon' => 'bueno'));

        $resolver->resolve('bacon');
    }

    public function testCollectionItemNonString()
    {
        $this->expectException(RuntimeException::class);
        $resolver = new CollectionResolver(array(
            'bacon' => array(new stdClass())
        ));

        $resolver->resolve('bacon');
    }

    public function testCouldNotResolve()
    {
        $this->expectException(RuntimeException::class);
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('bacon')
            ->will($this->returnValue(null));

        $resolver = new CollectionResolver(array(
            'myCollection' => array('bacon')
        ));

        $resolver->setAggregateResolver($aggregateResolver);

        $resolver->resolve('myCollection');
    }

    public function testResolvesToNonAsset()
    {
        $this->expectException(RuntimeException::class);
        $aggregateResolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $aggregateResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('bacon')
            ->will($this->returnValue('invalid'));

        $resolver = new CollectionResolver(array(
            'myCollection' => array('bacon')
        ));

        $resolver->setAggregateResolver($aggregateResolver);

        $resolver->resolve('myCollection');
    }

    public function testMimeTypesDontMatch()
    {
        $this->expectException(RuntimeException::class);
        $callbackInvocationCount = 0;
        $callback = function () use (&$callbackInvocationCount) {

            $asset1 = new StringAsset('bacon');
            $asset2 = new StringAsset('eggs');
            $asset3 = new StringAsset('Mud');

            $asset1->setMimeType('text/plain');
            $asset2->setMimeType('text/css');
            $asset3->setMimeType('text/javascript');

            $callbackInvocationCount += 1;
            $assetName = "asset$callbackInvocationCount";
            return $$assetName;
        };

        $aggregateResolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $aggregateResolver
            ->expects($this->exactly(2))
            ->method('resolve')
            ->will($this->returnCallback($callback));

        $assetFilterManager = $this->getMockBuilder(AssetFilterManager::class)->getMock();
        $assetFilterManager
            ->expects($this->once())
            ->method('setFilters')
            ->will($this->returnValue(null));

        $resolver = new CollectionResolver(array(
            'myCollection' => array(
                'bacon',
                'eggs',
                'mud',
            )
        ));

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $resolver->resolve('myCollection');
    }

    public function testTwoCollectionsHasDifferentCacheKey()
    {
        $aggregateResolver = $this->getMockBuilder(ResolverInterface::class)->getMock();

        //assets with same 'last modified time'.
        $now = time();
        $bacon = new StringAsset('bacon');
        $bacon->setLastModified($now);
        $bacon->setMimeType('text/plain');

        $eggs = new StringAsset('eggs');
        $eggs->setLastModified($now);
        $eggs->setMimeType('text/plain');

        $assets = array(
            array('bacon', $bacon),
            array('eggs', $eggs),
        );

        $aggregateResolver
            ->expects($this->any())
            ->method('resolve')
            ->will($this->returnValueMap($assets));

        $resolver = new CollectionResolver(array(
            'collection1' => array(
                'bacon',
            ),
            'collection2' => array(
                'eggs',
            ),
        ));

        $mimeResolver = new MimeResolver;
        $assetFilterManager = new AssetFilterManager();
        $assetFilterManager->setMimeResolver($mimeResolver);

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $collection1 = $resolver->resolve('collection1');
        $collection2 = $resolver->resolve('collection2');

        $cacheInterface = $this->getMockBuilder(CacheInterface::class)->getMock();

        $cacheKeys = new ArrayObject();
        $callback = function ($key) use ($cacheKeys) {
            $cacheKeys[] = $key;
            return true;
        };

        $cacheInterface
            ->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnCallback($callback));

        $cacheInterface
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue('cached content'));

        $cache1 = new AssetCache($collection1, $cacheInterface);
        $cache1->load();

        $cache2 = new AssetCache($collection2, $cacheInterface);
        $cache2->load();

        $this->assertCount(2, $cacheKeys);
        $this->assertNotEquals($cacheKeys[0], $cacheKeys[1]);
    }

    public function testSuccessResolve()
    {
        $callbackInvocationCount = 0;
        $callback = function () use (&$callbackInvocationCount) {

            $asset1 = new StringAsset('bacon');
            $asset2 = new StringAsset('eggs');
            $asset3 = new StringAsset('Mud');

            $asset1->setMimeType('text/plain');
            $asset2->setMimeType('text/plain');
            $asset3->setMimeType('text/plain');

            $callbackInvocationCount += 1;
            $assetName = "asset$callbackInvocationCount";
            return $$assetName;
        };

        $aggregateResolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $aggregateResolver
            ->expects($this->exactly(3))
            ->method('resolve')
            ->will($this->returnCallback($callback));

        $resolver = new CollectionResolver(array(
            'myCollection' => array(
                'bacon',
                'eggs',
                'mud',
            )
        ));


        $mimeResolver = new MimeResolver;
        $assetFilterManager = new AssetFilterManager();

        $assetFilterManager->setMimeResolver($mimeResolver);

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $collectionResolved = $resolver->resolve('myCollection');

        $this->assertEquals($collectionResolved->getMimeType(), 'text/plain');
        $this->assertTrue($collectionResolved instanceof AssetCollection);
    }

    /**
     * Test Collect returns valid list of assets
     *
     * @covers \AssetManager\Resolver\CollectionResolver::collect
     */
    public function testCollect()
    {
        $collections = array(
            'myCollection' => array(
                'bacon',
                'eggs',
                'mud',
            ),
            'my/collect.ion' => array(
                'bacon',
                'eggs',
                'mud',
            ),
        );
        $resolver = new CollectionResolver($collections);

        $this->assertEquals(array_keys($collections), $resolver->collect());
    }
}
