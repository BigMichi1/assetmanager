<?php

namespace AssetManagerTest\Resolver;

use AssetManager\Asset\FileAsset;
use AssetManager\Asset\HttpAsset;
use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Resolver\MapResolver;
use AssetManager\Resolver\MimeResolverAwareInterface;
use AssetManager\Service\MimeResolver;
use AssetManagerTest\Service\MapIterable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use stdClass;

class MapResolverTest extends TestCase
{
    public function testConstruct()
    {
        $resolver = new MapResolver(
            array(
                'key1' => 'value1',
                'key2' => 'value2'
            )
        );

        Assert::assertSame(
            array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
            $resolver->getMap()
        );
    }

    public function testGetMimeResolver()
    {
        $resolver = new MapResolver;
        Assert::assertNull($resolver->getMimeResolver());
    }

    public function testSetMapSuccess()
    {
        $resolver = new MapResolver(new MapIterable());

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
            $resolver->getMap()
        );
    }

    public function testSetMapFails()
    {
        $this->expectException(InvalidArgumentException::class);

        $resolver = new MapResolver;
        $resolver->setMap(new stdClass());
    }

    public function testGetMap()
    {
        $resolver = new MapResolver;
        Assert::assertSame(array(), $resolver->getMap());
    }

    public function testResolveNull()
    {
        $resolver = new MapResolver;
        Assert::assertNull($resolver->resolve('bacon'));
    }

    public function testResolveAssetFail()
    {
        $resolver = new MapResolver;

        $asset1 = array(
            'bacon' => 'porn',
        );

        Assert::assertNull($resolver->setMap($asset1));
    }

    public function testResolveAssetSuccess()
    {
        $resolver = new MapResolver();

        Assert::assertInstanceOf(MimeResolverAwareInterface::class, $resolver);

        $mimeResolver = new MimeResolver;

        $resolver->setMimeResolver($mimeResolver);

        $asset1 = array(
            'bacon' => __FILE__,
        );

        $resolver->setMap($asset1);

        $asset = $resolver->resolve('bacon');
        $mimetype = $mimeResolver->getMimeType(__FILE__);

        Assert::assertTrue($asset instanceof FileAsset);
        Assert::assertEquals($mimetype, $asset->getMimeType());
        Assert::assertEquals($asset->dump(), file_get_contents(__FILE__));
    }

    public function testResolveHttpAssetSuccess()
    {
        $resolver = new MapResolver;
        $mimeResolver = $this
            ->getMockBuilder(MimeResolver::class)
            ->getMock();

        $mimeResolver->expects(TestCase::any())
            ->method('getMimeType')
            ->with('http://foo.bar/')
            ->will(TestCase::returnValue('text/foo'));

        $resolver->setMimeResolver($mimeResolver);

        $asset1 = array(
            'bacon' => 'http://foo.bar/',
        );

        $resolver->setMap($asset1);

        $asset = $resolver->resolve('bacon');

        Assert::assertTrue($asset instanceof HttpAsset);
        Assert::assertSame('text/foo', $asset->getMimeType());
    }

    /**
     * Test Collect returns valid list of assets
     */
    public function testCollect()
    {
        $map = array(
            'foo' => 'bar',
            'baz' => 'qux',
        );
        $resolver = new MapResolver($map);

        Assert::assertEquals(array_keys($map), $resolver->collect());
    }
}
