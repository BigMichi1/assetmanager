<?php

namespace AssetManager\Asset;

use Assetic\Asset\AssetCache as BaseAssetCache;

class AssetCache extends BaseAssetCache implements AssetWithMimeTypeInterface
{
    use AssetWithMimeTypeTrait;
}
