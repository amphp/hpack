--TEST--
HPackNghttp2 cannot be serialized and survives instances created without its constructor
--SKIPIF--
<?php
require __DIR__ . '/../../vendor/autoload.php';
if (!\Amp\Http\Internal\HPackNghttp2::isSupported()) {
    die('skip nghttp2 not available via FFI');
}
?>
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

use Amp\Http\Internal\HPackNghttp2;

$hpack = new HPackNghttp2;

try {
    serialize($hpack);
    echo "serialized\n";
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

unset($hpack);

try {
    unserialize(\sprintf('O:%d:"%s":0:{}', \strlen(HPackNghttp2::class), HPackNghttp2::class));
    echo "unserialized\n";
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

$instance = (new \ReflectionClass(HPackNghttp2::class))->newInstanceWithoutConstructor();
unset($instance);

echo "done\n";
?>
--EXPECT--
Serialization of class Amp\Http\Internal\HPackNghttp2 is not allowed
Unserialization of class Amp\Http\Internal\HPackNghttp2 is not allowed
done
