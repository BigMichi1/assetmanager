<?php
declare(strict_types=1);

namespace AssetManagerTest\Service;

use ArrayObject;
use IteratorAggregate;
use Traversable;

class ConcatIterable implements IteratorAggregate
{
    public $concatName1 = array(
        'concat 1.1',
        'concat 1.2',
        'concat 1.3',
        'concat 1.4',
    );

    public $concatName2 = array(
        'concat 2.1',
        'concat 2.2',
        'concat 2.3',
        'concat 2.4',
    );

    public $concatName3 = array(
        'concat 3.1',
        'concat 3.2',
        'concat 3.3',
        'concat 3.4',
    );

    public function getIterator(): Traversable
    {
        return new ArrayObject($this);
    }
}
