<?php
declare(strict_types=1);

namespace AssetManagerTest\Resolver;

use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Resolver\MimeResolverAwareInterface;
use AssetManager\Resolver\PrioritizedPathsResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\MimeResolver;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PrioritizedPathsResolverTest extends TestCase
{
    public function testConstructor()
    {
        $resolver = new PrioritizedPathsResolver();
        Assert::assertEmpty($resolver->getPaths()->toArray());

        $resolver->addPaths(array(__DIR__));
        Assert::assertEquals(array(__DIR__ . DIRECTORY_SEPARATOR), $resolver->getPaths()->toArray());
        Assert::assertInstanceOf(MimeResolverAwareInterface::class, $resolver);
        Assert::assertInstanceOf(ResolverInterface::class, $resolver);
    }

    public function testClearPaths()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->addPath('someDir');

        $paths = $resolver->getPaths();


        Assert::assertEquals('someDir' . DIRECTORY_SEPARATOR, $paths->top());

        $resolver->clearPaths();
        Assert::assertEquals(array(), $resolver->getPaths()->toArray());
    }

    public function testSetPaths()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setPaths(array(
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
        ));

        Assert::assertTrue($resolver->getPaths()->hasPriority(1000));
        Assert::assertTrue($resolver->getPaths()->hasPriority(500));

        $fetched = array();

        foreach ($resolver->getPaths() as $path) {
            $fetched[] = $path;
        }

        // order inverted because of how a stack is traversed
        Assert::assertSame(
            array('dir2' . DIRECTORY_SEPARATOR, 'dir3' . DIRECTORY_SEPARATOR, 'dir1' . DIRECTORY_SEPARATOR),
            $fetched
        );
    }

    public function testAddPaths()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setPaths(array(
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
        ));

        $resolver->addPaths(array(
            'dir4',
            array(
                'path' => 'dir5',
                'priority' => -5,
            )
        ));

        $fetched = array();

        foreach ($resolver->getPaths() as $path) {
            $fetched[] = $path;
        }

        // order inverted because of how a stack is traversed
        Assert::assertSame(
            array(
                'dir2' . DIRECTORY_SEPARATOR,
                'dir3' . DIRECTORY_SEPARATOR,
                'dir1' . DIRECTORY_SEPARATOR,
                'dir4' . DIRECTORY_SEPARATOR,
                'dir5' . DIRECTORY_SEPARATOR,
            ),
            $fetched
        );
    }

    public function testAddPath()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setPaths(array(
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
        ));

        $resolver->addPath('dir4');
        $resolver->addPath(array('path' => 'dir5', 'priority' => -5));

        $fetched = array();

        foreach ($resolver->getPaths() as $path) {
            $fetched[] = $path;
        }

        // order inverted because of how a stack is traversed
        Assert::assertSame(
            array(
                'dir2' . DIRECTORY_SEPARATOR,
                'dir3' . DIRECTORY_SEPARATOR,
                'dir1' . DIRECTORY_SEPARATOR,
                'dir4' . DIRECTORY_SEPARATOR,
                'dir5' . DIRECTORY_SEPARATOR,
            ),
            $fetched
        );
    }

    public function testSetPathsAllowsStringPaths()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setPaths(array('dir1', 'dir2', 'dir3'));

        $paths = $resolver->getPaths()->toArray();
        Assert::assertCount(3, $paths);
        Assert::assertContains('dir1' . DIRECTORY_SEPARATOR, $paths);
        Assert::assertContains('dir2' . DIRECTORY_SEPARATOR, $paths);
        Assert::assertContains('dir3' . DIRECTORY_SEPARATOR, $paths);
    }

    public function testWillValidateGivenPathArray()
    {
        $resolver = new PrioritizedPathsResolver();
        $this->expectException(InvalidArgumentException::class);
        $resolver->addPath(array('invalid'));
    }

    public function testResolve()
    {
        $resolver = new PrioritizedPathsResolver;
        $resolver->setMimeResolver(new MimeResolver);
        $resolver->addPath(__DIR__);

        Assert::assertEquals(file_get_contents(__FILE__), $resolver->resolve(basename(__FILE__))->dump());
        Assert::assertNull($resolver->resolve('i-do-not-exist.php'));
    }

    public function testWillNotResolveDirectories()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->addPath(__DIR__ . '/..');

        Assert::assertNull($resolver->resolve(basename(__DIR__)));
    }

    public function testLfiProtection()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->setMimeResolver(new MimeResolver);
        // should be on by default
        Assert::assertTrue($resolver->isLfiProtectionOn());
        $resolver->addPath(__DIR__);

        Assert::assertNull($resolver->resolve(
            '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
        ));

        $resolver->setLfiProtection(false);

        Assert::assertSame(
            file_get_contents(__FILE__),
            $resolver->resolve(
                '..' . DIRECTORY_SEPARATOR . basename(__DIR__) . DIRECTORY_SEPARATOR . basename(__FILE__)
            )->dump()
        );
    }

    public function testWillRefuseInvalidPath()
    {
        $resolver = new PrioritizedPathsResolver();
        $this->expectException(InvalidArgumentException::class);
        $resolver->addPath(null);
    }

    /**
     * Test Collect returns valid list of assets
     */
    public function testCollect()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->addPath(__DIR__);

        Assert::assertContains(basename(__FILE__), $resolver->collect());
        Assert::assertNotContains('i-do-not-exist.php', $resolver->collect());
    }

    /**
     * Test Collect returns valid list of assets
     */
    public function testCollectDirectory()
    {
        $resolver = new PrioritizedPathsResolver();
        $resolver->addPath(realpath(__DIR__ . '/../'));
        $dir = substr(__DIR__, strrpos(__DIR__, '/', 0) + 1);

        Assert::assertContains($dir . DIRECTORY_SEPARATOR . basename(__FILE__), $resolver->collect());
        Assert::assertNotContains($dir . DIRECTORY_SEPARATOR . 'i-do-not-exist.php', $resolver->collect());
    }
}
