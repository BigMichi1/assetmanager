<?php

namespace AssetManager;

use AssetManager\Service\AssetManager;
use Laminas\Console\Adapter\AdapterInterface;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventsCapableInterface;
use Laminas\Http\Response;
use Laminas\Loader\AutoloaderFactory;
use Laminas\Loader\StandardAutoloader;
use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;

/**
 * Module class
 *
 * @category   AssetManager
 * @package    AssetManager
 */
class Module implements
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    BootstrapListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            AutoloaderFactory::STANDARD_AUTOLOADER => array(
                StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * Callback method for dispatch and dispatch.error events.
     *
     * @param MvcEvent $event
     * @return ResponseInterface|null
     */
    public function onDispatch(MvcEvent $event): ?ResponseInterface
    {
        $response = $event->getResponse();
        if (!($response instanceof Response)) {
            return null;
        }
        if ($response->getStatusCode() !== 404) {
            return null;
        }
        $request = $event->getRequest();
        $serviceManager = $event->getApplication()->getServiceManager();
        /** @var AssetManager $assetManager */
        $assetManager = $serviceManager->get(__NAMESPACE__ . '\Service\AssetManager');

        if (!$assetManager->resolvesToAsset($request)) {
            return null;
        }

        $response->setStatusCode(200);

        return $assetManager->setAssetOnResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function onBootstrap(EventInterface $event)
    {
        // Attach for dispatch, and dispatch.error (with low priority to make sure statusCode gets set)
        $target = $event->getTarget();
        if ($target instanceof EventsCapableInterface) {
            $eventManager = $target->getEventManager();
            $callback = array($this, 'onDispatch');
            $priority = -9999999;
            $eventManager->attach(MvcEvent::EVENT_DISPATCH, $callback, $priority);
            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, $callback, $priority);
        }
    }

    /**
     * @param AdapterInterface $console
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConsoleUsage(AdapterInterface $console)
    {
        return array(
            'Warmup',
            'assetmanager warmup [--purge] [--verbose|-v]' => 'Warm AssetManager up',
            array('--purge', '(optional) forces cache flushing'),
            array('--verbose | -v', '(optional) verbose mode'),
        );
    }
}
