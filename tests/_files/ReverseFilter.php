<?php

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\FilterInterface;

class ReverseFilter implements FilterInterface
{
    private static $executed;

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        self::$executed++;
        $content = $asset->getContent();
        $asset->setContent(self::$executed . strrev($content));
    }
}
