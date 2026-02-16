<?php

/**
 * Validator
 * Input validation and sanitization
 */
class Validator
{
    private $data   = [];
    private $errors = [];
    private $rules  = [];

    /**
     * Constructor
     *
     * @param array $data Data to validate
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     *
     * @param array $rules Validation rules
     * @return bool
     */
    public function validate(array $rules): bool
    {
        $this->rules  = $rules;
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value    = $this->data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply a validation rule
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule to apply
     */
    private function applyRule(string $field, $value, string $rule): void
    {
        if (str_contains($rule, ':')) {
            [$ruleName, $param] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $param    = null;
        }

        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $this->$method($field, $value, $param);
        }
    }

    /**
     * Validate required field
     */
    private function validateRequired(string $field, $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    /**
     * Validate email
     */
    private function validateEmail(string $field, $value): void
    {
        if ($value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid email';
        }
    }

    /**
     * Validate URL
     */
    private function validateUrl(string $field, $value): void
    {
        if ($value && ! filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid URL';
        }
    }

    /**
     * Validate minimum length
     */
    private function validateMin(string $field, $value, $param): void
    {
        if ($value && strlen($value) < (int) $param) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$param} characters";
        }
    }

    /**
     * Validate maximum length
     */
    private function validateMax(string $field, $value, $param): void
    {
        if ($value && strlen($value) > (int) $param) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$param} characters";
        }
    }

    /**
     * Validate numeric
     */
    private function validateNumeric(string $field, $value): void
    {
        if ($value && ! is_numeric($value)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be numeric';
        }
    }

    /**
     * Validate integer
     */
    private function validateInteger(string $field, $value): void
    {
        if ($value && ! filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be an integer';
        }
    }

    /**
     * Validate array
     */
    private function validateArray(string $field, $value): void
    {
        if ($value && ! is_array($value)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be an array';
        }
    }

    /**
     * Validate boolean
     */
    private function validateBoolean(string $field, $value): void
    {
        if ($value !== null && ! is_bool($value) && ! in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a boolean';
        }
    }

    /**
     * Validate JSON
     */
    private function validateJson(string $field, $value): void
    {
        if ($value) {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be valid JSON';
            }
        }
    }

    /**
     * Validate value is in list
     */
    private function validateIn(string $field, $value, $param): void
    {
        if ($value) {
            $options = explode(',', $param);
            if (! in_array($value, $options, true)) {
                $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be one of: ' . implode(', ', $options);
            }
        }
    }

    /**
     * Validate regex pattern
     */
    private function validateRegex(string $field, $value, $param): void
    {
        if ($value && ! preg_match($param, $value)) {
            $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' format is invalid';
        }
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     *
     * @return bool
     */
    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function validated(): array
    {
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }

    /**
     * Sanitize string
     *
     * @param string $value Value to sanitize
     * @return string
     */
    public static function sanitizeString(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize email
     *
     * @param string $value Value to sanitize
     * @return string
     */
    public static function sanitizeEmail(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL
     *
     * @param string $value Value to sanitize
     * @return string
     */
    public static function sanitizeUrl(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize filename
     *
     * @param string $value Value to sanitize
     * @return string
     */
    public static function sanitizeFilename(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', trim($value));
    }

    /**
     * Sanitize array values
     *
     * @param array $array Array to sanitize
     * @return array
     */
    public static function sanitizeArray(array $array): array
    {
        return array_map(fn($value) => is_string($value) ? self::sanitizeString($value) : $value, $array);
    }
}
