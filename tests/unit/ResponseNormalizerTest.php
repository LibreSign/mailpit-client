<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit;

use LibreSign\Mailpit\ResponseNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ResponseNormalizerTest extends TestCase
{
    private ResponseNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ResponseNormalizer();
    }

    #[Test]
    public function it_should_decode_valid_json_response(): void
    {
        $json = '{"message": "success", "data": {"id": 123}}';

        $result = $this->normalizer->decodeJsonResponse($json, 'test');

        $this->assertEquals(['message' => 'success', 'data' => ['id' => 123]], $result);
    }

    #[Test]
    public function it_should_throw_exception_for_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Mailpit JSON response while fetching test context');

        $this->normalizer->decodeJsonResponse('invalid json', 'test context');
    }

    #[Test]
    public function it_should_throw_exception_when_json_is_not_an_array(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Mailpit JSON response while fetching scalar value');

        $this->normalizer->decodeJsonResponse('"just a string"', 'scalar value');
    }

    #[Test]
    public function it_should_preserve_all_string_keys_from_json_response(): void
    {
        $json = '{"valid": "kept", "name": "value", "key123": "also kept"}';

        $result = $this->normalizer->decodeJsonResponse($json, 'test');

        $this->assertEquals(['valid' => 'kept', 'name' => 'value', 'key123' => 'also kept'], $result);
    }

    #[Test]
    public function it_should_normalize_array_of_string_keyed_arrays(): void
    {
        $input = [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ];

        $result = $this->normalizer->normalizeArrayOfStringKeyedArrays($input);

        $this->assertEquals($input, $result);
    }

    #[Test]
    public function it_should_filter_non_array_items_from_array(): void
    {
        $input = [
            ['name' => 'John'],
            'not an array',
            ['name' => 'Jane'],
            123,
            null,
        ];

        $result = $this->normalizer->normalizeArrayOfStringKeyedArrays($input);

        $this->assertEquals([
            ['name' => 'John'],
            ['name' => 'Jane'],
        ], $result);
    }

    #[Test]
    public function it_should_filter_numeric_keys_from_nested_arrays(): void
    {
        $input = [
            ['valid' => 'kept', 0 => 'removed'],
            ['another' => 'kept', 1 => 'removed'],
        ];

        $result = $this->normalizer->normalizeArrayOfStringKeyedArrays($input);

        $this->assertEquals([
            ['valid' => 'kept'],
            ['another' => 'kept'],
        ], $result);
    }

    #[Test]
    public function it_should_skip_empty_arrays_after_normalization(): void
    {
        $input = [
            [0 => 'all numeric keys'],
            ['valid' => 'kept'],
            [1 => 'numeric', 2 => 'keys'],
        ];

        $result = $this->normalizer->normalizeArrayOfStringKeyedArrays($input);

        $this->assertEquals([['valid' => 'kept']], $result);
    }

    #[Test]
    public function it_should_return_empty_array_when_input_is_not_array(): void
    {
        $result = $this->normalizer->normalizeArrayOfStringKeyedArrays('not an array');

        $this->assertEquals([], $result);
    }

    #[Test]
    public function it_should_normalize_header_map_correctly(): void
    {
        $input = [
            'Content-Type' => ['text/html', 'charset=utf-8'],
            'X-Custom' => ['value1', 'value2'],
        ];

        $result = $this->normalizer->normalizeHeaderMap($input);

        $this->assertEquals($input, $result);
    }

    #[Test]
    public function it_should_filter_non_string_header_names(): void
    {
        $input = [
            'Content-Type' => ['text/html'],
            0 => ['should be removed'],
            123 => ['also removed'],
        ];

        $result = $this->normalizer->normalizeHeaderMap($input);

        $this->assertEquals(['Content-Type' => ['text/html']], $result);
    }

    #[Test]
    public function it_should_filter_non_array_header_values(): void
    {
        $input = [
            'Content-Type' => ['text/html'],
            'Invalid-Header' => 'not an array',
            'Another-Invalid' => 123,
        ];

        $result = $this->normalizer->normalizeHeaderMap($input);

        $this->assertEquals(['Content-Type' => ['text/html']], $result);
    }

    #[Test]
    public function it_should_filter_non_string_values_from_header_arrays(): void
    {
        $input = [
            'X-Mixed' => ['valid', 123, 'also valid', null, false],
        ];

        $result = $this->normalizer->normalizeHeaderMap($input);

        $this->assertEquals(['X-Mixed' => ['valid', 'also valid']], $result);
    }

    #[Test]
    public function it_should_skip_headers_with_empty_values_after_filtering(): void
    {
        $input = [
            'Valid' => ['value'],
            'OnlyInvalid' => [123, null, false],
            'Another' => ['valid'],
        ];

        $result = $this->normalizer->normalizeHeaderMap($input);

        $this->assertEquals([
            'Valid' => ['value'],
            'Another' => ['valid'],
        ], $result);
    }

    #[Test]
    public function it_should_return_empty_array_when_header_map_is_not_array(): void
    {
        $result = $this->normalizer->normalizeHeaderMap('not an array');

        $this->assertEquals([], $result);
    }

    #[Test]
    public function it_should_normalize_string_keyed_array(): void
    {
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $result = $this->normalizer->normalizeStringKeyedArray($input);

        $this->assertEquals($input, $result);
    }

    #[Test]
    public function it_should_filter_numeric_keys(): void
    {
        $input = [
            'valid' => 'kept',
            0 => 'removed',
            'another' => 'kept',
            1 => 'removed',
        ];

        $result = $this->normalizer->normalizeStringKeyedArray($input);

        $this->assertEquals(['valid' => 'kept', 'another' => 'kept'], $result);
    }

    #[Test]
    public function it_should_preserve_all_value_types_in_normalized_array(): void
    {
        $input = [
            'string' => 'text',
            'number' => 123,
            'null' => null,
            'bool' => true,
            'array' => ['nested'],
        ];

        $result = $this->normalizer->normalizeStringKeyedArray($input);

        $this->assertEquals($input, $result);
    }

    #[Test]
    public function it_should_handle_empty_arrays(): void
    {
        $this->assertEquals([], $this->normalizer->normalizeStringKeyedArray([]));
        $this->assertEquals([], $this->normalizer->normalizeArrayOfStringKeyedArrays([]));
        $this->assertEquals([], $this->normalizer->normalizeHeaderMap([]));
    }
}
