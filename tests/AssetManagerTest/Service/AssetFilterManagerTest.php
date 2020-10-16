<?php

namespace AssetManagerTest\Service;

use Assetic\Contracts\Filter\FilterInterface;
use AssetManager\Asset\AssetWithMimeTypeInterface;
use AssetManager\Asset\StringAsset;
use AssetManager\Exception\RuntimeException;
use AssetManager\Service\AssetFilterManager;
use CustomFilter;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class AssetFilterManagerTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        require_once __DIR__ . '/../../_files/CustomFilter.php';
    }

    public function testNulledValuesAreSkipped()
    {
        $assetFilterManager = new AssetFilterManager(array(
            'test/path.test' => array(
                'null_filters' => null
            )
        ));

        $asset = new StringAsset('Herp Derp');

        $assetFilterManager->setFilters('test/path.test', $asset);

        Assert::assertEquals('Herp Derp', $asset->dump());
    }

    public function testEnsureByService()
    {
        $assetFilterManager = new AssetFilterManager(array(
            'test/path.test' => array(
                array(
                    'service' => 'testFilter',
                ),
            ),
        ));

        $serviceManager = new ServiceManager();
        $serviceManager->setService('testFilter', new CustomFilter());
        $assetFilterManager->setServiceLocator($serviceManager);

        $asset = new StringAsset('Herp derp');

        $assetFilterManager->setFilters('test/path.test', $asset);

        Assert::assertEquals('called', $asset->dump());
    }

    public function testEnsureByServiceInvalid()
    {
        $this->expectException(RuntimeException::class);
        $assetFilterManager = new AssetFilterManager(array(
            'test/path.test' => array(
                array(
                    'service' => 9,
                ),
            ),
        ));

        $serviceManager = new ServiceManager();
        $serviceManager->setService('testFilter', new CustomFilter());
        $assetFilterManager->setServiceLocator($serviceManager);

        $asset = new StringAsset('Herp derp');

        $assetFilterManager->setFilters('test/path.test', $asset);

        Assert::assertEquals('called', $asset->dump());
    }

    public function testEnsureByInvalid()
    {
        $this->expectException(RuntimeException::class);
        $assetFilterManager = new AssetFilterManager(array(
            'test/path.test' => array(
                array(),
            ),
        ));

        $asset = new StringAsset('Herp derp');

        $assetFilterManager->setFilters('test/path.test', $asset);
    }

    public function testFiltersAreInstantiatedOnce()
    {
        $assetFilterManager = new AssetFilterManager(array(
            'test/path.test' => array(
                array(
                    'filter' => 'CustomFilter'
                ),
            ),
        ));

        $filterInstance = null;

        $asset = $this->getMockBuilder(AssetWithMimeTypeInterface::class)->getMock();
        $asset
            ->expects(TestCase::any())
            ->method('ensureFilter')
            ->with(Assert::callback(function (FilterInterface $filter) use (&$filterInstance): bool {
                if ($filterInstance === null) {
                    $filterInstance = $filter;
                }
                self::assertEquals($filter, $filterInstance);
                return $filter === $filterInstance;
            }));

        $assetFilterManager->setFilters('test/path.test', $asset);
        $assetFilterManager->setFilters('test/path.test', $asset);
    }
}
