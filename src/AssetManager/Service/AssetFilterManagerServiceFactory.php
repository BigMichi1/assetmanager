<?php

namespace AssetManager\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AssetFilterManagerServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $filters = array();
        $config = $container->get('config');

        if (isset($config['asset_manager']['filters'])
            && is_array($config['asset_manager']['filters'])
            && count($config['asset_manager']['filters']) > 0
        ) {
            $filters = $config['asset_manager']['filters'];
        }

        $assetFilterManager = new AssetFilterManager($filters);

        $assetFilterManager->setServiceLocator($container);
        $assetFilterManager->setMimeResolver($container->get(MimeResolver::class));

        return $assetFilterManager;
    }
}
