<?php

namespace AssetManager\Resolver;

use Assetic\Factory\Resource\DirectoryResource;
use AssetManager\Asset\FileAsset;
use AssetManager\Exception\InvalidArgumentException;
use AssetManager\Service\MimeResolver;
use Laminas\Stdlib\SplStack;
use SplFileInfo;
use Traversable;

/**
 * This resolver allows you to resolve from a stack of paths.
 */
class PathStackResolver implements ResolverInterface, MimeResolverAwareInterface
{
    /**
     * @var SplStack
     */
    protected $paths;

    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
     *
     * @var bool
     */
    protected $lfiProtectionOn = true;

    /**
     * The mime resolver.
     *
     * @var MimeResolver
     */
    protected $mimeResolver;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paths = new SplStack();
    }

    /**
     * Set the mime resolver
     *
     * @param MimeResolver $resolver
     */
    public function setMimeResolver(MimeResolver $resolver)
    {
        $this->mimeResolver = $resolver;
    }

    /**
     * Get the mime resolver
     *
     * @return MimeResolver
     */
    public function getMimeResolver()
    {
        return $this->mimeResolver;
    }

    /**
     * Add many paths to the stack at once
     *
     * @param array|Traversable $paths
     */
    public function addPaths($paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }

    /**
     * Rest the path stack to the paths provided
     *
     * @param Traversable|array $paths
     * @throws InvalidArgumentException
     */
    public function setPaths($paths)
    {
        if (!is_array($paths) && !$paths instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'Invalid argument provided for $paths, expecting either an array or Traversable object, "%s" given',
                is_object($paths) ? get_class($paths) : gettype($paths)
            ));
        }

        $this->clearPaths();
        $this->addPaths($paths);
    }

    /**
     * Normalize a path for insertion in the stack
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path)
    {
        $path = rtrim($path, '/\\');
        $path .= DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * Add a single path to the stack
     *
     * @param string|null $path
     * @throws InvalidArgumentException
     */
    public function addPath(?string $path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid path provided; must be a string, received %s',
                gettype($path)
            ));
        }

        $this->paths[] = $this->normalizePath($path);
    }

    /**
     * Clear all paths
     *
     * @return void
     */
    public function clearPaths()
    {
        $this->paths = new SplStack();
    }

    /**
     * Returns stack of paths
     *
     * @return SplStack
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set LFI protection flag
     *
     * @param bool $flag
     */
    public function setLfiProtection(bool $flag)
    {
        $this->lfiProtectionOn = $flag;
    }

    /**
     * Return status of LFI protection flag
     *
     * @return bool
     */
    public function isLfiProtectionOn()
    {
        return $this->lfiProtectionOn;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($name)
    {
        if ($this->isLfiProtectionOn() && preg_match('#\.\.[\\\/]#', $name)) {
            return null;
        }

        foreach ($this->getPaths() as $path) {
            $file = new SplFileInfo($path . $name);

            if ($file->isReadable() && !$file->isDir()) {
                $filePath = $file->getRealPath();
                $mimeType = $this->getMimeResolver()->getMimeType($filePath);
                $asset = new FileAsset($filePath);

                $asset->setMimeType($mimeType);

                return $asset;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $collection = array();
        foreach ($this->getPaths() as $path) {
            $locations = new SplStack();
            $pathInfo = new SplFileInfo($path);
            $locations->push($pathInfo);
            $basePath = $this->normalizePath($pathInfo->getRealPath());

            while (!$locations->isEmpty()) {
                /** @var SplFileInfo $pathInfo */
                $pathInfo = $locations->pop();
                if (!$pathInfo->isReadable()) {
                    continue;
                }
                if ($pathInfo->isDir()) {
                    $dir = new DirectoryResource($pathInfo->getRealPath());
                    foreach ($dir as $resource) {
                        $locations->push(new SplFileInfo($resource));
                    }
                } elseif (!isset($collection[$pathInfo->getPath()])) {
                    $collection[] = substr($pathInfo->getRealPath(), strlen($basePath));
                }
            }
        }

        return $collection;
    }
}
