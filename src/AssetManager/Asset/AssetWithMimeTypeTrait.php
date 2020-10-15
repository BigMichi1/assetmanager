<?php

namespace AssetManager\Asset;

trait AssetWithMimeTypeTrait
{
    /** @var string|null */
    private $mimeType;

    /**
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     */
    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
