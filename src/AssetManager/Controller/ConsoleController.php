<?php

namespace AssetManager\Controller;

use AssetManager\Service\AssetManager;
use Laminas\Console\Adapter\AdapterInterface as Console;
use Laminas\Console\Request;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use RuntimeException;

/**
 * Class ConsoleController
 *
 * @package AssetManager\Controller
 */
class ConsoleController extends AbstractActionController
{

    /**
     * @var Console console object
     */
    protected $console;

    /**
     * @var AssetManager asset manager object
     */
    protected $assetManager;

    /**
     * @var array associative array represents app config
     */
    protected $appConfig;

    /**
     * @param Console $console
     * @param AssetManager $assetManager
     * @param array $appConfig
     */
    public function __construct(Console $console, AssetManager $assetManager, array $appConfig)
    {
        $this->console = $console;
        $this->assetManager = $assetManager;
        $this->appConfig = $appConfig;
    }

    /**
     * {@inheritdoc}
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return mixed|ResponseInterface
     * @throws RuntimeException
     */
    public function dispatch(RequestInterface $request, ResponseInterface $response = null)
    {
        if (!($request instanceof ConsoleRequest)) {
            throw new RuntimeException('You can use this controller only from a console!');
        }

        return parent::dispatch($request, $response);
    }

    /**
     * Dumps all assets to cache directories.
     */
    public function warmupAction(): void
    {
        $request = $this->getRequest();
        if (!($request instanceof Request)) {
            throw new RuntimeException('You can use this controller only from a console!');
        }
        $purge = (bool)$request->getParam('purge', false);
        $verbose = (bool)$request->getParam('verbose', false) || (bool)$request->getParam('v', false);

        // purge cache for every configuration
        if ($purge) {
            $this->purgeCache($verbose);
        }

        $this->output('Collecting all assets...', $verbose);

        $collection = $this->assetManager->getResolver()->collect();
        $this->output(sprintf('Collected %d assets, warming up...', count($collection)), $verbose);

        foreach ($collection as $path) {
            $asset = $this->assetManager->getResolver()->resolve($path);
            if ($asset !== null) {
                $this->assetManager->getAssetFilterManager()->setFilters($path, $asset);
                $this->assetManager->getAssetCacheManager()->setCache($path, $asset)->dump();
            }
        }

        $this->output('Warming up finished...', $verbose);
    }

    /**
     * Purges all directories defined as AssetManager cache dir.
     * @param bool $verbose verbose flag, default false
     * @return bool false if caching is not set, otherwise true
     */
    private function purgeCache($verbose = false): bool
    {

        if (!isset($this->appConfig['asset_manager']['caching'])
            || !is_array($this->appConfig['asset_manager']['caching'])
            || count($this->appConfig['asset_manager']['caching']) === 0
        ) {
            return false;
        }

        foreach ($this->appConfig['asset_manager']['caching'] as $configName => $config) {
            if (!isset($config['options']['dir'])
                || !is_string($config['options']['dir'])
                || strlen($config['options']['dir']) === 0
            ) {
                continue;
            }
            $this->output(sprintf('Purging %s on "%s"...', $config['options']['dir'], $configName), $verbose);

            $node = $config['options']['dir'];

            if ($configName !== 'default') {
                $node .= '/' . $configName;
            }

            $this->recursiveRemove($node, $verbose);
        }

        return true;
    }

    /**
     * Removes given node from filesystem (recursively).
     * @param string $node - uri of node that should be removed from filesystem
     * @param bool $verbose verbose flag, default false
     */
    private function recursiveRemove(string $node, $verbose = false): void
    {
        if (is_dir($node)) {
            $objects = scandir($node);

            foreach ($objects as $object) {
                if ($object === '.' || $object === '..') {
                    continue;
                }
                $this->recursiveRemove($node . '/' . $object);
            }
        } elseif (is_file($node)) {
            $this->output(sprintf("unlinking %s...", $node), $verbose);
            unlink($node);
        }
    }

    /**
     * Outputs given $line if $verbose i truthy value.
     * @param string $line
     * @param bool $verbose verbose flag, default true
     */
    private function output(string $line, $verbose = true): void
    {
        if ($verbose) {
            $this->console->writeLine($line);
        }
    }
}
