<?php

require_once __DIR__ . '/../../core/Database.php';

/**
 * SsoManager
 *
 * Manages SSO site registration and one-time verification tokens.
 * Replaces config/sso.json with database-backed storage.
 *
 * Flow:
 *   1. sso/index.php calls isDomainRegistered() to allow/reject the SSO request
 *   2. sso/index.php calls createToken() after the user is authenticated
 *   3. sso/verify.php calls verifyAndConsumeToken() when the WP plugin calls back
 */
class SsoManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // -------------------------------------------------------------------------
    // Site Registry
    // -------------------------------------------------------------------------

    /**
     * Check if a domain is registered and active for SSO.
     */
    public function isDomainRegistered(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        $row    = $this->db->fetch(
            'SELECT id FROM sso_sites WHERE domain = :domain AND is_active = 1',
            [':domain' => $domain]
        );
        return $row !== false;
    }

    /**
     * Register a new WordPress site for SSO.
     *
     * @param string $domain     Hostname (e.g. site.kinsta.cloud)
     * @param string $createdBy  Email of the sitebuilder user performing the action
     * @param string $notes      Optional notes (deployment ID, site name, etc.)
     * @return bool True on success, false if domain already exists
     */
    public function registerSite(string $domain, string $createdBy, string $notes = ''): bool
    {
        $domain = strtolower(trim($domain));
        if (empty($domain)) {
            return false;
        }

        // Check if domain is already registered (active or not)
        $existing = $this->db->fetch(
            'SELECT id, is_active FROM sso_sites WHERE domain = :domain',
            [':domain' => $domain]
        );

        if ($existing) {
            // Re-activate if previously deactivated
            if (! $existing['is_active']) {
                $this->db->update(
                    'sso_sites',
                    ['is_active' => 1, 'created_by' => $createdBy, 'notes' => $notes],
                    ['domain' => $domain]
                );
                return true;
            }
            // Already active — treat as success (idempotent)
            return true;
        }

        try {
            $this->db->insert('sso_sites', [
                'domain'     => $domain,
                'is_active'  => 1,
                'created_by' => $createdBy,
                'notes'      => $notes,
            ]);
            return true;
        } catch (\PDOException $e) {
            error_log('SsoManager::registerSite failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deactivate a site's SSO access without deleting the record.
     */
    public function deactivateSite(string $domain): bool
    {
        $domain  = strtolower(trim($domain));
        $updated = $this->db->update(
            'sso_sites',
            ['is_active' => 0],
            ['domain' => $domain]
        );
        return $updated > 0;
    }

    /**
     * Return all registered sites for the web UI.
     */
    public function listSites(): array
    {
        return $this->db->fetchAll(
            'SELECT id, domain, is_active, created_by, notes, created_at, updated_at
             FROM sso_sites
             ORDER BY created_at DESC'
        );
    }

    // -------------------------------------------------------------------------
    // One-Time Verification Tokens
    // -------------------------------------------------------------------------

    /**
     * Create a short-lived one-time token for the SSO callback.
     *
     * The token is passed to the WP plugin in the redirect URL, which then calls
     * /sso/verify to exchange it for user data. Token is valid for 5 minutes.
     *
     * @param string $domain  Domain this token is issued for
     * @param string $email   Authenticated user's email
     * @param string $name    Authenticated user's display name
     * @return string         64-character hex token
     */
    public function createToken(string $domain, string $email, string $name): string
    {
        $token     = bin2hex(random_bytes(32)); // 64 hex chars
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes

        $this->db->insert('sso_tokens', [
            'token'      => $token,
            'domain'     => strtolower(trim($domain)),
            'email'      => $email,
            'name'       => $name,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Verify a token and mark it as used (single-use).
     *
     * Checks that the token:
     *   - exists in the database
     *   - has not been used (used_at IS NULL)
     *   - has not expired (expires_at > NOW())
     *   - belongs to the claiming domain
     *
     * On success, sets used_at to the current time and returns user data.
     * Returns null on any failure (no detail leaked to the caller).
     *
     * @param string $token   64-char hex token from the callback URL
     * @param string $domain  Domain claiming the token
     * @return array|null     ['email' => ..., 'name' => ...] or null
     */
    public function verifyAndConsumeToken(string $token, string $domain): ?array
    {
        $domain = strtolower(trim($domain));
        $token  = trim($token);

        if (empty($token) || empty($domain)) {
            return null;
        }

        $row = $this->db->fetch(
            'SELECT id, domain, email, name, expires_at, used_at
             FROM sso_tokens
             WHERE token = :token',
            [':token' => $token]
        );

        if (! $row) {
            return null; // Token not found
        }

        if ($row['used_at'] !== null) {
            return null; // Already consumed
        }

        if (strtotime($row['expires_at']) < time()) {
            return null; // Expired
        }

        if (strtolower($row['domain']) !== $domain) {
            return null; // Domain mismatch
        }

        // Mark as used
        $this->db->update(
            'sso_tokens',
            ['used_at' => date('Y-m-d H:i:s')],
            ['id' => $row['id']]
        );

        return [
            'email' => $row['email'],
            'name'  => $row['name'],
        ];
    }

    /**
     * Delete expired and used tokens older than 24 hours to keep the table clean.
     *
     * @return int Number of rows deleted
     */
    public function cleanupTokens(): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - 86400);
        $stmt   = $this->db->query(
            'DELETE FROM sso_tokens WHERE expires_at < :cutoff OR used_at IS NOT NULL AND used_at < :cutoff2',
            [':cutoff' => $cutoff, ':cutoff2' => $cutoff]
        );
        return $stmt->rowCount();
    }
}
