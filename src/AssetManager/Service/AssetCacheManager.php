<?php

namespace AssetManager\Service;

use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Asset\AssetCache;
use AssetManager\Asset\AssetWithMimeTypeInterface;
use Interop\Container\ContainerInterface;

/**
 * Asset Cache Manager.  Sets asset cache based on configuration.
 */
class AssetCacheManager
{
    /**
     * @var ContainerInterface
     */
    protected $serviceLocator;

    /**
     * @var array Cache configuration.
     */
    protected $config = array();

    /**
     * Construct the AssetCacheManager
     *
     * @param ContainerInterface $serviceLocator
     * @param array $config
     */
    public function __construct(ContainerInterface $serviceLocator, array $config)
    {
        $this->serviceLocator = $serviceLocator;
        $this->config = $config;
    }

    /**
     * Set the cache (if any) on the asset, and return the new AssetCache.
     *
     * @param string $path Path to asset
     * @param AssetWithMimeTypeInterface $asset Assetic Asset Interface
     *
     * @return  AssetWithMimeTypeInterface
     */
    public function setCache(string $path, AssetWithMimeTypeInterface $asset)
    {
        $provider = $this->getProvider($path);

        if (!$provider instanceof CacheInterface) {
            return $asset;
        }

        $assetCache = new AssetCache($asset, $provider);
        $assetCache->setMimeType($asset->getMimeType());

        return $assetCache;
    }

    /**
     * Get the cache provider.  First checks to see if the provider is callable,
     * then will attempt to get it from the service locator, finally will fallback
     * to a class mapper.
     *
     * @param string $path
     *
     * @return CacheInterface|null
     */
    private function getProvider(string $path): ?CacheInterface
    {
        $cacheProvider = $this->getCacheProviderConfig($path);

        if ($cacheProvider === null || count($cacheProvider) === 0) {
            return null;
        }

        if (is_string($cacheProvider['cache']) &&
            $this->serviceLocator->has($cacheProvider['cache'])
        ) {
            return $this->serviceLocator->get($cacheProvider['cache']);
        }

        // Left here for BC.  Please consider defining a ZF2 service instead.
        if (is_callable($cacheProvider['cache'])) {
            return call_user_func($cacheProvider['cache'], $path);
        }

        $dir = '';
        $class = $cacheProvider['cache'];

        if (isset($cacheProvider['options']['dir'])
            && is_string($cacheProvider['options']['dir'])
            && strlen($cacheProvider['options']['dir']) > 0) {
            $dir = $cacheProvider['options']['dir'];
        }

        $class = $this->classMapper($class);
        return new $class($dir, $path);
    }

    /**
     * Get the cache provider config.  Use default values if defined.
     *
     * @param string $path
     *
     * @return null|array Cache config definition.  Returns null if not found in
     *                    config.
     */
    private function getCacheProviderConfig(string $path)
    {
        $cacheProvider = null;

        if (isset($this->config[$path]['cache'])
            && is_string($this->config[$path]['cache'])
            && strlen($this->config[$path]['cache']) > 0
        ) {
            $cacheProvider = $this->config[$path];
        }

        if ($cacheProvider === null
            && isset($this->config['default']['cache'])
            && is_string($this->config['default']['cache'])
            && strlen($this->config['default']['cache']) > 0
        ) {
            $cacheProvider = $this->config['default'];
        }

        return $cacheProvider;
    }

    /**
     * Class mapper to provide backwards compatibility
     *
     * @param string $class
     *
     * @return string
     */
    private function classMapper(string $class)
    {
        $classToCheck = $class;
        $classToCheck .= (substr($class, -5) === 'Cache') ? '' : 'Cache';

        switch ($classToCheck) {
            case 'FilesystemCache':
                $class = 'Assetic\Cache\FilesystemCache';
                break;
            case 'FilePathCache':
                $class = 'AssetManager\Cache\FilePathCache';
                break;
        }

        return $class;
    }
}
