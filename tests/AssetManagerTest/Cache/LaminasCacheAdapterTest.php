<?php

namespace AssetManagerTest\Cache;

use AssetManager\Cache\LaminasCacheAdapter;
use Laminas\Cache\Storage\Adapter\Memory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test file for Laminas Cache Adapter
 *
 * @package AssetManager\Cache
 */
class LaminasCacheAdapterTest extends TestCase
{
    public function testConstructor()
    {
        /** @var Memory&MockObject $mockLaminasCache */
        $mockLaminasCache = $this
            ->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adapter = new LaminasCacheAdapter($mockLaminasCache);

        Assert::assertInstanceOf(LaminasCacheAdapter::class, $adapter);
    }

    public function testHasMethodCallsLaminasCacheHasItem()
    {
        /** @var Memory&MockObject $mockLaminasCache */
        $mockLaminasCache = $this
            ->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('hasItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->has('SomeKey');
    }

    public function testGetMethodCallsLaminasCacheGetItem()
    {
        /** @var Memory&MockObject $mockLaminasCache */
        $mockLaminasCache = $this
            ->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('getItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->get('SomeKey');
    }

    public function testSetMethodCallsLaminasCacheSetItem()
    {
        /** @var Memory&MockObject $mockLaminasCache */
        $mockLaminasCache = $this
            ->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('setItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->set('SomeKey', '');
    }

    public function testRemoveMethodCallsLaminasCacheRemoveItem()
    {
        /** @var Memory&MockObject $mockLaminasCache */
        $mockLaminasCache = $this
            ->getMockBuilder(Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLaminasCache->expects(TestCase::once())
            ->method('removeItem');

        $adapter = new LaminasCacheAdapter($mockLaminasCache);
        $adapter->remove('SomeKey');
    }
}
