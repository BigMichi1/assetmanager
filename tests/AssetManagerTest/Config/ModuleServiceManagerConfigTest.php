<?php

namespace AssetManagerTest\Config;

use AssetManager\Service\MimeResolver;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

/**
 * Test to ensure config file is properly setup and all services are retrievable
 *
 * @package AssetManagerTest\Config
 */
class ModuleServiceManagerConfigTest extends TestCase
{
    /**
     * Test the Service Managers Factories.
     */
    public function testServiceManagerFactories()
    {
        $config = include __DIR__.'/../../../config/module.config.php';

        $serviceManagerConfig = new Config($config['service_manager']);
        $serviceManager = new ServiceManager();
        $serviceManagerConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('config', $config);

        foreach ($config['service_manager']['factories'] as $serviceName => $service) {
            Assert::assertTrue($serviceManager->has($serviceName));

            //Make sure we can fetch the service
            $service = $serviceManager->get($serviceName);

            Assert::assertTrue(is_object($service));
        }
    }

    /**
     * Test the Service Managers Invokables.
     */
    public function testServiceManagerInvokables()
    {
        $config = include __DIR__.'/../../../config/module.config.php';

        $serviceManagerConfig = new Config($config['service_manager']);
        $serviceManager = new ServiceManager();
        $serviceManagerConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('config', $config);

        foreach ($config['service_manager']['invokables'] as $serviceName => $service) {
            Assert::assertTrue($serviceManager->has($serviceName));

            //Make sure we can fetch the service
            $service = $serviceManager->get($serviceName);

            Assert::assertTrue(is_object($service));
        }
    }

    /**
     * Test the Service Managers Invokables.
     */
    public function testServiceManagerAliases()
    {
        $config = include __DIR__.'/../../../config/module.config.php';

        $serviceManagerConfig = new Config($config['service_manager']);
        $serviceManager = new ServiceManager();
        $serviceManagerConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('config', $config);

        foreach ($config['service_manager']['aliases'] as $serviceName => $service) {
            Assert::assertTrue($serviceManager->has($serviceName));

            //Make sure we can fetch the service
            $service = $serviceManager->get($serviceName);

            Assert::assertTrue(is_object($service));
        }
    }

    /**
     * Test for Issue #134 - Test for specific mime_resolver invokable
     */
    public function mimeResolverInvokableTest()
    {
        $config = include __DIR__.'/../../../config/module.config.php';

        $serviceManagerConfig = new Config($config['service_manager']);
        $serviceManager = new ServiceManager();
        $serviceManagerConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('config', $config);

        Assert::assertTrue($serviceManager->has(MimeResolver::class));
        Assert::assertTrue(is_object($serviceManager->get(MimeResolver::class)));
    }

    /**
     * Test for Issue #134 - Test for specific mime_resolver alias
     */
    public function mimeResolverAliasTest()
    {
        $config = include __DIR__.'/../../../config/module.config.php';

        $serviceManagerConfig = new Config($config['service_manager']);
        $serviceManager = new ServiceManager();
        $serviceManagerConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('config', $config);

        Assert::assertTrue($serviceManager->has('mime_resolver'));
        Assert::assertTrue(is_object($serviceManager->get('mime_resolver')));
    }
}
