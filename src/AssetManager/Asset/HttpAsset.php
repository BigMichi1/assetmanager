<?php

namespace AssetManager\Asset;

use Assetic\Asset\HttpAsset as BaseHttpAsset;

class HttpAsset extends BaseHttpAsset implements AssetWithMimeTypeInterface
{
    use AssetWithMimeTypeTrait;
}
