<?php

namespace AssetManager\Asset;

use Assetic\Asset\StringAsset as BaseStringAsset;

class StringAsset extends BaseStringAsset implements AssetWithMimeTypeInterface
{
    use AssetWithMimeTypeTrait;
}
