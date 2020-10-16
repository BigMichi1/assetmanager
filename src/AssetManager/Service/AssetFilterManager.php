<?php

namespace AssetManager\Service;

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\FilterInterface;
use AssetManager\Asset\AssetWithMimeTypeInterface;
use AssetManager\Exception\RuntimeException;
use AssetManager\Resolver\MimeResolverAwareInterface;
use Interop\Container\ContainerInterface;

class AssetFilterManager implements MimeResolverAwareInterface
{
    /**
     * @var array Filter configuration.
     */
    protected $config;

    /**
     * @var ContainerInterface
     */
    protected $serviceLocator;

    /**
     * @var MimeResolver
     */
    protected $mimeResolver;

    /**
     * @var FilterInterface[] Filters already instantiated
     */
    protected $filterInstances = array();

    /**
     * Construct the AssetFilterManager
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->setConfig($config);
    }

    /**
     * Get the filter configuration.
     *
     * @return  array
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the filter configuration.
     *
     * @param array $config
     */
    protected function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * See if there are filters for the asset, and if so, set them.
     *
     * @param string $path
     * @param AssetWithMimeTypeInterface $asset
     *
     * @throws RuntimeException on invalid filters
     */
    public function setFilters(string $path, AssetWithMimeTypeInterface $asset)
    {
        $config = $this->getConfig();

        if (isset($config[$path])
            && is_array($config[$path])
            && count($config[$path]) > 0
        ) {
            $filters = $config[$path];
        } elseif (isset($config[$asset->getMimeType()])
            && is_array($config[$asset->getMimeType()])
            && count($config[$asset->getMimeType()]) > 0
        ) {
            $filters = $config[$asset->getMimeType()];
        } else {
            $extension = $this->getMimeResolver()->getExtension($asset->getMimeType());
            if (isset($config[$extension])
                && is_array($config[$extension])
                && count($config[$extension]) > 0
            ) {
                $filters = $config[$extension];
            } else {
                return;
            }
        }

        foreach ($filters as $filter) {
            if (is_null($filter)) {
                continue;
            }
            if (isset($filter['filter'])) {
                $this->ensureByFilter($asset, $filter['filter']);
            } elseif (isset($filter['service'])) {
                $this->ensureByService($asset, $filter['service']);
            } else {
                throw new RuntimeException(
                    'Invalid filter supplied. Expected Filter or Service.'
                );
            }
        }
    }

    /**
     * Ensure that the filters as service are set.
     *
     * @param AssetInterface $asset
     * @param mixed $service A valid service name.
     * @throws  RuntimeException
     */
    protected function ensureByService(AssetInterface $asset, $service)
    {
        if (is_string($service)) {
            $this->ensureByFilter($asset, $this->getServiceLocator()->get($service));
        } else {
            throw new RuntimeException(
                'Unexpected service provided. Expected string or callback.'
            );
        }
    }

    /**
     * Ensure that the filters as filter are set.
     *
     * @param AssetInterface $asset
     * @param mixed $filter Either an instance of FilterInterface or a classname.
     * @throws  RuntimeException
     */
    protected function ensureByFilter(AssetInterface $asset, $filter)
    {
        if ($filter instanceof FilterInterface) {
            $filterInstance = $filter;
            $asset->ensureFilter($filterInstance);

            return;
        }

        $filterClass = $filter;

        if (!is_subclass_of($filterClass, 'Assetic\Contracts\Filter\FilterInterface', true)) {
            $filterClass .= (substr($filterClass, -6) === 'Filter') ? '' : 'Filter';
            $filterClass = 'Assetic\Filter\\' . $filterClass;
        }

        if (!class_exists($filterClass)) {
            throw new RuntimeException(
                'No filter found for ' . $filter
            );
        }

        if (!isset($this->filterInstances[$filterClass])) {
            $this->filterInstances[$filterClass] = new $filterClass();
        }

        $filterInstance = $this->filterInstances[$filterClass];

        $asset->ensureFilter($filterInstance);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeResolver()
    {
        return $this->mimeResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function setMimeResolver(MimeResolver $resolver)
    {
        $this->mimeResolver = $resolver;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
