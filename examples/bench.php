<?php

use Amp\Http\HPack;

require __DIR__ . '/../vendor/autoload.php';

$root = __DIR__ . "/../vendor/http2jp/hpack-test-case";
$paths = \glob("$root/*/*.json");

foreach ($paths as $path) {
    if (\basename(\dirname($path)) === "raw-data") {
        continue;
    }

    $data = \json_decode(\file_get_contents($path));
    $cases = [];
    foreach ($data->cases as $case) {
        foreach ($case->headers as &$header) {
            $header = (array) $header;
            $header = [\key($header), \current($header)];
        }
        $cases[$case->seqno] = [\hex2bin($case->wire), $case->headers];
    }

    $tests[] = $cases;
}

$minDuration = \PHP_INT_MAX;

for ($i = 0; $i < 10; $i++) {
    $start = \microtime(true);

    foreach ($tests as $test) {
        $hpack = new Amp\Http\HPack;
        foreach ($cases as [$input, $output]) {
            $hpack->decode($input, 8192);
        }
    }

    $duration = \microtime(true) - $start;
    $minDuration = \min($minDuration, $duration);
}

print "$minDuration s" . PHP_EOL . PHP_EOL;
