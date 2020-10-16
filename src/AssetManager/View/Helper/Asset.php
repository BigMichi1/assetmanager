<?php

namespace AssetManager\View\Helper;

use AssetManager\Resolver\ResolverInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter as AbstractCacheAdapter;
use Laminas\View\Helper\AbstractHelper;

class Asset extends AbstractHelper
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var ResolverInterface
     */
    private $assetManagerResolver;

    /**
     * @var AbstractCacheAdapter|null
     */
    private $cache;

    /**
     * @param ResolverInterface $assetManagerResolver
     * @param AbstractCacheAdapter|null $cache
     * @param array $config
     */
    public function __construct(ResolverInterface $assetManagerResolver, ?AbstractCacheAdapter $cache, array $config)
    {
        $this->assetManagerResolver = $assetManagerResolver;
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Append timestamp as query param to the filename
     *
     * @param string $filename
     * @param string $queryString
     * @param int|null $timestamp
     *
     * @return string
     */
    private function appendTimestamp(string $filename, string $queryString, ?int $timestamp = null): string
    {
        // current timestamp as default
        $timestamp = $timestamp === null ? time() : $timestamp;

        return $filename . '?' . urlencode($queryString) . '=' . $timestamp;
    }

    /**
     * find the file and if it exists, append its unix modification time to the filename
     *
     * @param string $filename
     * @param string $queryString
     * @return string
     */
    private function elaborateFilePath(string $filename, string $queryString): string
    {
        $asset = $this->assetManagerResolver->resolve($filename);
        if ($asset !== null) {
            // append last modified date to the filepath and use a custom query string
            return $this->appendTimestamp($filename, $queryString, $asset->getLastModified());
        }

        return $filename;
    }

    /**
     * Use the cache to get the filePath
     *
     * @param string $filename
     * @param string $queryString
     *
     * @return string|null
     */
    private function getFilePathFromCache(string $filename, string $queryString): ?string
    {
        // return if cache not found
        if ($this->cache == null) {
            return null;
        }

        // cache key based on the filename
        $cacheKey = md5($filename);
        $itemIsFoundInCache = false;
        $filePath = $this->cache->getItem($cacheKey, $itemIsFoundInCache);

        // if there is no element in the cache, elaborate and cache it
        if ($itemIsFoundInCache === false || $filePath === null) {
            $filePath = $this->elaborateFilePath($filename, $queryString);
            $this->cache->setItem($cacheKey, $filePath);
        }

        return $filePath;
    }

    /**
     * Output the filepath with its unix modification time as query param
     *
     * @param string $filename
     * @return string
     */
    public function __invoke(string $filename): string
    {
        // nothing to append
        if (!isset($this->config['view_helper']['append_timestamp'])
            || !$this->config['view_helper']['append_timestamp']
        ) {
            return $filename;
        }

        // search the cache config for the specific file requested (if none, use the default one)
        if (isset($this->config['caching'][$filename])) {
            $cacheConfig = $this->config['caching'][$filename];
        } elseif (isset($this->config['caching']['default'])) {
            $cacheConfig = $this->config['caching']['default'];
        }

        // query string params
        $queryString = isset($this->config['view_helper']['query_string'])
            ? $this->config['view_helper']['query_string']
            : '_';

        // no cache dir is defined
        if (!isset($cacheConfig['options']['dir'])) {
            // append current timestamp to the filepath and use a custom query string
            return $this->appendTimestamp($filename, $queryString);
        }

        // get the filePath from the cache (if available)
        $filePath = $this->getFilePathFromCache($filename, $queryString);
        if ($filePath !== null) {
            return $filePath;
        }

        return $this->elaborateFilePath($filename, $queryString);
    }
}
