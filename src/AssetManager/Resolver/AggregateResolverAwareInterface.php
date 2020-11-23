<?php
declare(strict_types=1);

namespace AssetManager\Resolver;

interface AggregateResolverAwareInterface
{
    /**
     * Set the aggregate resolver.
     *
     * @param ResolverInterface $aggregateResolver
     */
    public function setAggregateResolver(ResolverInterface $aggregateResolver);

    /**
     * Get the aggregate resolver.
     *
     * @return ResolverInterface
     */
    public function getAggregateResolver();
}
