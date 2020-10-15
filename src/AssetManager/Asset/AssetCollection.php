<?php

namespace AssetManager\Asset;

use Assetic\Asset\AssetCollection as BaseAssetCollection;

class AssetCollection extends BaseAssetCollection implements AssetWithMimeTypeInterface
{
    use  AssetWithMimeTypeTrait;
}
