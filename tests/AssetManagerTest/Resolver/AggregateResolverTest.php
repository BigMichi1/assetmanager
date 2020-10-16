<?php

namespace AssetManagerTest\Resolver;

use AssetManager\Resolver\AggregateResolver;
use AssetManager\Resolver\ResolverInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class AggregateResolverTest extends TestCase
{
    public function testResolve()
    {
        $resolver = new AggregateResolver();

        Assert::assertTrue($resolver instanceof ResolverInterface);

        $lowPriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $lowPriority
            ->expects(TestCase::once())
            ->method('resolve')
            ->with('to-be-resolved')
            ->will(TestCase::returnValue('first'));
        $resolver->attach($lowPriority);

        Assert::assertSame('first', $resolver->resolve('to-be-resolved'));

        $highPriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $highPriority
            ->expects(TestCase::exactly(2))
            ->method('resolve')
            ->with('to-be-resolved')
            ->will(TestCase::returnValue('second'));
        $resolver->attach($highPriority, 1000);

        Assert::assertSame('second', $resolver->resolve('to-be-resolved'));

        $averagePriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $averagePriority
            ->expects(TestCase::never())
            ->method('resolve')
            ->will(TestCase::returnValue('third'));
        $resolver->attach($averagePriority, 500);

        Assert::assertSame('second', $resolver->resolve('to-be-resolved'));
    }

    public function testCollectWithCollectMethod()
    {
        $resolver = new AggregateResolver();
        $lowPriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $lowPriority
            ->expects(TestCase::exactly(2))
            ->method('collect')
            ->will(TestCase::returnValue(array('one', 'two')));
        $resolver->attach($lowPriority);

        Assert::assertContains('one', $resolver->collect());

        $highPriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $highPriority
            ->expects(TestCase::once())
            ->method('collect')
            ->will(TestCase::returnValue(array('three')));
        $resolver->attach($highPriority, 1000);

        $collection = $resolver->collect();
        Assert::assertContains('one', $collection);
        Assert::assertContains('three', $collection);

        Assert::assertCount(3, $collection);
    }

    public function testCollectWithoutCollectMethod()
    {
        $resolver = new AggregateResolver();
        $lowPriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $lowPriority
            ->expects(TestCase::exactly(2))
            ->method('collect')
            ->will(TestCase::returnValue([]));

        $resolver->attach($lowPriority);

        Assert::assertEquals(array(), $resolver->collect());

        $highPriority = $this
            ->getMockBuilder(ResolverInterface::class)
            ->getMock();
        $highPriority
            ->expects(TestCase::exactly(1))
            ->method('collect')
            ->will(TestCase::returnValue([]));
        $resolver->attach($highPriority, 1000);

        $collection = $resolver->collect();
        Assert::assertEquals(array(), $collection);

        Assert::assertCount(0, $collection);
    }
}
