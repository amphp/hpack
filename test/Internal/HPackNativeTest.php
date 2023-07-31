<?php declare(strict_types=1);

namespace Amp\Http\Internal;

use Amp\Http\HPackTest;

class HPackNativeTest extends HPackTest
{
    protected function createInstance(): HPackNative
    {
        return new HPackNative;
    }
}
