<?php

/**
 * Config
 * Configuration utilities and helpers
 */
class Config
{
    /**
     * Deep merge two arrays
     *
     * @param array $existing Existing array
     * @param array $new New array to merge
     * @return array Merged array
     */
    public static function deepMerge(array $existing, array $new): array
    {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {
                if (self::isIndexedArray($value) || empty($value)) {
                    $existing[$key] = $value;
                } else {
                    $existing[$key] = self::deepMerge($existing[$key], $value);
                }
            } else {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    /**
     * Check if array is indexed (not associative)
     *
     * @param array $array Array to check
     * @return bool
     */
    public static function isIndexedArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Get nested value from array using dot notation
     *
     * @param array $array Array to search
     * @param string $key Dot notation key (e.g., 'user.profile.name')
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set nested value in array using dot notation
     *
     * @param array &$array Array to modify
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys    = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $current[$segment] = $value;
            } else {
                if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }

    /**
     * Check if nested key exists using dot notation
     *
     * @param array $array Array to search
     * @param string $key Dot notation key
     * @return bool
     */
    public static function has(array $array, string $key): bool
    {
        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Remove a key from array using dot notation
     *
     * @param array &$array Array to modify
     * @param string $key Dot notation key
     */
    public static function remove(array &$array, string $key): void
    {
        $keys    = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                unset($current[$segment]);
            } else {
                if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                    return;
                }
                $current = &$current[$segment];
            }
        }
    }

    /**
     * Normalize array values (ensure proper types)
     *
     * @param array $config Configuration array
     * @param array $typeMap Type mappings (field => type)
     * @return array
     */
    public static function normalizeTypes(array $config, array $typeMap): array
    {
        foreach ($typeMap as $path => $expectedType) {
            $keys    = explode('.', $path);
            $current = &$config;
            $valid   = true;

            foreach ($keys as $i => $key) {
                if ($i === count($keys) - 1) {
                    if (isset($current[$key])) {
                        $current[$key] = self::castToType($current[$key], $expectedType);
                    } else {
                        $current[$key] = self::getDefaultForType($expectedType);
                    }
                } else {
                    if (! isset($current[$key]) || ! is_array($current[$key])) {
                        $valid = false;
                        break;
                    }
                    $current = &$current[$key];
                }
            }
        }

        return $config;
    }

    /**
     * Cast value to specific type
     *
     * @param mixed $value Value to cast
     * @param string $type Target type
     * @return mixed
     */
    private static function castToType($value, string $type)
    {
        switch ($type) {
            case 'array':
                if (! is_array($value)) {
                    if (is_string($value) && ($value[0] ?? '') === '[') {
                        $decoded = json_decode($value, true);
                        return is_array($decoded) ? $decoded : [];
                    } elseif (is_string($value)) {
                        return array_filter(array_map('trim', explode(',', $value)));
                    }
                    return [];
                }
                return array_values(array_unique($value));

            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'int':
            case 'integer':
                return (int) $value;

            case 'float':
            case 'double':
                return (float) $value;

            case 'string':
                return (string) $value;

            default:
                return $value;
        }
    }

    /**
     * Get default value for type
     *
     * @param string $type Type name
     * @return mixed
     */
    private static function getDefaultForType(string $type)
    {
        switch ($type) {
            case 'array':
                return [];
            case 'bool':
            case 'boolean':
                return false;
            case 'int':
            case 'integer':
                return 0;
            case 'float':
            case 'double':
                return 0.0;
            case 'string':
                return '';
            default:
                return null;
        }
    }

    /**
     * Filter config by schema (whitelist approach)
     *
     * @param array $config Configuration array
     * @param array $schema Allowed keys schema
     * @return array
     */
    public static function filterBySchema(array $config, array $schema): array
    {
        $filtered = [];

        foreach ($config as $key => $value) {
            if (in_array($key, $schema, true)) {
                if (is_array($value) && isset($schema[$key]) && is_array($schema[$key])) {
                    $filtered[$key] = self::filterBySchema($value, $schema[$key]);
                } else {
                    $filtered[$key] = $value;
                }
            } elseif (isset($schema[$key])) {
                if (is_array($schema[$key]) && is_array($value)) {
                    $filtered[$key] = self::filterBySchema($value, $schema[$key]);
                } else {
                    $filtered[$key] = $value;
                }
            }
        }

        return $filtered;
    }

    /**
     * Validate config structure
     *
     * @param array $config Configuration to validate
     * @param array $required Required keys
     * @return array Array of missing keys
     */
    public static function validateRequired(array $config, array $required): array
    {
        $missing = [];

        foreach ($required as $key) {
            if (! self::has($config, $key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Flatten nested array with dot notation keys
     *
     * @param array $array Array to flatten
     * @param string $prefix Key prefix
     * @return array
     */
    public static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix !== '' ? "{$prefix}.{$key}" : $key;

            if (is_array($value) && ! self::isIndexedArray($value)) {
                $result = array_merge($result, self::flatten($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Unflatten array with dot notation keys
     *
     * @param array $array Flattened array
     * @return array
     */
    public static function unflatten(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            self::set($result, $key, $value);
        }

        return $result;
    }
}
