<?php

namespace AssetManager\Asset;

use Assetic\Asset\BaseAsset;
use Assetic\Contracts\Filter\FilterInterface;
use AssetManager\Exception;

/**
 * Represents a concatenated string asset.
 */
class AggregateAsset extends BaseAsset implements AssetWithMimeTypeInterface
{
    /**
     * @var int Timestamp of last modified date from asset
     */
    private $lastModified;

    /** @var string|null */
    public $mimetype;

    /**
     * Constructor.
     *
     * @param array $content The array of assets to be merged
     * @param array $filters Filters for the asset
     * @param string|null $sourceRoot The source asset root directory
     * @param string|null $sourcePath The source asset path
     */
    public function __construct(
        array $content = [],
        array $filters = [],
        ?string $sourceRoot = null,
        ?string $sourcePath = null
    ) {
        parent::__construct($filters, $sourceRoot, $sourcePath);
        $this->processContent($content);
    }

    /**
     * load asset
     *
     * @param ?FilterInterface $additionalFilter
     */
    public function load(FilterInterface $additionalFilter = null): void
    {
        $this->doLoad($this->getContent(), $additionalFilter);
    }

    /**
     * set last modified value of asset
     *
     * this is useful for cache mechanism detection id file has changed
     *
     * @param int $lastModified
     */
    public function setLastModified(int $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    /**
     * get last modified value from asset
     *
     * @return int|null
     */
    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    /**
     * Loop through assets and merge content
     *
     * @param array $content
     *
     * @throws Exception\RuntimeException
     */
    private function processContent(array $content): void
    {
        $this->mimetype = null;
        foreach ($content as $asset) {
            if (null === $this->mimetype) {
                $this->mimetype = $asset->getMimeType();
            }

            if ($asset->getMimeType() !== $this->mimetype) {
                throw new Exception\RuntimeException(
                    sprintf(
                        'Asset "%s" doesn\'t have the expected mime-type "%s".',
                        $asset->getTargetPath(),
                        $this->mimetype
                    )
                );
            }

            $this->setLastModified(
                max(
                    $asset->getLastModified(),
                    $this->getLastModified()
                )
            );
            $this->setContent(
                $this->getContent() . $asset->dump()
            );
        }
    }

    /**
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mimetype;
    }

    /**
     * @param string|null $mimetype
     */
    public function setMimeType(?string $mimetype): void
    {
        $this->mimetype = $mimetype;
    }
}
