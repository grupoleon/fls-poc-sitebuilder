<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Logger.php';

/**
 * PageController
 * Handles page content-related requests
 */
class PageController
{
    private $configManager;
    private $pageManager;

    public function __construct($configManager, $pageManager)
    {
        $this->configManager = $configManager;
        $this->pageManager   = $pageManager;
    }

    /**
     * Get available themes
     */
    public function getThemes(): void
    {
        Response::success($this->pageManager->getAvailableThemes());
    }

    /**
     * Get pages list
     */
    public function getPages(): void
    {
        $themes      = $this->configManager->getAvailableThemes();
        $themeConfig = $this->configManager->getConfig('theme');
        $activeTheme = $themeConfig['active_theme'] ?? ($themes[0] ?? 'FLS-One');

        Response::success([
            'themes'       => $themes,
            'active_theme' => $activeTheme,
        ]);
    }

    /**
     * Get theme pages
     */
    public function getThemePages(): void
    {
        $theme = $_GET['theme'] ?? '';

        if (empty($theme)) {
            Response::error('Theme parameter is required');
        }

        $pages = $this->pageManager->getThemePages($theme);
        Response::success(['pages' => $pages]);
    }

    /**
     * Get theme pages with names
     */
    public function getThemePagesWithNames(): void
    {
        $theme = $_GET['theme'] ?? '';

        if (empty($theme)) {
            Response::error('Theme parameter is required');
        }

        $pages = $this->pageManager->getThemePagesWithNames($theme);
        Response::success(['pages' => $pages]);
    }

    /**
     * Get page content
     */
    public function getPageContent(): void
    {
        $theme = $_GET['theme'] ?? '';
        $page  = $_GET['page'] ?? '';

        if (empty($theme) || empty($page)) {
            Response::error('Theme and page parameters are required');
        }

        $content = $this->pageManager->extractEditableContent($theme, $page);
        Response::success($content);
    }

    /**
     * Save page content
     */
    public function savePageContent(): void
    {
        $input = $this->getJsonInput();
        $theme = $input['theme'] ?? '';
        $page  = $input['page'] ?? '';

        if (empty($theme) || empty($page)) {
            Response::error('Theme and page are required for saving page content');
        }

        try {
            if (isset($input['sections'])) {
                foreach ($input['sections'] as $index => $sectionData) {
                    $this->pageManager->updateWidgetContent($theme, $page, $index, $sectionData);
                }
            } else {
                $data = $input['data'] ?? [];

                if (empty($data)) {
                    Response::error('Page data is required');
                }

                if (! $this->pageManager->saveCompletePageData($theme, $page, $data)) {
                    throw new \Exception('Failed to save page data');
                }
            }

            Response::success(null, 'Page content saved successfully');
        } catch (\Exception $e) {
            Logger::error("Page content save error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Upload page image
     */
    public function uploadPageImage(): void
    {
        require_once __DIR__ . '/../helpers/UploadHelper.php';

        if (! isset($_FILES['image'])) {
            Response::error('No image file uploaded');
        }

        $theme = $_POST['theme'] ?? '';
        $page  = $_POST['page'] ?? '';

        if (empty($theme) || empty($page)) {
            Response::error('Theme and page parameters are required');
        }

        try {
            $files = UploadHelper::normalizeFiles($_FILES['image']);

            if (empty($files)) {
                Response::error('No valid files uploaded');
            }

            $file = $files[0];

            $validation = UploadHelper::validate($file, [
                'max_size'           => 5 * 1024 * 1024, // 5MB
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ]);

            if (! $validation['valid']) {
                Response::error($validation['error']);
            }

            $uploadsDir  = dirname(dirname(__DIR__)) . '/uploads/images';
            $filename    = uniqid() . '_' . Validator::sanitizeFilename($file['name']);
            $destination = $uploadsDir . '/' . $filename;

            if (! UploadHelper::processImage($file, $destination, [
                'max_width'  => 1920,
                'max_height' => 1080,
                'quality'    => 85,
            ])) {
                throw new \Exception('Failed to process and save image');
            }

            $imageUrl = '/uploads/images/' . $filename;

            Response::success([
                'url'      => $imageUrl,
                'filename' => $filename,
            ], 'Image uploaded successfully');
        } catch (\Exception $e) {
            Logger::error("Image upload error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get JSON input from request body
     */
    private function getJsonInput(): array
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON input');
        }

        return $input ?: [];
    }
}
