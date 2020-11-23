<?php
declare(strict_types=1);

namespace AssetManagerTest\Resolver;

use AssetManager\Asset\FileAsset;
use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Resolver\AliasPathStackResolver;
use AssetManager\Service\MimeResolver;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Unit Tests for the Alias Path Stack Resolver
 */
class AliasPathStackResolverTest extends TestCase
{
    /**
     * Test constructor passes
     *
     * @throws ReflectionException
     */
    public function testConstructor()
    {
        $aliases = array(
            'alias1' => __DIR__ . DIRECTORY_SEPARATOR,
        );

        $resolver = new AliasPathStackResolver($aliases);

        $reflectionClass = new ReflectionClass(AliasPathStackResolver::class);
        $property = $reflectionClass->getProperty('aliases');
        $property->setAccessible(true);

        Assert::assertEquals(
            $aliases,
            $property->getValue($resolver)
        );
    }

    /**
     * Test add alias method.
     *
     * @throws ReflectionException
     */
    public function testAddAlias()
    {
        $resolver = new AliasPathStackResolver(array());
        $reflectionClass = new ReflectionClass(AliasPathStackResolver::class);
        $addAlias = $reflectionClass->getMethod('addAlias');

        $addAlias->setAccessible(true);

        $property = $reflectionClass->getProperty('aliases');

        $property->setAccessible(true);

        $addAlias->invoke($resolver, 'alias', 'path');

        Assert::assertEquals(
            array('alias' => 'path' . DIRECTORY_SEPARATOR),
            $property->getValue($resolver)
        );
    }

    /**
     * Test addAlias fails with bad key
     *
     * @throws ReflectionException
     */
    public function testAddAliasFailsWithBadKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $resolver = new AliasPathStackResolver(array());
        $reflectionClass = new ReflectionClass(AliasPathStackResolver::class);
        $addAlias = $reflectionClass->getMethod('addAlias');

        $addAlias->setAccessible(true);

        $property = $reflectionClass->getProperty('aliases');
        $property->setAccessible(true);

        $addAlias->invoke($resolver, null, 'path');
    }

    /**
     * Test addAlias fails with bad Path
     *
     * @throws ReflectionException
     */
    public function testAddAliasFailsWithBadPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $resolver = new AliasPathStackResolver(array());

        $reflectionClass = new ReflectionClass(AliasPathStackResolver::class);

        $addAlias = $reflectionClass->getMethod('addAlias');
        $addAlias->setAccessible(true);

        $property = $reflectionClass->getProperty('aliases');
        $property->setAccessible(true);

        $addAlias->invoke($resolver, 'alias', null);
    }

    /**
     * Test normalize path
     *
     * @throws ReflectionException
     */
    public function testNormalizePath()
    {
        $resolver = new AliasPathStackResolver(array());
        $reflectionClass = new ReflectionClass(AliasPathStackResolver::class);
        $addAlias = $reflectionClass->getMethod('normalizePath');

        $addAlias->setAccessible(true);

        $result = $addAlias->invoke($resolver, 'somePath\/\/\/');

        Assert::assertEquals(
            'somePath' . DIRECTORY_SEPARATOR,
            $result
        );
    }

    /**
     * Test Set Mime Resolver Only Accepts a mime Resolver
     */
    public function testGetAndSetMimeResolver()
    {
        /** @var MimeResolver&MockObject $mimeResolver */
        $mimeResolver = $this->getMockBuilder(MimeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolver = new AliasPathStackResolver(array('my/alias/' => __DIR__));

        $resolver->setMimeResolver($mimeResolver);

        $returned = $resolver->getMimeResolver();

        Assert::assertEquals($mimeResolver, $returned);
    }

    /**
     * Test Lfi Protection Flag Defaults to true
     */
    public function testLfiProtectionFlagDefaultsTrue()
    {
        $resolver = new AliasPathStackResolver(array('my/alias/' => __DIR__));
        $returned = $resolver->isLfiProtectionOn();

        Assert::assertTrue($returned);
    }

    /**
     * Test Get and Set of Lfi Protection Flag
     */
    public function testGetAndSetOfLfiProtectionFlag()
    {
        $resolver = new AliasPathStackResolver(array('my/alias/' => __DIR__));
        $resolver->setLfiProtection(true);
        $returned = $resolver->isLfiProtectionOn();

        Assert::assertTrue($returned);

        $resolver->setLfiProtection(false);
        $returned = $resolver->isLfiProtectionOn();

        Assert::assertFalse($returned);
    }

    /**
     * Test Resolve returns valid asset
     */
    public function testResolve()
    {
        $resolver = new AliasPathStackResolver(array('my/alias/' => __DIR__));
        Assert::assertInstanceOf(AliasPathStackResolver::class, $resolver);
        $mimeResolver = new MimeResolver();
        $resolver->setMimeResolver($mimeResolver);
        $fileAsset = new FileAsset(__FILE__);
        $fileAsset->setMimeType($mimeResolver->getMimeType(__FILE__));
        Assert::assertEquals($fileAsset, $resolver->resolve('my/alias/' . basename(__FILE__)));
        Assert::assertNull($resolver->resolve('i-do-not-exist.php'));
    }

    /**
     * Test Resolve returns valid asset
     */
    public function testResolveWhenAliasStringDoesnotContainTrailingSlash()
    {
        $resolver = new AliasPathStackResolver(array('my/alias' => __DIR__));
        $mimeResolver = new MimeResolver();
        $resolver->setMimeResolver($mimeResolver);
        $fileAsset = new FileAsset(__FILE__);
        $fileAsset->setMimeType($mimeResolver->getMimeType(__FILE__));
        Assert::assertEquals($fileAsset, $resolver->resolve('my/alias/' . basename(__FILE__)));
    }

    public function testResolveWhenAliasExistsInPath()
    {
        $resolver = new AliasPathStackResolver(array('AliasPathStackResolverTest/' => __DIR__));
        $mimeResolver = new MimeResolver();
        $resolver->setMimeResolver($mimeResolver);
        $fileAsset = new FileAsset(__FILE__);
        $fileAsset->setMimeType($mimeResolver->getMimeType(__FILE__));
        Assert::assertEquals($fileAsset, $resolver->resolve('AliasPathStackResolverTest/' . basename(__FILE__)));

        $map = array(
            'AliasPathStackResolverTest/' => __DIR__,
            'prefix/AliasPathStackResolverTest/' => __DIR__
        );
        $resolver = new AliasPathStackResolver($map);
        $resolver->setMimeResolver(new MimeResolver());
        $fileAsset = new FileAsset(__FILE__);
        $fileAsset->setMimeType($mimeResolver->getMimeType(__FILE__));
        Assert::assertEquals($fileAsset, $resolver->resolve('prefix/AliasPathStackResolverTest/' . basename(__FILE__)));
    }

    /**
     * Test that resolver will not resolve directories
     */
    public function testWillNotResolveDirectories()
    {
        $resolver = new AliasPathStackResolver(array('my/alias/' => __DIR__ . '/..'));
        Assert::assertNull($resolver->resolve('my/alias/' . basename(__DIR__)));
    }

    /**
     * Test Lfi Protection
     */
    public function testLfiProtection()
    {
        $mimeResolver = new MimeResolver();
        $resolver = new AliasPathStackResolver(array('my/alias/' => __DIR__));
        $resolver->setMimeResolver($mimeResolver);

        // should be on by default
        Assert::assertTrue($resolver->isLfiProtectionOn());

        Assert::assertNull($resolver->resolve(
            '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
        ));

        $resolver->setLfiProtection(false);

        Assert::assertEquals(
            file_get_contents(__FILE__),
            $resolver->resolve(
                'my/alias/..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
            )->dump()
        );
    }

    /**
     * Test Collect returns valid list of assets
     */
    public function testCollect()
    {
        $alias = 'my/alias/';
        $resolver = new AliasPathStackResolver(array($alias => __DIR__));

        Assert::assertContains($alias . basename(__FILE__), $resolver->collect());
        Assert::assertNotContains($alias . 'i-do-not-exist.php', $resolver->collect());
    }

    /**
     * Test Collect returns valid list of assets
     */
    public function testCollectDirectory()
    {
        $alias = 'my/alias/';
        $resolver = new AliasPathStackResolver(array($alias => realpath(__DIR__ . '/../')));
        $dir = substr(__DIR__, strrpos(__DIR__, '/', 0) + 1);

        Assert::assertContains($alias . $dir . DIRECTORY_SEPARATOR . basename(__FILE__), $resolver->collect());
        Assert::assertNotContains($alias . $dir . DIRECTORY_SEPARATOR . 'i-do-not-exist.php', $resolver->collect());
    }
}
