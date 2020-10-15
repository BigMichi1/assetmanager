<?php

namespace AssetManager\Asset;

use Assetic\Asset\FileAsset as BaseFileAsset;

class FileAsset extends BaseFileAsset implements AssetWithMimeTypeInterface
{
    use AssetWithMimeTypeTrait;
}
