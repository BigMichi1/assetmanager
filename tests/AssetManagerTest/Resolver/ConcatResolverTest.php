<?php
declare(strict_types=1);

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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
        $resolver = new ConcatResolver();

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

    public function testSetConcatSuccess()
    {
        $resolver = new ConcatResolver(new ConcatIterable());

        Assert::assertEquals(
            array(
                'concatName1' => array(
                    'concat 1.1',
                    'concat 1.2',
                    'concat 1.3',
                    'concat 1.4',
                ),
                'concatName2' => array(
                    'concat 2.1',
                    'concat 2.2',
                    'concat 2.3',
                    'concat 2.4',
                ),
                'concatName3' => array(
                    'concat 3.1',
                    'concat 3.2',
                    'concat 3.3',
                    'concat 3.4',
                )
            ),
            $resolver->getConcats()
        );
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

        /** @var ResolverInterface&MockObject $aggregateResolver */
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
