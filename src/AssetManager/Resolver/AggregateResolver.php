<?php
declare(strict_types=1);

namespace AssetManager\Resolver;

use AssetManager\Asset\AssetWithMimeTypeInterface;
use Laminas\Stdlib\PriorityQueue;

/**
 * The aggregate resolver consists out of a multitude of
 * resolvers defined by the ResolverInterface.
 */
class AggregateResolver implements ResolverInterface
{
    /**
     * @var PriorityQueue|ResolverInterface[]
     */
    protected $queue;

    /**
     * Constructor
     *
     * Instantiate the internal priority queue
     */
    public function __construct()
    {
        $this->queue = new PriorityQueue();
    }

    /**
     * Attach a resolver
     *
     * @param ResolverInterface $resolver
     * @param int $priority
     */
    public function attach(ResolverInterface $resolver, $priority = 1)
    {
        $this->queue->insert($resolver, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($name): ?AssetWithMimeTypeInterface
    {
        foreach ($this->queue as $resolver) {
            $resource = $resolver->resolve($name);
            if (null !== $resource) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function collect(): array
    {
        $collection = array();

        foreach ($this->queue as $resolver) {
            $collection = array_merge($resolver->collect(), $collection);
        }

        return array_unique($collection);
    }
}
