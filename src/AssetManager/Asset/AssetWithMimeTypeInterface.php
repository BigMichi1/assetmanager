<?php

namespace AssetManager\Asset;

use Assetic\Contracts\Asset\AssetInterface;

interface AssetWithMimeTypeInterface extends AssetInterface
{
    /**
     * @return string|null
     */
    public function getMimeType(): ?string;

    /**
     * @param string|null $mimeType
     */
    public function setMimeType(?string $mimeType): void;
}
