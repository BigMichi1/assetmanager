<?php
declare(strict_types=1);

namespace AssetManagerTest\Resolver;

use ArrayObject;
use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Asset\AssetCache;
use AssetManager\Asset\AssetCollection;
use AssetManager\Asset\StringAsset;
use AssetManager\Exception\RuntimeException;
use AssetManager\Resolver\AggregateResolverAwareInterface;
use AssetManager\Resolver\CollectionResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\MimeResolver;
use AssetManagerTest\Service\CollectionsIterable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

class CollectionResolverTest extends TestCase
{
    public function testConstructor()
    {
        $resolver = new CollectionResolver();

        // Check if valid instance
        Assert::assertInstanceOf(ResolverInterface::class, $resolver);
        Assert::assertInstanceOf(AggregateResolverAwareInterface::class, $resolver);

        // Check if set to empty (null argument)
        Assert::assertSame(array(), $resolver->getCollections());

        $resolver = new CollectionResolver(array(
            'key1' => array('value1'),
            'key2' => array('value2'),
        ));
        Assert::assertSame(
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

        Assert::assertSame(
            $collArr,
            $resolver->getCollections()
        );

        // overwrite
        $collArr = array(
            'key3' => array('value3'),
            'key4' => array('value4'),
        );

        $resolver->setCollections($collArr);

        Assert::assertSame(
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

        Assert::assertEquals($collArr, $resolver->getCollections());
    }

    public function testSetGetAggregateResolver()
    {
        $resolver = new CollectionResolver;

        /** @var ResolverInterface&MockObject $aggregateResolver */
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('say')
            ->willReturn(new StringAsset('world'));

        $resolver->setAggregateResolver($aggregateResolver);

        $asset = $resolver->getAggregateResolver()->resolve('say');
        $asset->load();

        Assert::assertEquals('world', $asset->getContent());
    }

    /**
     * Resolve
     */
    public function testResolveNoArgsEqualsNull()
    {
        $resolver = new CollectionResolver;

        Assert::assertNull($resolver->resolve('bacon'));
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

        /** @var ResolverInterface&MockObject $aggregateResolver */
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('bacon')
            ->will(TestCase::returnValue(null));

        $resolver = new CollectionResolver(array(
            'myCollection' => array('bacon')
        ));

        $resolver->setAggregateResolver($aggregateResolver);

        $resolver->resolve('myCollection');
    }

    public function testResolvesToNonAsset()
    {
        $this->expectException(TypeError::class);

        /** @var ResolverInterface&MockObject $aggregateResolver */
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('bacon')
            ->will(TestCase::returnValue('invalid'));

        $resolver = new CollectionResolver(array(
            'myCollection' => array('bacon')
        ));

        $resolver->setAggregateResolver($aggregateResolver);

        $resolver->resolve('myCollection');
    }

    public function testMimeTypesDoNotMatch()
    {
        $this->expectException(RuntimeException::class);

        /** @var ResolverInterface&MockObject $aggregateResolver */
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects(TestCase::exactly(2))
            ->method('resolve')
            ->willReturn(
                StringAsset::of('bacon', 'text/plain'),
                StringAsset::of('eggs', 'text/css'),
                StringAsset::of('Mud', 'text/javascript')
            );

        /** @var AssetFilterManager&MockObject $assetFilterManager */
        $assetFilterManager = $this
            ->getMockBuilder(AssetFilterManager::class)
            ->getMock();
        $assetFilterManager
            ->expects(TestCase::once())
            ->method('setFilters')
            ->will(TestCase::returnValue(null));

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
        /** @var ResolverInterface&MockObject $aggregateResolver */
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();

        //assets with same 'last modified time'.
        $now = time();
        $bacon = StringAsset::of('bacon', 'text/plain');
        $bacon->setLastModified($now);

        $eggs = StringAsset::of('eggs', 'text/plain');
        $eggs->setLastModified($now);

        $assets = array(
            array('bacon', $bacon),
            array('eggs', $eggs),
        );

        $aggregateResolver
            ->expects(TestCase::any())
            ->method('resolve')
            ->will(TestCase::returnValueMap($assets));

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

        /** @var CacheInterface&MockObject $cacheInterface */
        $cacheInterface = $this
            ->getMockBuilder(CacheInterface::class)
            ->getMock();

        $cacheKeys = new ArrayObject();
        $callback = function ($key) use ($cacheKeys): bool {
            $cacheKeys[] = $key;
            return true;
        };

        $cacheInterface
            ->expects(TestCase::exactly(2))
            ->method('has')
            ->will(TestCase::returnCallback($callback));

        $cacheInterface
            ->expects(TestCase::exactly(2))
            ->method('get')
            ->will(TestCase::returnValue('cached content'));

        $cache1 = new AssetCache($collection1, $cacheInterface);
        $cache1->load();

        $cache2 = new AssetCache($collection2, $cacheInterface);
        $cache2->load();

        Assert::assertCount(2, $cacheKeys);
        Assert::assertNotEquals($cacheKeys[0], $cacheKeys[1]);
    }

    public function testSuccessResolve()
    {
        /** @var ResolverInterface&MockObject $aggregateResolver */
        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects(TestCase::exactly(3))
            ->method('resolve')
            ->willReturn(
                StringAsset::of('bacon', 'text/plain'),
                StringAsset::of('eggs', 'text/plain'),
                StringAsset::of('Mud', 'text/plain')
            );

        $resolver = new CollectionResolver(array(
            'myCollection' => array(
                'bacon',
                'eggs',
                'mud',
            )
        ));


        $mimeResolver = new MimeResolver();
        $assetFilterManager = new AssetFilterManager();

        $assetFilterManager->setMimeResolver($mimeResolver);

        $resolver->setAggregateResolver($aggregateResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $collectionResolved = $resolver->resolve('myCollection');

        Assert::assertEquals('text/plain', $collectionResolved->getMimeType());
        Assert::assertInstanceOf(AssetCollection::class, $collectionResolved);
    }

    /**
     * Test Collect returns valid list of assets
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

        Assert::assertEquals(array_keys($collections), $resolver->collect());
    }
}
