<?php declare(strict_types=1);

namespace Amp\Http;

use Amp\Http\Internal\HPackNative;
use Amp\Http\Internal\HPackNghttp2;
use PHPUnit\Framework\TestCase;

/** @group hpack */
abstract class HPackTest extends TestCase
{
    private const MAX_LENGTH = 8192;

    /**
     * @dataProvider provideDecodeCases
     *
     */
    public function testDecode(iterable $cases): void
    {
        $hpack = $this->createInstance();

        foreach ($cases as $i => [$input, $output]) {
            $result = $hpack->decode($input, self::MAX_LENGTH);
            $this->assertEquals($output, $result, "Failure on test case #$i");
        }
    }

    public function provideDecodeCases(): \Generator
    {
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

            yield "$path: $data->description" => [$cases];
        }
    }

    /**
     * @depends      testDecode
     * @dataProvider provideEncodeCases
     */
    public function testEncode($cases): void
    {
        foreach ($cases as $i => [$input, $output]) {
            $hpack = $this->createInstance();

            $encoded = $hpack->encode($input);
            $decoded = $hpack->decode($encoded, self::MAX_LENGTH);

            \sort($output);
            \sort($decoded);

            $this->assertEquals($output, $decoded, "Failure on test case #$i (standalone)");
        }

        // Ensure that usage of dynamic table works as expected
        $encHpack = $this->createInstance();
        $decHpack = $this->createInstance();

        foreach ($cases as $i => [$input, $output]) {
            $encoded = $encHpack->encode($input);
            $decoded = $decHpack->decode($encoded, self::MAX_LENGTH);

            \sort($output);
            \sort($decoded);

            $this->assertEquals($output, $decoded, "Failure on test case #$i (shared context)");
        }
    }

    public function provideEncodeCases(): \Generator
    {
        $root = __DIR__ . "/../vendor/http2jp/hpack-test-case";
        $paths = \glob("$root/raw-data/*.json");

        foreach ($paths as $path) {
            $data = \json_decode(\file_get_contents($path));
            $cases = [];
            $i = 0;

            foreach ($data->cases as $case) {
                $headers = [];

                foreach ($case->headers as &$header) {
                    $header = (array) $header;
                    $header = [\key($header), \current($header)];
                    $headers[] = $header;
                }

                $cases[$case->seqno ?? $i] = [$headers, $case->headers];
                $i++;
            }

            $description = $data->description ?? "no description";

            yield "$path: $description" => [$cases];
        }
    }

    public function testEncodeWithNonStrings(): void
    {
        $hpack = $this->createInstance();

        $stringable = new class() {
            public function __toString()
            {
                return 'value';
            }
        };

        $encoded = $hpack->encode([
            ['x-integer', 42],
            ['x-float', 3.14159],
            ['x-boolean', true],
            ['x-stringable', $stringable],
            [42, 'value'],
        ]);

        self::assertNotEmpty($encoded);
    }

    public function testEncodeInteger(): void
    {
        for ($i = 1; $i < 1024; $i++) {
            $hpack = $this->createInstance();

            $input = [
                ['x', \str_repeat('.', $i)],
            ];

            $encoded = $hpack->encode($input);

            self::assertSame($input, $hpack->decode($encoded, 128000), 'Length ' . $i);
        }

        self::assertNotEmpty($encoded);
    }

    /**
     * @return HPackNative|HPackNghttp2
     */
    abstract protected function createInstance();
}
