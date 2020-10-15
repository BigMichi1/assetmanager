<?php

namespace AssetManager\Resolver;

use AssetManager\Asset\AssetWithMimeTypeInterface;

interface ResolverInterface
{
    /**
     * Resolve an Asset
     *
     * @param string $path The path to resolve.
     *
     * @return  AssetWithMimeTypeInterface|null Asset instance when found, null when not.
     */
    public function resolve(string $path);

    /**
     * Collect all assets - used for warm up and for asset extraction
     * @return string[]
     */
    public function collect();
}
