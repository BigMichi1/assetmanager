<?php

namespace AssetManager\Asset;

use Assetic\Asset\StringAsset as BaseStringAsset;

class StringAsset extends BaseStringAsset implements AssetWithMimeTypeInterface
{
    use AssetWithMimeTypeTrait;

    public static function of(string $content, string $mimeType): StringAsset
    {
        $asset = new self($content);
        $asset->setMimeType($mimeType);
        return $asset;
    }
}
