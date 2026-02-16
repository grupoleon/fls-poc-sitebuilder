<?php

require_once __DIR__ . '/../core/Logger.php';

/**
 * KinstaService
 * Handles all Kinsta API interactions
 */
class KinstaService
{
    private $token;
    private $baseUrl = 'https://api.kinsta.com/v2';
    private $timeout = 30;

    /**
     * Constructor
     *
     * @param string|null $token Kinsta API token
     */
    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    /**
     * Get Kinsta API token from configuration
     *
     * @param object $configManager ConfigManager instance
     * @return string Token
     * @throws Exception If token not found
     */
    public static function getTokenFromConfig($configManager): string
    {
        $siteConfig = $configManager->getConfig('site');
        $mainConfig = $configManager->getConfig('main');

        if (! empty($siteConfig) && isset($siteConfig['kinsta_token']) && $siteConfig['kinsta_token']) {
            return $siteConfig['kinsta_token'];
        }

        if (! empty($mainConfig) && isset($mainConfig['site']['kinsta_token']) && $mainConfig['site']['kinsta_token']) {
            return $mainConfig['site']['kinsta_token'];
        }

        if (! empty($mainConfig) && isset($mainConfig['kinsta_token']) && $mainConfig['kinsta_token']) {
            return $mainConfig['kinsta_token'];
        }

        $alt = $configManager->getConfig('config');
        if (! empty($alt) && isset($alt['site']['kinsta_token']) && $alt['site']['kinsta_token']) {
            return $alt['site']['kinsta_token'];
        }

        $configFilePath = dirname(dirname(dirname(__FILE__))) . '/config/config.json';
        if (file_exists($configFilePath)) {
            $raw  = @file_get_contents($configFilePath);
            $json = @json_decode($raw, true);
            if (isset($json['site']['kinsta_token']) && $json['site']['kinsta_token']) {
                return $json['site']['kinsta_token'];
            }
        }

        throw new \Exception('Kinsta API token not configured');
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array|null $data Request data
     * @return array Response data
     * @throws Exception On API error
     */
    private function request(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        if (! $this->token) {
            throw new \Exception('Kinsta API token not set');
        }

        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        Logger::api("Kinsta API Request: {$method} {$url}");

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            Logger::error("Kinsta API request failed: {$curlError}", ['url' => $url]);
            throw new \Exception("Kinsta API request failed: {$curlError}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Logger::error("Kinsta API returned error code: {$httpCode}", [
                'url'      => $url,
                'response' => $response,
            ]);
            throw new \Exception("Kinsta API returned error code: {$httpCode}");
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error("Invalid JSON response from Kinsta API", ['response' => $response]);
            throw new \Exception('Invalid JSON response from Kinsta API');
        }

        Logger::api("Kinsta API Response: {$httpCode}", 'INFO', ['data' => $result]);

        return $result;
    }

    /**
     * Get site information
     *
     * @param string $siteId Site ID
     * @return array Site information
     */
    public function getSiteInfo(string $siteId): array
    {
        $data = $this->request("/sites/{$siteId}");

        if (! isset($data['site'])) {
            throw new \Exception('Invalid response from Kinsta API');
        }

        return [
            'site_id'      => $siteId,
            'site_url'     => 'https://' . ($data['site']['environments'][0]['primaryDomain']['name'] ?? ''),
            'domain'       => $data['site']['environments'][0]['primaryDomain']['name'] ?? '',
            'status'       => $data['site']['status'] ?? '',
            'name'         => $data['site']['name'] ?? '',
            'display_name' => $data['site']['display_name'] ?? '',
        ];
    }

    /**
     * List all sites for a company
     *
     * @param string $companyId Company ID
     * @return array List of sites
     */
    public function listSites(string $companyId): array
    {
        $data = $this->request("/sites?company={$companyId}");

        if (isset($data['company']['sites']) && is_array($data['company']['sites'])) {
            return $data['company']['sites'];
        } elseif (isset($data['sites']) && is_array($data['sites'])) {
            return $data['sites'];
        } elseif (is_array($data) && array_values($data) === $data) {
            return $data;
        }

        throw new \Exception('Invalid response from Kinsta API');
    }

    /**
     * Check if site exists
     *
     * @param string $siteTitle Site title to check
     * @param string $companyId Company ID
     * @return array ['exists' => bool, 'matching_sites' => array]
     */
    public function checkSiteExists(string $siteTitle, string $companyId): array
    {
        $sites = $this->listSites($companyId);

        $matchingSites         = [];
        $normalizedSearchTitle = strtolower(trim($siteTitle));
        $slugifiedSearchTitle  = $this->slugify($siteTitle);

        foreach ($sites as $site) {
            $siteName        = strtolower(trim($site['name'] ?? ''));
            $siteDisplayName = strtolower(trim($site['display_name'] ?? ''));

            if ($siteName === $normalizedSearchTitle ||
                $siteDisplayName === $normalizedSearchTitle ||
                $siteName === $slugifiedSearchTitle ||
                $siteDisplayName === $slugifiedSearchTitle) {
                $matchingSites[] = [
                    'id'           => $site['id'],
                    'name'         => $site['name'],
                    'display_name' => $site['display_name'] ?? '',
                ];
            }
        }

        return [
            'exists'         => count($matchingSites) > 0,
            'matching_sites' => $matchingSites,
        ];
    }

    /**
     * Get available regions
     *
     * @param string $companyId Company ID
     * @return array List of regions
     */
    public function getAvailableRegions(string $companyId): array
    {
        $data = $this->request("/companies/{$companyId}");

        if (! isset($data['company']['regions']) || ! is_array($data['company']['regions'])) {
            throw new \Exception('Invalid response: regions data not found');
        }

        $regions = [];
        foreach ($data['company']['regions'] as $region) {
            $regions[] = [
                'id'   => $region['id'] ?? '',
                'name' => $region['name'] ?? '',
            ];
        }

        return $regions;
    }

    /**
     * Delete a site
     *
     * @param string $siteId Site ID
     * @return bool Success status
     */
    public function deleteSite(string $siteId): bool
    {
        try {
            $this->request("/sites/{$siteId}", 'DELETE');
            Logger::info("Site deleted successfully from Kinsta: {$siteId}");
            return true;
        } catch (\Exception $e) {
            Logger::error("Failed to delete site from Kinsta: {$siteId}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get operation status
     *
     * @param string $operationId Operation ID
     * @return array Operation status
     */
    public function getOperationStatus(string $operationId): array
    {
        return $this->request("/operations/{$operationId}");
    }

    /**
     * Slugify a string (same as Kinsta does)
     *
     * @param string $text Text to slugify
     * @return string Slugified text
     */
    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('/[^\w\-]/', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Set timeout for API requests
     *
     * @param int $timeout Timeout in seconds
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Create instance from config manager
     *
     * @param object $configManager ConfigManager instance
     * @return self
     */
    public static function fromConfig($configManager): self
    {
        $token = self::getTokenFromConfig($configManager);
        return new self($token);
    }
}
