<?php

namespace AssetManagerTest\Controller;

use AssetManager\Controller\ConsoleController;
use AssetManager\Resolver\MapResolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetCacheManager;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetManager;
use AssetManager\Service\MimeResolver;
use JSMin;
use Laminas\Console\Adapter\AdapterInterface;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

class ConsoleControllerTest extends TestCase
{
    /**
     *
     * @var ConsoleController
     */
    protected $controller;
    protected $request;
    protected $routeMatch;
    protected $event;
    protected static $assetName;

    public static function setUpBeforeClass(): void
    {
        self::$assetName = '_assettest.' . time();
    }

    public function setUp(): void
    {
        require_once __DIR__ . '/../../_files/JSMin.inc';

        $config = array(
            'filters' => array(
                self::$assetName => array(
                    array(
                        'filter' => 'JSMin',
                    ),
                ),
            ),
        );

        $assetFilterManager = new AssetFilterManager($config['filters']);
        $assetCacheManager = $this->getAssetCacheManager();

        $resolver = $this->getResolver();
        $assetManager = new AssetManager($resolver, $config);
        $assetManager->setAssetFilterManager($assetFilterManager);
        $assetManager->setAssetCacheManager($assetCacheManager);

        $this->request = new ConsoleRequest();
        $this->routeMatch = $this->createRouteMatch(['controller' => 'console']);

        $this->event = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);

        $this->controller = new ConsoleController(
            $this->getMockBuilder(AdapterInterface::class)->getMock(),
            $assetManager,
            array()
        );
        $this->controller->setEvent($this->event);
    }

    public function createRouteMatch(array $params = [])
    {
        return new RouteMatch($params);
    }

    /**
     *
     * @return ResolverInterface
     */
    protected function getResolver()
    {
        $mimeResolver = new MimeResolver();
        $resolver = new MapResolver(array(
            self::$assetName => __DIR__ . '/../../_files/require-jquery.js'
        ));
        $resolver->setMimeResolver($mimeResolver);
        return $resolver;
    }

    /**
     * @return AssetCacheManager
     */
    protected function getAssetCacheManager()
    {
        $serviceLocator = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
        $config = array(
            self::$assetName => array(
                'cache' => 'FilePathCache',
                'options' => array(
                    'dir' => sys_get_temp_dir()
                )
            ),
        );
        return new AssetCacheManager($serviceLocator, $config);
    }

    public function testWarmupAction()
    {
        $this->routeMatch->setParam('action', 'warmup');
        $this->controller->dispatch($this->request);

        $dumpedAsset = sys_get_temp_dir() . '/' . self::$assetName;
        $this->assertEquals(
            file_get_contents($dumpedAsset),
            JSMin::minify(file_get_contents(__DIR__ . '/../../_files/require-jquery.js'))
        );
    }
}
