<?php declare(strict_types=1);

namespace Amp\Http\Internal;

use Amp\Http\HPackTest;

class HPackNativeTest extends HPackTest
{
    protected function createInstance(): HPackNative
    {
        return new HPackNative;
    }

    public function testHuffmanEncodeDoesNotTriggerChrDeprecation(): void
    {
        $deprecations = [];

        \set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[$errstr] = true;
                return true;
            },
            \E_DEPRECATED
        );

        try {
            $hpack = self::createInstance();

            // Simple ASCII
            $headers = [
                ['x-test', 'hello world'],
            ];

            $encoded = $hpack->encode($headers);
            self::assertSame($headers, $hpack->decode($encoded, 8192));

            // Full byte range to exercise all Huffman codes
            $headers = [
                ['x-allbytes', \implode(\array_map('chr', \range(0, 255)))],
            ];

            $encoded = $hpack->encode($headers);
            self::assertSame($headers, $hpack->decode($encoded, 8192));

            // Long value to exercise multi-byte Huffman sequences
            $headers = [
                ['x-long', \str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 50)],
            ];

            $encoded = $hpack->encode($headers);
            self::assertSame($headers, $hpack->decode($encoded, 8192));

            // Multiple headers
            $headers = [
                [':method', 'GET'],
                [':path', '/foo/bar?baz=1&qux=2'],
                ['content-type', 'application/json'],
                ['x-binary', "\x00\x01\x02\xff\xfe\xfd"],
            ];

            $encoded = $hpack->encode($headers);
            self::assertSame($headers, $hpack->decode($encoded, 8192));
        } finally {
            \restore_error_handler();
        }

        self::assertEmpty(
            $deprecations,
            'chr() deprecation triggered: ' . \implode(', ', \array_keys($deprecations))
        );
    }
}
