--TEST--
HPackNghttp2 cannot be cloned, so its nghttp2 contexts are never freed twice
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

use Amp\Http\HPack;
use Amp\Http\Internal\HPackNghttp2;

$hpack = new HPackNghttp2;

try {
    $clone = clone $hpack;
    echo "cloned\n";
} catch (\Error $e) {
    echo \get_class($e), "\n";
}

echo $hpack->decode($hpack->encode([['a', 'b']]), 8192) === [['a', 'b']] ? "original: ok\n" : "original: mismatch\n";
unset($hpack);

// The public wrapper may be cloned, the copies share one HPackNghttp2 instance.
$hpack = new HPack;
$clone = clone $hpack;
unset($hpack);
echo $clone->decode($clone->encode([['a', 'b']]), 8192) === [['a', 'b']] ? "wrapper clone: ok\n" : "wrapper clone: mismatch\n";
unset($clone);

echo "done\n";
?>
--EXPECT--
Error
original: ok
wrapper clone: ok
done
