<?php

use AssetManager\Asset\AssetWithMimeTypeInterface;
use AssetManager\Resolver;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetFilterManagerAwareInterface;
use AssetManager\Service\MimeResolver;

class InterfaceTestResolver implements
    Resolver\ResolverInterface,
    Resolver\AggregateResolverAwareInterface,
    Resolver\MimeResolverAwareInterface,
    AssetFilterManagerAwareInterface
{
    public $calledFilterManager;
    public $calledMime;
    public $calledAggregate;

    public function resolve(string $path): ?AssetWithMimeTypeInterface
    {
        return null;
    }

    public function collect(): array
    {
        return [];
    }

    public function getAggregateResolver()
    {

    }

    public function setAggregateResolver(ResolverInterface $resolver)
    {
        $this->calledAggregate = true;
    }

    public function setMimeResolver(MimeResolver $resolver)
    {
        $this->calledMime = true;
    }

    public function getMimeResolver()
    {
        return $this->calledMime;
    }

    public function getAssetFilterManager()
    {

    }

    public function setAssetFilterManager(AssetFilterManager $filterManager)
    {
        $this->calledFilterManager = true;
    }
}
