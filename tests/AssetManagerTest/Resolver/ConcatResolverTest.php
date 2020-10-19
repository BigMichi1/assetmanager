<?php

namespace AssetManagerTest\Resolver;

use AssetManager\Asset\AggregateAsset;
use AssetManager\Asset\FileAsset;
use AssetManager\Asset\StringAsset;
use AssetManager\Resolver\AggregateResolverAwareInterface;
use AssetManager\Resolver\ConcatResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\MimeResolver;
use AssetManagerTest\Service\ConcatIterable;
use Laminas\Stdlib\Exception\InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

class ConcatResolverTest extends TestCase
{
    public function testConstruct()
    {
        $resolver = new ConcatResolver(
            array(
                'key1' => array(
                    __FILE__
                ),
                'key2' => array(
                    __FILE__
                ),
            )
        );

        Assert::assertInstanceOf(ResolverInterface::class, $resolver);
        Assert::assertInstanceOf(AggregateResolverAwareInterface::class, $resolver);

        Assert::assertSame(
            array(
                'key1' => array(
                    __FILE__
                ),
                'key2' => array(
                    __FILE__
                ),
            ),
            $resolver->getConcats()
        );
    }

    public function testSetGetAggregateResolver()
    {
        $resolver = new ConcatResolver;

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

    public function testSetAggregateResolverFails()
    {
        $this->expectException(TypeError::class);

        $resolver = new ConcatResolver;

        $resolver->setAggregateResolver(new stdClass());
    }

    public function testSetConcatSuccess()
    {
        $resolver = new ConcatResolver;

        $resolver->setConcats(new ConcatIterable());

        Assert::assertEquals(
            array(
                'mapName1' => array(
                    'map 1.1',
                    'map 1.2',
                    'map 1.3',
                    'map 1.4',
                ),
                'mapName2' => array(
                    'map 2.1',
                    'map 2.2',
                    'map 2.3',
                    'map 2.4',
                ),
                'mapName3' => array(
                    'map 3.1',
                    'map 3.2',
                    'map 3.3',
                    'map 3.4',
                )
            ),
            $resolver->getConcats()
        );
    }

    public function testSetConcatFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $resolver = new ConcatResolver;
        $resolver->setConcats(new stdClass());
    }

    public function testGetConcat()
    {
        $resolver = new ConcatResolver;
        Assert::assertSame(array(), $resolver->getConcats());
    }

    public function testResolveNull()
    {
        $resolver = new ConcatResolver;
        Assert::assertNull($resolver->resolve('bacon'));
    }

    public function testResolveAssetFail()
    {
        $resolver = new ConcatResolver;

        $asset1 = array(
            'bacon' => 'yummy',
        );

        Assert::assertNull($resolver->setConcats($asset1));
    }

    public function testResolveAssetSuccess()
    {
        $resolver = new ConcatResolver;

        $asset1 = array(
            'bacon' => array(
                __FILE__,
                __FILE__,
            ),
        );

        $callback = function ($file): FileAsset {
            return new FileAsset($file);
        };

        $aggregateResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $aggregateResolver
            ->expects(TestCase::exactly(2))
            ->method('resolve')
            ->will(TestCase::returnCallback($callback));
        $resolver->setAggregateResolver($aggregateResolver);

        $assetFilterManager = new AssetFilterManager();
        $mimeResolver = new MimeResolver;
        $assetFilterManager->setMimeResolver($mimeResolver);
        $resolver->setMimeResolver($mimeResolver);
        $resolver->setAssetFilterManager($assetFilterManager);

        $resolver->setConcats($asset1);

        $asset = $resolver->resolve('bacon');

        Assert::assertTrue($asset instanceof AggregateAsset);
        Assert::assertEquals(
            $asset->dump(),
            file_get_contents(__FILE__) . file_get_contents(__FILE__)
        );
    }

    /**
     * Test Collect returns valid list of assets
     */
    public function testCollect()
    {
        $concats = array(
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
        $resolver = new ConcatResolver($concats);

        Assert::assertEquals(array_keys($concats), $resolver->collect());
    }
}
