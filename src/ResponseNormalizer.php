<?php
declare(strict_types=1);

namespace LibreSign\Mailpit;

use RuntimeException;

final class ResponseNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public function decodeJsonResponse(string $body, string $context): array
    {
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Invalid Mailpit JSON response while fetching %s.', $context));
        }

        return $this->normalizeStringKeyedArray($data);
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>
     */
    public function normalizeArrayOfStringKeyedArrays(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalizedItem = $this->normalizeStringKeyedArray($item);
            if ($normalizedItem !== []) {
                $result[] = $normalizedItem;
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return array<string, array<int, string>>
     */
    public function normalizeHeaderMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $name => $values) {
            if (!is_string($name) || !is_array($values)) {
                continue;
            }

            $filteredValues = $this->filterStringValues($values);
            if ($filteredValues !== []) {
                $normalized[$name] = $filteredValues;
            }
        }

        return $normalized;
    }

    /**
     * @param array<mixed, mixed> $data
     * @return array<string, mixed>
     */
    public function normalizeStringKeyedArray(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param array<mixed, mixed> $values
     * @return array<int, string>
     */
    private function filterStringValues(array $values): array
    {
        $filtered = [];
        foreach ($values as $valueItem) {
            if (is_string($valueItem)) {
                $filtered[] = $valueItem;
            }
        }

        return $filtered;
    }
}
