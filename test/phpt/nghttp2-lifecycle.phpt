--TEST--
HPackNghttp2 frees its nghttp2 inflater and deflater
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

$headers = [
    [':method', 'GET'],
    [':path', '/'],
    ['cookie', 'secret'],
    ['x-custom', \str_repeat('a', 1000)],
];

// Fill the dynamic tables, so both contexts own heap allocations beyond their initial state.
for ($i = 0; $i < 100; $i++) {
    $hpack = new HPackNghttp2;
    if ($hpack->decode($hpack->encode($headers), 8192) !== $headers) {
        echo "instance $i: mismatch\n";
    }
}
unset($hpack);
echo "instances: ok\n";

$hpack = new HPack;
echo $hpack->decode($hpack->encode($headers), 8192) === $headers ? "wrapper: ok\n" : "wrapper: mismatch\n";
unset($hpack);

// Failed decodes leave the inflater mid-block, freeing it must still release everything.
$hpack = new HPackNghttp2;
$encoded = $hpack->encode($headers);
echo $hpack->decode($encoded, 10) === null ? "too large: null\n" : "too large: decoded\n";
echo $hpack->decode("\xff\xff\xff\xff", 8192) === null ? "invalid: null\n" : "invalid: decoded\n";
unset($hpack);

// Explicit destructor calls must not free the contexts a second time.
$hpack = new HPackNghttp2;
$hpack->__destruct();
$hpack->__destruct();
unset($hpack);

echo "done\n";
?>
--EXPECT--
instances: ok
wrapper: ok
too large: null
invalid: null
done
