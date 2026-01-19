<?php
/**
 * Page Content Manager
 * Handles reading, writing, and managing SiteOrigin page layouts
 */

class PageContentManager
{
    private $pagesDir;
    private $uploadsDir;

    public function __construct()
    {
        $this->pagesDir   = dirname(dirname(dirname(__DIR__))) . '/pages';
        $this->uploadsDir = dirname(dirname(dirname(__DIR__))) . '/uploads/images';
    }

    /**
     * Get available themes from the pages directory
     */
    public function getAvailableThemes()
    {
        $themes       = [];
        $excludedDirs = ['cpt', 'slides', 'forms']; // Exclude non-theme directories
        $themesDir    = $this->pagesDir . '/themes';

        if (is_dir($themesDir)) {
            $dirs = scandir($themesDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($themesDir . '/' . $dir)) {
                    // Skip excluded directories that are not themes
                    if (! in_array($dir, $excludedDirs)) {
                        // Additional check: ensure it's a theme by checking for layouts directory
                        $layoutsDir = $themesDir . '/' . $dir . '/layouts';
                        if (is_dir($layoutsDir)) {
                            $themes[] = $dir;
                        }
                    }
                }
            }
        }

        return $themes;
    }

    /**
     * Get available pages for a specific theme
     */
    public function getThemePages($theme)
    {
        $pages      = [];
        $layoutsDir = $this->pagesDir . '/themes/' . $theme . '/layouts';

        if (is_dir($layoutsDir)) {
            $files = scandir($layoutsDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $pageName = pathinfo($file, PATHINFO_FILENAME);

                    // Filter out header and footer pages
                    if (! $this->isHeaderOrFooterPage($pageName)) {
                        $pages[] = $pageName;
                    }
                }
            }
        }

        // Reorder pages to have home, about, and contact first
        $orderedPages  = [];
        $priorityPages = ['home', 'about', 'contact'];

        // First add the priority pages in the specified order
        foreach ($priorityPages as $priority) {
            foreach ($pages as $key => $page) {
                $pageName = strtolower($page);
                // Match exact name or name with variations (e.g., "home", "home-2", "about", "about-2")
                if ($pageName === $priority || preg_match('/^' . preg_quote($priority) . '[-\s]*\d*$/', $pageName)) {
                    $orderedPages[] = $page;
                    unset($pages[$key]);
                }
            }
        }

        // Then add all remaining pages
        foreach ($pages as $page) {
            $orderedPages[] = $page;
        }

        return $orderedPages;
    }

    /**
     * Get available pages for a specific theme with their display names from JSON
     */
    public function getThemePagesWithNames($theme)
    {
        $pages      = [];
        $layoutsDir = $this->pagesDir . '/themes/' . $theme . '/layouts';

        if (is_dir($layoutsDir)) {
            $files = scandir($layoutsDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $pageName = pathinfo($file, PATHINFO_FILENAME);

                    // Filter out header and footer pages
                    if (! $this->isHeaderOrFooterPage($pageName)) {
                        $filePath    = $layoutsDir . '/' . $file;
                        $jsonContent = file_get_contents($filePath);
                        $layoutData  = json_decode($jsonContent, true);

                        $displayName = $layoutData['name'] ?? $pageName;

                        $pages[] = [
                            'id'   => $pageName,
                            'name' => $displayName,
                        ];
                    }
                }
            }
        }

        // Reorder pages to have home, about, and contact first
        $orderedPages  = [];
        $priorityPages = ['home', 'about', 'contact'];

        // First add the priority pages in the specified order
        foreach ($priorityPages as $priority) {
            foreach ($pages as $key => $page) {
                $pageId = strtolower($page['id']);
                // Match exact name or name with variations (e.g., "home", "home-2", "about", "about-2")
                if ($pageId === $priority || preg_match('/^' . preg_quote($priority) . '[-\s]*\d*$/', $pageId)) {
                    $orderedPages[] = $page;
                    unset($pages[$key]);
                }
            }
        }

        // Then add all remaining pages
        foreach ($pages as $page) {
            $orderedPages[] = $page;
        }

        return $orderedPages;
    }

    /**
     * Check if a page is a header or footer page
     */
    private function isHeaderOrFooterPage($pageName)
    {
        $pageName = strtolower($pageName);

        // Check for common header/footer patterns
        $excludePatterns = [
            'header',
            'footer',
            'nav',
            'navigation',
            'menu',
        ];

        foreach ($excludePatterns as $pattern) {
            if (strpos($pageName, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all pages for a theme (including headers/footers)
     */
    public function getAllThemePages($theme)
    {
        $pages      = [];
        $layoutsDir = $this->pagesDir . '/themes/' . $theme . '/layouts';

        if (is_dir($layoutsDir)) {
            $files = scandir($layoutsDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $pages[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }

        return $pages;
    }

    /**
     * Get page content for a specific theme and page
     */
    public function getPageContent($theme, $page)
    {
        $filePath = $this->pagesDir . '/themes/' . $theme . '/layouts/' . $page . '.json';

        if (! file_exists($filePath)) {
            throw new Exception("Page layout not found: {$theme}/{$page}");
        }

        $content = file_get_contents($filePath);
        $data    = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in {$theme}/{$page}: " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Update page content
     */
    public function updatePageContent($theme, $page, $content)
    {
        if (! $this->validatePageStructure($content)) {
            throw new Exception("Invalid page structure for SiteOrigin");
        }

        $filePath = $this->pagesDir . '/themes/' . $theme . '/layouts/' . $page . '.json';
        $dirPath  = dirname($filePath);

        // Ensure directory exists
        if (! is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $jsonData = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to encode JSON: " . json_last_error_msg());
        }

        $result = file_put_contents($filePath, $jsonData);
        if ($result === false) {
            throw new Exception("Failed to write page file: {$filePath}");
        }

        return true;
    }

    /**
     * Validate SiteOrigin page structure
     */
    private function validatePageStructure($data)
    {
        // Check for required SiteOrigin structure
        if (! isset($data['widgets']) || ! is_array($data['widgets'])) {
            return false;
        }

        if (! isset($data['grids']) || ! is_array($data['grids'])) {
            return false;
        }

        if (! isset($data['grid_cells']) || ! is_array($data['grid_cells'])) {
            return false;
        }

        return true;
    }

    /**
     * Extract editable content from SiteOrigin widgets
     * Now returns full page data to support new grid editor
     */
    public function extractEditableContent($theme, $page)
    {
        $pageData = $this->getPageContent($theme, $page);

        // Return the full page data for the new editor
        // This includes widgets, grids, and grid_cells arrays
        if (isset($pageData['widgets']) || isset($pageData['grids'])) {
            return $pageData;
        }

        // Fallback: create sections array for legacy format
        $editableContent = [];

        if (isset($pageData['widgets'])) {
            foreach ($pageData['widgets'] as $index => $widget) {
                $content = $this->extractWidgetContent($widget, $index);
                if ($content) {
                    $editableContent[] = $content;
                }
            }
        }

        return [
            'page_name' => $page,
            'theme'     => $theme,
            'sections'  => $editableContent,
        ];
    }

    /**
     * Extract content from a specific widget
     */
    private function extractWidgetContent($widget, $index)
    {
        $content = [
            'index'    => $index,
            'type'     => 'unknown',
            'editable' => [],
        ];

        // Determine widget type
        if (isset($widget['panels_info']['class'])) {
            $class = $widget['panels_info']['class'];
            switch ($class) {
                case 'SiteOrigin_Widget_Editor_Widget':
                    $content['type'] = 'text_editor';
                    if (isset($widget['text'])) {
                        $content['editable']['text']     = $this->cleanHtmlContent($widget['text']);
                        $content['editable']['raw_text'] = $widget['text'];
                    }
                    break;

                case 'SiteOrigin_Widget_Image_Widget':
                    $content['type']                       = 'image';
                    $content['editable']['image_url']      = $widget['image'] ?? '';
                    $content['editable']['image_fallback'] = $widget['image_fallback'] ?? '';
                    $content['editable']['alt_text']       = $widget['alt'] ?? '';
                    $content['editable']['title']          = $widget['title'] ?? '';

                    // Debug log for image widgets
                    error_log('Image Widget Data: ' . json_encode($widget));
                    break;

                case 'SiteOrigin_Widget_Features_Widget':
                    $content['type'] = 'features';
                    if (isset($widget['features']) && is_array($widget['features'])) {
                        $content['editable']['features'] = [];
                        foreach ($widget['features'] as $feature) {
                            $content['editable']['features'][] = [
                                'title'    => $feature['title'] ?? '',
                                'text'     => $this->cleanHtmlContent($feature['text'] ?? ''),
                                'raw_text' => $feature['text'] ?? '', // Preserve original HTML for editor
                                'image'    => $feature['icon_image_fallback'] ?? '',
                                'url'      => $feature['more_url'] ?? '',
                            ];
                        }
                    }
                    break;

                case 'SiteOrigin_Widget_Hero_Widget':
                    $content['type'] = 'hero';
                    if (isset($widget['frames']) && is_array($widget['frames'])) {
                        $content['editable']['frames'] = [];
                        foreach ($widget['frames'] as $frame) {
                            $frameContent = [
                                'background_image'          => $frame['background_image'] ?? '',
                                'background_image_fallback' => $frame['background_image_fallback'] ?? '',
                                'title'                     => $frame['title'] ?? '',
                                'text'                      => $this->cleanHtmlContent($frame['text'] ?? ''),
                                'raw_text'                  => $frame['text'] ?? '', // Preserve original HTML for editor
                            ];

                            // Extract any buttons from the frame
                            if (isset($frame['buttons']) && is_array($frame['buttons'])) {
                                $frameContent['buttons'] = [];
                                foreach ($frame['buttons'] as $button) {
                                    $frameContent['buttons'][] = [
                                        'text'  => $button['button_text'] ?? '',
                                        'url'   => $button['button_url'] ?? '',
                                        'style' => $button['button_style'] ?? 'primary',
                                    ];
                                }
                            }

                            $content['editable']['frames'][] = $frameContent;
                        }
                    }

                    // Log hero widget data for debugging
                    error_log('Hero Widget Data: ' . json_encode($widget));
                    break;
            }
        }

        // Extract any buttons from text content
        if (isset($content['editable']['text'])) {
            $buttons = $this->extractButtons($content['editable']['raw_text']);
            if (! empty($buttons)) {
                $content['editable']['buttons'] = $buttons;
            }
        }

        return empty($content['editable']) ? null : $content;
    }

    /**
     * Clean HTML content for editing - preserve styles and images
     */
    private function cleanHtmlContent($html)
    {
        // Remove shortcodes for display, but keep original for saving
        $cleaned = preg_replace('/\[([^\]]+)\]/', '', $html);

        // Use DOMDocument to properly handle HTML while preserving attributes
        if (extension_loaded('dom')) {
            $dom = new DOMDocument();
            // Suppress warnings for malformed HTML
            libxml_use_internal_errors(true);

            // Wrap in a container to handle fragments
            $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $cleaned . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            // Get the container content
            $container = $dom->getElementsByTagName('div')->item(0);
            if ($container) {
                $cleaned = '';
                foreach ($container->childNodes as $node) {
                    $cleaned .= $dom->saveHTML($node);
                }
            }
        } else {
            // Fallback: Allow img tags and common styling tags, preserve all attributes
            $cleaned = strip_tags($cleaned, '<h1><h2><h3><h4><h5><h6><p><br><strong><em><a><img><figure><div><span><ul><ol><li>');
        }

        return trim($cleaned);
    }

    /**
     * Extract button information from HTML
     */
    private function extractButtons($html)
    {
        $buttons = [];

        // Match [button] shortcodes
        if (preg_match_all('/\[button\s+([^\]]+)\]/', $html, $matches)) {
            foreach ($matches[1] as $match) {
                $button = [];

                // Extract button attributes
                if (preg_match('/text="([^"]+)"/', $match, $textMatch)) {
                    $button['text'] = $textMatch[1];
                }
                if (preg_match('/url="([^"]+)"/', $match, $urlMatch)) {
                    $button['url'] = $urlMatch[1];
                }
                if (preg_match('/style="([^"]+)"/', $match, $styleMatch)) {
                    $button['style'] = $styleMatch[1];
                }

                $buttons[] = $button;
            }
        }

        return $buttons;
    }

    /**
     * Update widget content with new data
     */
    public function updateWidgetContent($theme, $page, $sectionIndex, $newContent)
    {
        $pageData = $this->getPageContent($theme, $page);

        if (! isset($pageData['widgets'][$sectionIndex])) {
            throw new Exception("Widget not found at index: {$sectionIndex}");
        }

        $widget = &$pageData['widgets'][$sectionIndex];
        $class  = $widget['panels_info']['class'] ?? '';

        switch ($class) {
            case 'SiteOrigin_Widget_Editor_Widget':
                if (isset($newContent['text'])) {
                    $widget['text'] = $this->processTextContent($newContent['text'], $newContent);
                }
                break;

            case 'SiteOrigin_Widget_Image_Widget':
                // Debug log before update
                error_log('Before Update Image Widget: ' . json_encode($widget));
                error_log('New Content for Image Widget: ' . json_encode($newContent));

                // Handle image content with correct SiteOrigin format
                // SiteOrigin expects attachment IDs in 'image' field, not URLs
                if (isset($newContent['image'])) {
                    // Set image_fallback to the URL for automation processing
                    $widget['image_fallback'] = $newContent['image'];
                    // Set image to 0 as placeholder for attachment ID (will be populated by automation)
                    $widget['image'] = 0;
                }
                if (isset($newContent['image_url'])) {
                    $widget['image_fallback'] = $newContent['image_url'];
                    $widget['image']          = 0;
                }
                if (isset($newContent['image_fallback'])) {
                    $widget['image_fallback'] = $newContent['image_fallback'];
                    // If image_fallback is provided and image is not set or is a URL, set image to 0
                    if (! isset($widget['image']) || ! is_numeric($widget['image'])) {
                        $widget['image'] = 0;
                    }
                }

                // If we have an attachment ID (numeric value), clear the fallback
                if (isset($newContent['attachment_id']) && is_numeric($newContent['attachment_id'])) {
                    $widget['image']          = (int) $newContent['attachment_id'];
                    $widget['image_fallback'] = '';
                }

                // Also set size_mode to custom to ensure proper rendering
                $widget['size_mode'] = 'custom';

                if (isset($newContent['alt_text'])) {
                    $widget['alt'] = $newContent['alt_text'];
                }

                // Debug log after update
                error_log('After Update Image Widget: ' . json_encode($widget));
                break;

            case 'SiteOrigin_Widget_Features_Widget':
                if (isset($newContent['features']) && is_array($newContent['features'])) {
                    foreach ($newContent['features'] as $index => $feature) {
                        if (isset($widget['features'][$index])) {
                            if (isset($feature['title'])) {
                                $widget['features'][$index]['title'] = $feature['title'];
                            }
                            if (isset($feature['text'])) {
                                $widget['features'][$index]['text'] = '<p>' . $feature['text'] . '</p>';
                            }
                            if (isset($feature['image'])) {
                                $widget['features'][$index]['icon_image_fallback'] = $feature['image'];
                            }
                        }
                    }
                }
                break;

            case 'SiteOrigin_Widget_Hero_Widget':
                // Log before update
                error_log('Before Update Hero Widget: ' . json_encode($widget));

                if (isset($newContent['frames']) && is_array($newContent['frames'])) {
                    foreach ($newContent['frames'] as $index => $frame) {
                        if (isset($widget['frames'][$index])) {
                            if (isset($frame['background_image'])) {
                                $widget['frames'][$index]['background_image'] = $frame['background_image'];
                            }
                            if (isset($frame['background_image_fallback'])) {
                                $widget['frames'][$index]['background_image_fallback'] = $frame['background_image_fallback'];
                                // Also ensure the image displays correctly
                                $widget['frames'][$index]['background_image_type'] = 'image';
                            }
                            if (isset($frame['title'])) {
                                $widget['frames'][$index]['title'] = $frame['title'];
                            }
                            if (isset($frame['text'])) {
                                $widget['frames'][$index]['text'] = $frame['text'];
                            }

                            // Update buttons
                            if (isset($frame['buttons']) && is_array($frame['buttons'])) {
                                foreach ($frame['buttons'] as $btnIndex => $button) {
                                    if (isset($widget['frames'][$index]['buttons'][$btnIndex])) {
                                        if (isset($button['text'])) {
                                            $widget['frames'][$index]['buttons'][$btnIndex]['button_text'] = $button['text'];
                                        }
                                        if (isset($button['url'])) {
                                            $widget['frames'][$index]['buttons'][$btnIndex]['button_url'] = $button['url'];
                                        }
                                        if (isset($button['style'])) {
                                            $widget['frames'][$index]['buttons'][$btnIndex]['button_style'] = $button['style'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Log after update
                error_log('After Update Hero Widget: ' . json_encode($widget));
                break;
        }

        return $this->updatePageContent($theme, $page, $pageData);
    }

    /**
     * Process text content, adding back buttons and formatting
     */
    private function processTextContent($text, $contentData)
    {
        // Add back button shortcodes if they exist
        if (isset($contentData['buttons']) && is_array($contentData['buttons'])) {
            foreach ($contentData['buttons'] as $button) {
                $buttonShortcode = '[button';
                if (isset($button['text'])) {
                    $buttonShortcode .= ' text="' . $button['text'] . '"';
                }
                if (isset($button['url'])) {
                    $buttonShortcode .= ' url="' . $button['url'] . '"';
                }
                if (isset($button['style'])) {
                    $buttonShortcode .= ' style="' . $button['style'] . '"';
                }
                $buttonShortcode .= ' target="_self"]';

                $text .= "\n<p>" . $buttonShortcode . "</p>";
            }
        }

        return $text;
    }

    /**
     * Handle image upload
     */
    public function handleImageUpload($file, $type = 'general')
    {
        if (! is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (! in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Only images are allowed.");
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size is 10MB.");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $timestamp = time();

        // Handle subfolders for different image types
        $subfolder = '';
        if ($type === 'logo') {
            $filename = "logo_{$timestamp}.{$extension}";
        } elseif ($type === 'slides') {
            $subfolder = 'slides/';
            $filename  = "slide_{$timestamp}_" . uniqid() . ".{$extension}";
        } else {
            $filename = "img_{$timestamp}_" . uniqid() . ".{$extension}";
        }

        // Create subfolder if needed
        if ($subfolder) {
            $subfolderPath = $this->uploadsDir . '/' . $subfolder;
            if (! is_dir($subfolderPath)) {
                mkdir($subfolderPath, 0755, true);
            }
        }

        $targetPath = $this->uploadsDir . '/' . $subfolder . $filename;

        if (! move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload image.");
        }

        return $subfolder . $filename;
    }

    /**
     * Get all uploaded images
     */
    public function getUploadedImages()
    {
        $images = [];

        if (is_dir($this->uploadsDir)) {
            $files = scandir($this->uploadsDir);
            foreach ($files as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                    $images[] = [
                        'filename' => $file,
                        'path'     => 'uploads/images/' . $file,
                        'url'      => 'uploads/images/' . $file, // Fixed: Remove ../ relative path
                        'type'     => strpos($file, 'logo_') === 0 ? 'logo' : 'general',
                    ];
                }
            }
        }

        // Sort by modification time (newest first)
        usort($images, function ($a, $b) {
            $pathA = $this->uploadsDir . '/' . $a['filename'];
            $pathB = $this->uploadsDir . '/' . $b['filename'];
            return filemtime($pathB) - filemtime($pathA);
        });

        return $images;
    }

    /**
     * Clean unused files from uploads directory (images/videos)
     * Scans all JSON files in the pages folder for references and deletes
     * files in the uploads directory which are not referenced anywhere.
     *
     * @param bool $execute If true, actually deletes files. If false, returns what would be deleted.
     * @return array Summary with keys: total, referenced, to_delete, deleted, errors
     */
    public function cleanUnusedUploads($execute = false)
    {
        $result = [
            'success' => true,
            'data'    => [
                'total'      => 0,
                'referenced' => 0,
                'to_delete'  => [],
                'deleted'    => [],
                'errors'     => [],
            ],
        ];

        // Gather all files from uploads (recursive)
        $uploadsRoot = dirname(dirname(dirname(__DIR__))) . '/uploads';
        if (! is_dir($uploadsRoot)) {
            $result['success']          = false;
            $result['data']['errors'][] = 'Uploads directory not found.';
            return $result;
        }

        $allFiles   = [];
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'ogg', 'mov', 'avi'];

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsRoot));
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (! in_array($ext, $allowedExt)) {
                continue;
            }
            // only images/videos
            $absPath            = $file->getPathname();
            $relPath            = substr($absPath, strlen(dirname(dirname(dirname(__DIR__))) . '/'));
            $allFiles[$relPath] = $absPath;
        }

        $result['data']['total'] = count($allFiles);

        // Scan all JSON files under pages and collect content
        $pagesDir     = dirname(dirname(dirname(__DIR__))) . '/pages';
        $jsonContents = '';
        if (is_dir($pagesDir)) {
            $rii2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pagesDir));
            foreach ($rii2 as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if (strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION)) !== 'json') {
                    continue;
                }

                $jsonContents .= ' ' . file_get_contents($file->getPathname());
            }
        }

        // Build set of referenced file basenames and relative path references
        $referenced = [];
        foreach (array_keys($allFiles) as $rel) {
            $basename = basename($rel);
            // Check for common patterns in json contents
            $found    = false;
            $patterns = [
                $rel, // uploads/images/whatever.jpg
                '/' . $rel,
                '../' . $rel,
                $basename,
                'wp-content/uploads/' . $basename,
            ];
            foreach ($patterns as $patt) {
                if (strpos($jsonContents, $patt) !== false) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $referenced[] = $rel;
            }
        }

        $result['data']['referenced'] = count($referenced);

        // Determine which files to delete (present in uploads but not referenced)
        foreach ($allFiles as $rel => $abs) {
            if (in_array($rel, $referenced)) {
                continue;
            }

            $result['data']['to_delete'][] = $rel;
        }

        if ($execute) {
            // Attempt to delete
            foreach ($result['data']['to_delete'] as $rel) {
                $abs = $allFiles[$rel];
                if (file_exists($abs) && is_writable($abs)) {
                    if (@unlink($abs)) {
                        $result['data']['deleted'][] = $rel;
                    } else {
                        $result['data']['errors'][] = "Failed to delete $rel";
                    }
                } else {
                    $result['data']['errors'][] = "Not writable or missing: $rel";
                }
            }
        }

        return $result;
    }

    /**
     * Get theme forms
     */
    public function getThemeForms($theme)
    {
        // Forms are now stored in a common location, not theme-specific
        $formsDir = dirname($this->pagesDir) . '/pages/forms';
        $forms    = [];

        if (is_dir($formsDir)) {
            $files = scandir($formsDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $formName = pathinfo($file, PATHINFO_FILENAME);
                    $forms[]  = $formName;
                }
            }
        }

        return $forms;
    }

    /**
     * Handle logo file upload
     */
    public function handleLogoUpload($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Logo upload error: ' . $file['error']);
        }

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/webp'];
        if (! in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid logo file type. Only PNG, JPG, SVG, and WebP are allowed.');
        }

        // Validate file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Logo file size must be less than 10MB');
        }

        // Create uploads directory if it doesn't exist
        $uploadsDir = dirname(dirname(dirname(__DIR__))) . '/uploads/images';
        if (! is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Generate unique filename with timestamp
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = 'logo_' . time() . '.' . $extension;
        $filepath  = $uploadsDir . '/' . $filename;

        // Move uploaded file
        if (! move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save logo file');
        }

        // Save logo filename to config.json
        $this->saveLogoToConfig($filename);

        return $filename;
    }

    /**
     * Get current logo information
     */
    public function getCurrentLogo()
    {
        $uploadsDir = dirname(dirname(dirname(__DIR__))) . '/uploads/images';
        $configFile = dirname(dirname(dirname(__DIR__))) . '/config/config.json';

        // First try to get logo from config.json
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['site']['logo']) && ! empty($config['site']['logo'])) {
                $filename = $config['site']['logo'];
                $filepath = $uploadsDir . '/' . $filename;

                if (file_exists($filepath)) {
                    return [
                        'filename'    => $filename,
                        'logo_url'    => 'uploads/images/' . $filename,
                        'upload_time' => filemtime($filepath),
                    ];
                }
            }
        }

        // Fallback: Look for logo files (most recent first)
        if (! is_dir($uploadsDir)) {
            return null;
        }

        $logoFiles = glob($uploadsDir . '/logo_*.*');

        if (empty($logoFiles)) {
            return null;
        }

        // Sort by modification time (newest first)
        usort($logoFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latestLogo = $logoFiles[0];
        $filename   = basename($latestLogo);

        return [
            'filename'    => $filename,
            'logo_url'    => 'uploads/images/' . $filename,
            'upload_time' => filemtime($latestLogo),
        ];
    }

    /**
     * Save logo filename to config.json
     */
    private function saveLogoToConfig($filename)
    {
        $configFile = dirname(dirname(dirname(__DIR__))) . '/config/config.json';

        // Ensure directory exists
        $configDir = dirname($configFile);
        if (! is_dir($configDir)) {
            @mkdir($configDir, 0755, true);
        }

        // Read existing config or create a fresh structure if missing/invalid
        $config = [];
        if (file_exists($configFile)) {
            $raw     = file_get_contents($configFile);
            $decoded = @json_decode($raw, true);
            if (is_array($decoded)) {
                $config = $decoded;
            } else {
                // Backup invalid config
                @copy($configFile, $configFile . '.bak.' . time());
            }
        }

        // Ensure site block exists
        if (! isset($config['site']) || ! is_array($config['site'])) {
            $config['site'] = [];
        }

        // Save logo filename (just the basename) in site.logo
        $config['site']['logo'] = $filename;

        // Write back to file
        $written = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $written !== false;
    }

    /**
     * Get other contents (issues and endorsements)
     */
    public function getOtherContents($type)
    {
        if ($type === 'sliders') {
            // Handle sliders differently - they are in theme layouts
            return $this->getSliderContents();
        }

        if ($type === 'forms') {
            // Handle forms differently - they are in pages/forms directory
            return $this->getFormContents();
        }

        $cptDir   = dirname($this->pagesDir) . '/pages/cpt';
        $filePath = $cptDir . '/' . $type . '.json';

        if (! file_exists($filePath)) {
            return [];
        }

        $jsonContent = file_get_contents($filePath);
        $contents    = json_decode($jsonContent, true);

        return $contents ?: [];
    }

    /**
     * Get form contents from pages/forms directory
     */
    private function getFormContents()
    {
        $forms    = [];
        $formsDir = dirname($this->pagesDir) . '/pages/forms';

        if (! is_dir($formsDir)) {
            return [];
        }

        $formFiles = glob($formsDir . '/*.json');
        foreach ($formFiles as $formFile) {
            $formName    = basename($formFile, '.json');
            $formContent = file_get_contents($formFile);
            $formData    = json_decode($formContent, true);

            if ($formData) {
                $forms[] = [
                    'id'   => $formName,
                    'name' => $formName,
                    'json' => $formData,
                ];
            }
        }

        return $forms;
    }

    /**
     * Get slider contents from common slides directory (theme-agnostic)
     */
    private function getSliderContents()
    {
        $sliders = [];
        // Read from common slides directory instead of theme-specific directory
        $slidesDir = $this->pagesDir . '/slides';

        if (! is_dir($slidesDir)) {
            return [];
        }

        $slideFiles = glob($slidesDir . '/slide-*.json');
        foreach ($slideFiles as $slideFile) {
            $slideName    = basename($slideFile, '.json');
            $slideContent = file_get_contents($slideFile);
            $slideData    = json_decode($slideContent, true);

            if ($slideData) {
                $slideData['name'] = $slideName;
                $slideData['file'] = $slideFile;
                $sliders[]         = $slideData;
            }
        }

        return $sliders;
    }

    /**
     * Save other contents (issues and endorsements)
     */
    public function saveOtherContents($type, $contents)
    {
        if ($type === 'sliders') {
            // Handle sliders differently - they are individual layout files
            return $this->saveSliderContents($contents);
        }

        if ($type === 'forms') {
            // Handle forms differently - they are individual form files
            return $this->saveFormContents($contents);
        }

        $cptDir = dirname($this->pagesDir) . '/pages/cpt';

        // Ensure directory exists
        if (! is_dir($cptDir)) {
            mkdir($cptDir, 0755, true);
        }

        $filePath = $cptDir . '/' . $type . '.json';

        // Validate content structure based on type
        if ($type === 'testimonials') {
            // Testimonials have a different structure
            foreach ($contents as $item) {
                if (! isset($item['client_name']) || ! isset($item['client_comment'])) {
                    throw new Exception("Invalid testimonial structure. Each item must have 'client_name' and 'client_comment' fields.");
                }
            }
        } else {
            // Standard structure for other content types
            foreach ($contents as $item) {
                if (! isset($item['title']) || ! isset($item['content'])) {
                    throw new Exception("Invalid content structure. Each item must have 'title' and 'content' fields.");
                }
            }
        }

        $jsonContent = json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($filePath, $jsonContent) === false) {
            throw new Exception("Failed to save {$type} content to file.");
        }

        return true;
    }

    /**
     * Save slider contents to individual slide files (common location for all themes)
     */
    private function saveSliderContents($sliders)
    {
        // Save slides to common location instead of theme-specific directory
        $slidesDir = $this->pagesDir . '/slides';

        // Ensure directory exists
        if (! is_dir($slidesDir)) {
            mkdir($slidesDir, 0755, true);
        }

        // Get list of existing slide files before we start
        $existingFiles = [];
        if (is_dir($slidesDir)) {
            $existingFiles = glob($slidesDir . '/slide-*.json');
        }

        // Track which slide files should exist after this save
        $expectedFiles = [];

        foreach ($sliders as $slider) {
            if (! isset($slider['name'])) {
                continue;
            }

            $slideName = $slider['name'];
            $slideFile = $slidesDir . '/' . $slideName . '.json';

            // Track this file as expected
            $expectedFiles[] = $slideFile;

            // Remove the name and file properties before saving
            $slideData = $slider;
            unset($slideData['name']);
            unset($slideData['file']);

            $jsonContent = json_encode($slideData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if (file_put_contents($slideFile, $jsonContent) === false) {
                throw new Exception("Failed to save slider {$slideName} to file.");
            }
        }

        // Delete any slide files that are no longer needed
        foreach ($existingFiles as $existingFile) {
            if (! in_array($existingFile, $expectedFiles)) {
                if (unlink($existingFile)) {
                    error_log("Deleted unused slide file: " . basename($existingFile));
                } else {
                    error_log("Failed to delete slide file: " . basename($existingFile));
                }
            }
        }

        return true;
    }

    /**
     * Save form contents to individual form files
     */
    private function saveFormContents($forms)
    {
        $formsDir = dirname($this->pagesDir) . '/pages/forms';

        // Ensure directory exists
        if (! is_dir($formsDir)) {
            mkdir($formsDir, 0755, true);
        }

        // Get list of existing form files
        $existingFiles = [];
        if (is_dir($formsDir)) {
            $existingFiles = glob($formsDir . '/*.json');
        }

        // Track which form files should exist after this save
        $expectedFiles = [];

        foreach ($forms as $form) {
            if (! isset($form['id']) || ! isset($form['json'])) {
                continue;
            }

            $formId   = $form['id'];
            $formFile = $formsDir . '/' . $formId . '.json';

            // Track this file as expected
            $expectedFiles[] = $formFile;

            // Save the form data (json property contains the actual Forminator structure)
            $jsonContent = json_encode($form['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if (file_put_contents($formFile, $jsonContent) === false) {
                throw new Exception("Failed to save form {$formId} to file.");
            }
        }

        // Delete any form files that are no longer needed
        foreach ($existingFiles as $existingFile) {
            if (! in_array($existingFile, $expectedFiles)) {
                if (unlink($existingFile)) {
                    error_log("Deleted unused form file: " . basename($existingFile));
                } else {
                    error_log("Failed to delete form file: " . basename($existingFile));
                }
            }
        }

        return true;
    }

    /**
     * Save full page data (new format with widgets, grids, etc.)
     */
    public function saveFullPageData($theme, $page, $data)
    {
        $pageFile = $this->pagesDir . '/themes/' . $theme . '/layouts/' . $page . '.json';

        if (! file_exists($pageFile)) {
            throw new Exception("Page file not found: {$pageFile}");
        }

        // Process any text content in widgets
        if (isset($data['widgets'])) {
            foreach ($data['widgets'] as $index => &$widget) {
                $class = $widget['panels_info']['class'] ?? '';

                if ($class === 'SiteOrigin_Widget_Editor_Widget' && isset($widget['text'])) {
                    $widget['text'] = $this->processTextContent($widget['text'], $widget);
                }

                // Process features widgets
                if ($class === 'SiteOrigin_Widget_Features_Widget' && isset($widget['features'])) {
                    foreach ($widget['features'] as &$feature) {
                        if (isset($feature['text'])) {
                            $feature['text'] = $this->processTextContent($feature['text'], $feature);
                        }
                    }
                }
            }
        }

        // Ensure the data structure is properly formatted
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($pageFile, $jsonContent) === false) {
            throw new Exception("Failed to save page data to file: {$pageFile}");
        }

        error_log("Successfully saved full page data to: {$pageFile}");
        return true;
    }
}
