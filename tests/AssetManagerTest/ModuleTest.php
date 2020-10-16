<?php

namespace AssetManagerTest;

use AssetManager\Module;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetManager;
use Laminas\Console\Response as ConsoleResponse;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AssetManager\Module
 */
class ModuleTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    public function testGetAutoloaderConfig()
    {
        $module = new Module();
        // just testing ZF specification requirements
        Assert::assertIsArray($module->getAutoloaderConfig());
    }

    public function testGetConfig()
    {
        $module = new Module();
        // just testing ZF specification requirements
        Assert::assertIsArray($module->getConfig());
    }

    /**
     * Verifies that dispatch listener does nothing on other repsponse codes
     */
    public function testDispatchListenerIgnoresOtherResponseCodes()
    {
        $event = new MvcEvent();
        $response = new Response();
        $module = new Module();

        $response->setStatusCode(500);
        $event->setResponse($response);

        $response = $module->onDispatch($event);

        Assert::assertNull($response);
    }

    public function testOnDispatchDoesntResolveToAsset()
    {
        $resolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $assetManager = $this->getMockBuilder(AssetManager::class)
            ->onlyMethods(array('resolvesToAsset'))
            ->setConstructorArgs(array($resolver))
            ->getMock();
        $assetManager
            ->expects(TestCase::once())
            ->method('resolvesToAsset')
            ->will(TestCase::returnValue(false));

        $serviceManager = $this
            ->getMockBuilder(ServiceLocatorInterface::class)
            ->getMock();
        $serviceManager
            ->expects(TestCase::any())
            ->method('get')
            ->will(TestCase::returnValue($assetManager));

        $application = $this
            ->getMockBuilder(ApplicationInterface::class)
            ->getMock();
        $application
            ->expects(TestCase::once())
            ->method('getServiceManager')
            ->will(TestCase::returnValue($serviceManager));

        $event = new MvcEvent();
        $response = new Response();
        $request = new Request();
        $module = new Module();

        $event->setApplication($application);
        $response->setStatusCode(404);
        $event->setResponse($response);
        $event->setRequest($request);

        $return = $module->onDispatch($event);

        Assert::assertNull($return);
    }

    public function testOnDispatchStatus200()
    {
        $resolver = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $assetManager = $this
            ->getMockBuilder(AssetManager::class)
            ->onlyMethods(array('resolvesToAsset', 'setAssetOnResponse'))
            ->setConstructorArgs(array($resolver))
            ->getMock();
        $assetManager
            ->expects(TestCase::once())
            ->method('resolvesToAsset')
            ->will(TestCase::returnValue(true));


        $amResponse = new Response();
        $amResponse->setContent('bacon');

        $assetManager
            ->expects(TestCase::once())
            ->method('setAssetOnResponse')
            ->will(TestCase::returnValue($amResponse));

        $serviceManager = $this
            ->getMockBuilder(ServiceLocatorInterface::class)
            ->getMock();
        $serviceManager
            ->expects(TestCase::any())
            ->method('get')
            ->will(TestCase::returnValue($assetManager));

        $application = $this
            ->getMockBuilder(ApplicationInterface::class)
            ->getMock();
        $application
            ->expects(TestCase::once())
            ->method('getServiceManager')
            ->will(TestCase::returnValue($serviceManager));

        $event = new MvcEvent();
        $response = new Response();
        $request = new Request();
        $module = new Module();

        $event->setApplication($application);
        $response->setStatusCode(404);
        $event->setResponse($response);
        $event->setRequest($request);

        /** @var Response $return */
        $return = $module->onDispatch($event);

        Assert::assertInstanceOf(Response::class, $return);
        Assert::assertEquals(200, $return->getStatusCode());
    }

    /**
     * @covers \AssetManager\Module::onDispatch
     */
    public function testWillIgnoreInvalidResponseType()
    {
        $cliResponse = $this->getMockBuilder(ConsoleResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mvcEvent = $this
            ->getMockBuilder(MvcEvent::class)
            ->getMock();
        $module = new Module();

        $cliResponse->expects(TestCase::never())->method('setErrorLevel');
        $mvcEvent->expects(TestCase::once())->method('getResponse')->will(TestCase::returnValue($cliResponse));

        Assert::assertNull($module->onDispatch($mvcEvent));
    }

    public function testOnBootstrap()
    {
        $applicationEventManager = new EventManager();

        $application = $this
            ->getMockBuilder(ApplicationInterface::class)
            ->getMock();
        $application
            ->expects(TestCase::any())
            ->method('getEventManager')
            ->will(TestCase::returnValue($applicationEventManager));

        $event = new Event();
        $event->setTarget($application);

        $module = new Module();
        $module->onBootstrap($event);

        $this->assertListenerAtPriority(
            [$module, 'onDispatch'],
            -9999999,
            MvcEvent::EVENT_DISPATCH,
            $applicationEventManager
        );

        $this->assertListenerAtPriority(
            [$module, 'onDispatch'],
            -9999999,
            MvcEvent::EVENT_DISPATCH_ERROR,
            $applicationEventManager
        );
    }
}
