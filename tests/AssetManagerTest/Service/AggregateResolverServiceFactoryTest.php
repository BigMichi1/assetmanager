<?php

namespace AssetManagerTest\Service;

use AssetManager\Asset\StringAsset;
use AssetManager\Exception\RuntimeException;
use AssetManager\Resolver\AggregateResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AggregateResolverServiceFactory;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\MimeResolver;
use InterfaceTestResolver;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use stdClass;

class AggregateResolverServiceFactoryTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        require_once __DIR__ . '/../../_files/InterfaceTestResolver.php';
    }

    public function testWillInstantiateEmptyResolver()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', array());
        $serviceManager->setService(MimeResolver::class, new MimeResolver);

        $factory = new AggregateResolverServiceFactory();
        $resolver = $factory($serviceManager, AggregateResolver::class);
        Assert::assertInstanceOf(ResolverInterface::class, $resolver);
        Assert::assertNull($resolver->resolve('/some-path'));
    }

    public function testWillAttachResolver()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolvers' => array(
                        'mocked_resolver' => 1234,
                    ),
                ),
            )
        );

        $mockedResolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $mockedResolver
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('test-path')
            ->willReturn(new StringAsset('test-resolved-path'));
        $serviceManager->setService('mocked_resolver', $mockedResolver);
        $serviceManager->setService(MimeResolver::class, new MimeResolver);

        $serviceFactory = new AggregateResolverServiceFactory();
        $resolver = $serviceFactory($serviceManager, AggregateResolver::class);

        $asset = $resolver->resolve('test-path');
        $asset->load();

        Assert::assertSame('test-resolved-path', $asset->getContent());
    }

    public function testInvalidCustomResolverFails()
    {
        $this->expectException(RuntimeException::class);
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolvers' => array(
                        'My\Resolver' => 1234,
                    ),
                ),
            )
        );
        $serviceManager->setService(
            'My\Resolver',
            new stdClass
        );

        $factory = new AggregateResolverServiceFactory();
        $factory($serviceManager, AggregateResolver::class);
    }

    public function testWillPrioritizeResolversCorrectly()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolvers' => array(
                        'mocked_resolver_1' => 1000,
                        'mocked_resolver_2' => 500,
                    ),
                ),
            )
        );

        $mockedResolver1 = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $mockedResolver1
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('test-path')
            ->willReturn(new StringAsset('test-resolved-path'));
        $serviceManager->setService('AssetManager\Service\MimeResolver', new MimeResolver);
        $serviceManager->setService('mocked_resolver_1', $mockedResolver1);

        $mockedResolver2 = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $mockedResolver2
            ->expects(TestCase::never())
            ->method('resolve');
        $serviceManager->setService('mocked_resolver_2', $mockedResolver2);

        $factory = new AggregateResolverServiceFactory();
        $resolver = $factory($serviceManager, AggregateResolver::class);

        $asset = $resolver->resolve('test-path');
        $asset->load();

        Assert::assertSame('test-resolved-path', $asset->getContent());
    }

    public function testWillFallbackToLowerPriorityRoutes()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolvers' => array(
                        'mocked_resolver_1' => 1000,
                        'mocked_resolver_2' => 500,
                    ),
                ),
            )
        );

        $mockedResolver1 = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $mockedResolver1
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('test-path')
            ->will(TestCase::returnValue(null));
        $serviceManager->setService('mocked_resolver_1', $mockedResolver1);
        $serviceManager->setService('AssetManager\Service\MimeResolver', new MimeResolver);

        $mockedResolver2 = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $mockedResolver2
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('test-path')
            ->willReturn(new StringAsset('test-resolved-path'));
        $serviceManager->setService('mocked_resolver_2', $mockedResolver2);

        $factory = new AggregateResolverServiceFactory();
        $resolver = $factory($serviceManager, AggregateResolver::class);

        $asset = $resolver->resolve('test-path');
        $asset->load();

        Assert::assertSame('test-resolved-path', $asset->getContent());
    }

    public function testWillSetForInterfaces()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'config',
            array(
                'asset_manager' => array(
                    'resolvers' => array(
                        'mocked_resolver' => 1000,
                    ),
                ),
            )
        );

        $interfaceTestResolver = new InterfaceTestResolver;

        $serviceManager->setService(MimeResolver::class, new MimeResolver);
        $serviceManager->setService('mocked_resolver', $interfaceTestResolver);
        $serviceManager->setService(AssetFilterManager::class, new AssetFilterManager);

        $factory = new AggregateResolverServiceFactory();

        $factory($serviceManager, AggregateResolver::class);

        Assert::assertTrue($interfaceTestResolver->calledMime);
        Assert::assertTrue($interfaceTestResolver->calledAggregate);
        Assert::assertTrue($interfaceTestResolver->calledFilterManager);
    }
}
