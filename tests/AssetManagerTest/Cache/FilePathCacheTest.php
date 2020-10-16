<?php

namespace AssetManagerTest\Cache;

use Assetic\Contracts\Cache\CacheInterface;
use AssetManager\Cache\FilePathCache;
use AssetManager\Exception\RuntimeException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FilePathCacheTest extends TestCase
{
    public function testConstruct()
    {
        $cache = new FilePathCache('/imagination', 'bacon.porn');
        Assert::assertTrue($cache instanceof CacheInterface);
    }

    public function testHas()
    {
        // Check fail
        $cache = new FilePathCache('/imagination', 'bacon.porn');
        Assert::assertFalse($cache->has('bacon'));

        // Check success
        $cache = new FilePathCache('', __FILE__);
        Assert::assertTrue($cache->has('bacon'));
    }

    public function testGetException()
    {
        $this->expectException(RuntimeException::class);
        $cache = new FilePathCache('/imagination', 'bacon.porn');
        $cache->get('bacon');
    }

    public function testGet()
    {
        $cache = new FilePathCache('', __FILE__);
        Assert::assertEquals(file_get_contents(__FILE__), $cache->get('bacon'));
    }

    public function testSetMayNotWriteFile()
    {
        $time = time();
        $base = sys_get_temp_dir() . '/_cachetest.' . $time . '/';
        try {
            $this->expectException(RuntimeException::class);
            restore_error_handler(); // Previous test fails, so doesn't unset.
            $sentence = 'I am, what I am. Cached data, please don\'t hate, '
                . 'for we are all equals. Except you, you\'re a dick.';
            mkdir($base, 0777);
            mkdir($base . 'readonly', 0400, true);

            $cache = new FilePathCache($base . 'readonly', 'bacon.' . $time . '.hammertime');
            $cache->set('bacon', $sentence);
        } finally {
            $this->rrmdir($base);
        }
    }

    public function testSetMayNotWriteDir()
    {
        $time = time() + 1;
        $base = sys_get_temp_dir() . '/_cachetest.' . $time . '/';
        try {
            $this->expectException(RuntimeException::class);
            restore_error_handler(); // Previous test fails, so doesn't unset.
            $sentence = 'I am, what I am. Cached data, please don\'t hate, '
                . 'for we are all equals. Except you, you\'re a dick.';
            mkdir($base, 0400, true);

            $cache = new FilePathCache($base . 'readonly', 'bacon.' . $time . '.hammertime');

            $cache->set('bacon', $sentence);
        } finally {
            $this->rrmdir($base);
        }
    }

    public function testSetCanNotWriteToFileThatExists()
    {
        $time = time() + 333;
        $base = sys_get_temp_dir() . '/_cachetest.' . $time . '/';
        try {
            $this->expectException(RuntimeException::class);
            restore_error_handler(); // Previous test fails, so doesn't unset.
            $sentence = 'I am, what I am. Cached data, please don\'t hate, '
                . 'for we are all equals. Except you, you\'re a dick.';
            mkdir($base, 0777);

            $fileName = 'sausage.' . $time . '.iceicebaby';

            touch($base . 'AssetManagerFilePathCache_' . $fileName);
            chmod($base . 'AssetManagerFilePathCache_' . $fileName, 0400);

            $cache = new FilePathCache($base, $fileName);

            $cache->set('bacon', $sentence);
        } finally {
            $this->rrmdir($base);
        }
    }

    public function testSetSuccess()
    {
        $time = time();
        $base = sys_get_temp_dir() . '/_cachetest.' . $time . '/';
        try {
            $sentence = 'I am, what I am. Cached data, please don\'t hate, '
                . 'for we are all equals. Except you, you\'re a dick.';
            $cache = new FilePathCache($base, 'bacon.' . $time);

            $cache->set('bacon', $sentence);
            Assert::assertEquals($sentence, file_get_contents($base . 'bacon.' . $time));
        } finally {
            $this->rrmdir($base);
        }
    }

    public function testRemoveFails()
    {
        $this->expectException(RuntimeException::class);
        $cache = new FilePathCache('/dev', 'null');

        $cache->remove('bacon');
    }

    public function testRemoveSuccess()
    {
        $time = time();
        $base = sys_get_temp_dir() . '/_cachetest.' . $time . '/';
        try {
            $sentence = 'I am, what I am. Cached data, please don\'t hate, '
                . 'for we are all equals. Except you, you\'re a dick.';
            $cache = new FilePathCache($base, 'bacon.' . $time);

            $cache->set('bacon', $sentence);

            Assert::assertTrue($cache->remove('bacon'));
        } finally {
            $this->rrmdir($base);
        }
    }

    public function testCachedFile()
    {
        $method = new ReflectionMethod(FilePathCache::class, 'cachedFile');

        $method->setAccessible(true);

        Assert::assertEquals(
            '/' . ltrim(__FILE__, '/'),
            $method->invoke(new FilePathCache('', __FILE__))
        );
    }

    private function rrmdir(string $dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
