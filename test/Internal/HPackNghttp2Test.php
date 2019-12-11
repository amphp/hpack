<?php

namespace Amp\Http\Internal;

use Amp\Http\HPackTest;

class HPackNghttp2Test extends HPackTest
{
    protected function createInstance(): HPackNghttp2
    {
        if (!HPackNghttp2::isSupported()) {
            $this->markTestSkipped(HPackNghttp2::class . ' is not supported in the current environment');
        }

        return new HPackNghttp2;
    }
}
