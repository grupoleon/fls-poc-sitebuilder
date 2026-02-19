<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../admin/includes/Auth.php';
require_once __DIR__ . '/../admin/includes/SsoManager.php';

/**
 * SsoController
 *
 * Manages SSO site registration via the sitebuilder web interface.
 * All actions require the user to be authenticated as a sitebuilder admin.
 */
class SsoController
{
    private ?SsoManager $ssoManager = null;

    /**
     * Lazy getter — DB connection only happens when an SSO route is actually called,
     * not at bootstrap time. Prevents connection failures from breaking the entire app.
     */
    private function getSsoManager(): SsoManager
    {
        if ($this->ssoManager === null) {
            $this->ssoManager = new SsoManager();
        }
        return $this->ssoManager;
    }

    /**
     * List all registered SSO sites.
     *
     * Response: { success: true, data: [ { id, domain, is_active, created_by, notes, ... } ] }
     */
    public function listSites(): void
    {
        $this->requireAuth();

        $sites = $this->getSsoManager()->listSites();
        Response::success($sites);
    }

    /**
     * Register a new WordPress site for SSO.
     *
     * Input: { domain: "site.kinsta.cloud", notes: "optional note" }
     * Response: { success: true, message: "Site registered" }
     */
    public function registerSite(): void
    {
        $this->requireAuth();

        $input  = $this->getJsonInput();
        $domain = trim((string) ($input['domain'] ?? ''));
        $notes  = trim((string) ($input['notes'] ?? ''));

        if (empty($domain)) {
            Response::error('domain is required', null, 400);
            return;
        }

        // Validate domain format (no scheme, no path)
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            Response::error('Invalid domain format. Provide a hostname only (e.g. site.kinsta.cloud)', null, 400);
            return;
        }

        $createdBy = Auth::getEmail() ?: 'unknown';
        $success   = $this->getSsoManager()->registerSite($domain, $createdBy, $notes);

        if ($success) {
            Logger::info("SSO site registered: {$domain} by {$createdBy}");
            Response::success(null, "Site '{$domain}' registered for SSO");
        } else {
            Response::error('Failed to register site. Please try again.', null, 500);
        }
    }

    /**
     * Deactivate a site's SSO access.
     *
     * Input: { domain: "site.kinsta.cloud" }
     * Response: { success: true, message: "Site deactivated" }
     */
    public function deactivateSite(): void
    {
        $this->requireAuth();

        $input  = $this->getJsonInput();
        $domain = trim((string) ($input['domain'] ?? ''));

        if (empty($domain)) {
            Response::error('domain is required', null, 400);
            return;
        }

        $success = $this->getSsoManager()->deactivateSite($domain);

        if ($success) {
            Logger::info("SSO site deactivated: {$domain} by " . (Auth::getEmail() ?: 'unknown'));
            Response::success(null, "SSO access revoked for '{$domain}'");
        } else {
            Response::error("Site '{$domain}' not found or already inactive", null, 404);
        }
    }

    /**
     * Update notes for a registered site, and optionally re-activate it.
     *
     * Input: { domain: "site.kinsta.cloud", notes: "updated note", activate: true }
     * Response: { success: true, message: "Site updated" }
     */
    public function updateSite(): void
    {
        $this->requireAuth();

        $input    = $this->getJsonInput();
        $domain   = trim((string) ($input['domain'] ?? ''));
        $notes    = trim((string) ($input['notes'] ?? ''));
        $activate = (bool) ($input['activate'] ?? false);

        if (empty($domain)) {
            Response::error('domain is required', null, 400);
            return;
        }

        $success = $this->getSsoManager()->updateSite($domain, $notes, $activate);

        if ($success) {
            Logger::info("SSO site updated: {$domain} by " . (Auth::getEmail() ?: 'unknown'));
            Response::success(null, "Site '{$domain}' updated");
        } else {
            Response::error("Site '{$domain}' not found", null, 404);
        }
    }

    /**
     * Delete expired/used SSO tokens older than 24 hours.
     *
     * Response: { success: true, data: { deleted: N } }
     */
    public function cleanupTokens(): void
    {
        $this->requireAuth();

        $deleted = $this->getSsoManager()->cleanupTokens();
        Logger::info("SSO token cleanup: {$deleted} tokens removed");
        Response::success(['deleted' => $deleted], "Cleaned up {$deleted} expired tokens");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function requireAuth(): void
    {
        Auth::init();
        if (! Auth::isLoggedIn()) {
            Response::error('Unauthorized', null, 401);
            exit;
        }
    }

    private function getJsonInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            return json_decode($raw, true) ?: [];
        }
        return $_POST ?: [];
    }
}
