<?php

namespace AssetManagerTest\Cache;

use AssetManager\Cache\LaminasCacheAdapter;
use DateTime;
use Laminas\Cache\Storage\Adapter\Memory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Test file for Laminas Cache Adapter
 *
 * @package AssetManager\Cache
 */
class LaminasCacheAdapterTest extends TestCase
{
    public function testConstructor()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapter = new LaminasCacheAdapter($mockLaminasCache);

        Assert::assertInstanceOf(LaminasCacheAdapter::class, $adapter);
    }

    public function testConstructorOnlyAcceptsALaminasCacheStorageInterface()
    {
        $this->expectException(TypeError::class);

        new LaminasCacheAdapter(new DateTime());
    }

    public function testHasMethodCallsLaminasCacheHasItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('hasItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->has('SomeKey');
    }

    public function testGetMethodCallsLaminasCacheGetItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('getItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->get('SomeKey');
    }

    public function testSetMethodCallsLaminasCacheSetItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('setItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->set('SomeKey', array());
    }

    public function testRemoveMethodCallsLaminasCacheRemoveItem()
    {
        $mockLaminasCache = $this->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('removeItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->remove('SomeKey');
    }
}
