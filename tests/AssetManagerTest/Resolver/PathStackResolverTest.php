<?php

namespace AssetManagerTest\Resolver;

use ArrayObject;
use AssetManager\Asset\FileAsset;
use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Resolver\MimeResolverAwareInterface;
use AssetManager\Resolver\PathStackResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\MimeResolver;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

class PathStackResolverTest extends TestCase
{
    public function testConstructor()
    {
        $resolver = new PathStackResolver();
        Assert::assertEmpty($resolver->getPaths()->toArray());

        $resolver->addPaths(array(__DIR__));
        Assert::assertEquals(array(__DIR__ . DIRECTORY_SEPARATOR), $resolver->getPaths()->toArray());

        $resolver->clearPaths();
        Assert::assertEquals(array(), $resolver->getPaths()->toArray());

        Assert::assertTrue($resolver instanceof MimeResolverAwareInterface);
        Assert::assertTrue($resolver instanceof ResolverInterface);
        $mimeResolver = new MimeResolver;

        $resolver->setMimeResolver($mimeResolver);

        Assert::assertEquals($mimeResolver, $resolver->getMimeResolver());
    }

    public function testSetMimeResolverFailObject()
    {
        $this->expectException(TypeError::class);

        $resolver = new PathStackResolver();
        $resolver->setMimeResolver(new stdClass());
    }

    public function testSetMimeResolverFailString()
    {
        $this->expectException(TypeError::class);

        $resolver = new PathStackResolver();
        $resolver->setMimeResolver('invalid');
    }

    public function testSetPaths()
    {
        $resolver = new PathStackResolver();
        $resolver->setPaths(array('dir2', 'dir1'));
        // order inverted because of how a stack is traversed
        Assert::assertSame(
            array('dir1' . DIRECTORY_SEPARATOR, 'dir2' . DIRECTORY_SEPARATOR),
            $resolver->getPaths()->toArray()
        );

        $paths = new ArrayObject(array(
            'dir4',
            'dir3',
        ));
        $resolver->setPaths($paths);
        Assert::assertSame(
            array('dir3' . DIRECTORY_SEPARATOR, 'dir4' . DIRECTORY_SEPARATOR),
            $resolver->getPaths()->toArray()
        );

        $this->expectException(InvalidArgumentException::class);
        $resolver->setPaths('invalid');
    }

    public function testResolve()
    {
        $resolver = new PathStackResolver();
        Assert::assertTrue($resolver instanceof PathStackResolver);

        $mimeResolver = new MimeResolver;
        $resolver->setMimeResolver($mimeResolver);

        $resolver->addPath(__DIR__);

        $fileAsset = new FileAsset(__FILE__);
        $fileAsset->setMimeType($mimeResolver->getMimeType(__FILE__));

        Assert::assertEquals($fileAsset, $resolver->resolve(basename(__FILE__)));
        Assert::assertNull($resolver->resolve('i-do-not-exist.php'));
    }

    public function testWillNotResolveDirectories()
    {
        $resolver = new PathStackResolver();
        $resolver->addPath(__DIR__ . '/..');

        Assert::assertNull($resolver->resolve(basename(__DIR__)));
    }

    public function testLfiProtection()
    {
        $mimeResolver = new MimeResolver;
        $resolver = new PathStackResolver;
        $resolver->setMimeResolver($mimeResolver);

        // should be on by default
        Assert::assertTrue($resolver->isLfiProtectionOn());
        $resolver->addPath(__DIR__);

        Assert::assertNull($resolver->resolve(
            '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
        ));

        $resolver->setLfiProtection(false);

        Assert::assertEquals(
            file_get_contents(__FILE__),
            $resolver->resolve(
                '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
            )->dump()
        );
    }

    public function testWillRefuseInvalidPath()
    {
        $resolver = new PathStackResolver();
        $this->expectException(InvalidArgumentException::class);
        $resolver->addPath(null);
    }

    /**
     * Test Collect returns valid list of assets
     *
     * @covers \AssetManager\Resolver\PathStackResolver::collect
     */
    public function testCollect()
    {
        $resolver = new PathStackResolver();
        $resolver->addPath(__DIR__);

        Assert::assertContains(basename(__FILE__), $resolver->collect());
        Assert::assertNotContains('i-do-not-exist.php', $resolver->collect());
    }

    /**
     * Test Collect returns valid list of assets
     *
     * @covers \AssetManager\Resolver\PathStackResolver::collect
     */
    public function testCollectDirectory()
    {
        $resolver = new PathStackResolver();
        $resolver->addPath(realpath(__DIR__ . '/../'));
        $dir = substr(__DIR__, strrpos(__DIR__, '/', 0) + 1);

        Assert::assertContains($dir . DIRECTORY_SEPARATOR . basename(__FILE__), $resolver->collect());
        Assert::assertNotContains($dir . DIRECTORY_SEPARATOR . 'i-do-not-exist.php', $resolver->collect());
    }
}
