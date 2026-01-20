<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Preparation - Frontline Framework</title>

    <link rel="icon" href="/php/admin/assets/img/favicon.ico">
    <link rel="stylesheet" href="//fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="/php/admin/docs/style.css">
</head>
<body>
    <div class="docs-container">
        <!-- Header -->
        <header class="docs-header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="/php/admin/assets/img/logo.png" alt="Frontline Framework" class="logo">
                    <div>
                        <h1>Content Preparation</h1>
                        <p>Prepare demo content for initial site deployment</p>
                    </div>
                </div>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Documentation Home
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="docs-main">
            <div class="docs-content">
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <span>/</span>
                    <a href="index.php#configuration">Configuration</a>
                    <span>/</span>
                    <span>Content Preparation</span>
                </nav>

                <h1 class="page-title">Content Preparation</h1>
                <p class="page-subtitle">Prepare demo content (posts, news, testimonials, endorsements, issues) as JSON files for deployment.</p>

                <!-- Important Notice -->
                <section class="content-section">
                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Demo Content Purpose
                        </div>
                        <p>This content is for initial site setup only. After deployment, manage content through WordPress admin (wp-admin → Posts/Pages). Changes made here are only applied during redeployment.</p>
                    </div>
                </section>

                <!-- Content Types -->
                <section class="content-section">
                    <h2><i class="fas fa-file-alt"></i> Content Types</h2>

                    <div class="card">
                        <h3><i class="fas fa-blog"></i> Blog Posts (posts.json)</h3>
                        <p>Standard WordPress blog posts with title, content, excerpt, featured image, categories, tags.</p>
                        <p><strong>Use for:</strong> Campaign updates, policy announcements, general news</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-newspaper"></i> News Articles (news.json)</h3>
                        <p>Custom post type for news/press releases. Includes date, headline, body content, link to full article.</p>
                        <p><strong>Use for:</strong> Press releases, media coverage, official announcements</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-quote-left"></i> Testimonials (testimonials.json)</h3>
                        <p>Customer/supporter testimonials with name, photo, quote, title/organization.</p>
                        <p><strong>Use for:</strong> Supporter quotes, volunteer stories, community feedback</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-star"></i> Endorsements (endorsements.json)</h3>
                        <p>Political/organizational endorsements with endorser name, title, photo, statement.</p>
                        <p><strong>Use for:</strong> Official endorsements, organizational support, notable backers</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-list-check"></i> Issues (issues.json)</h3>
                        <p>Platform issues/positions with title, description, stance, supporting data.</p>
                        <p><strong>Use for:</strong> Campaign platform, policy positions, issue statements</p>
                    </div>
                </section>

                <!-- How Content is Imported -->
                <section class="content-section">
                    <h2><i class="fas fa-cogs"></i> How Content is Imported</h2>

                    <ol class="step-list">
                        <li>
                            <strong>Configure Content</strong>
                            <p>Use Content Manager to create/edit content. Saved to /pages/cpt/*.json</p>
                        </li>

                        <li>
                            <strong>Upload During Deployment</strong>
                            <p>deploy.sh uploads JSON files to /tmp/pages/cpt/ on Kinsta server</p>
                        </li>

                        <li>
                            <strong>Import by template.sh</strong>
                            <p>template.sh copies CPT files to active theme's demo-data directory using copy_custom_cpt_to_theme() function</p>
                        </li>

                        <li>
                            <strong>WordPress Creates Posts</strong>
                            <p>Theme's demo installation function reads JSON and creates WordPress posts using wp_insert_post()</p>
                        </li>
                    </ol>
                </section>

                <!-- Content Manager Interface -->
                <section class="content-section">
                    <h2><i class="fas fa-desktop"></i> Using the Content Manager</h2>

                    <h3>Creating New Content</h3>
                    <ol>
                        <li>Select content type (Posts, News, Testimonials, Endorsements, Issues)</li>
                        <li>Click "Add New" button</li>
                        <li>Fill in required fields (title, content, author, etc.)</li>
                        <li>Add optional fields (featured image, categories, tags)</li>
                        <li>Click "Save" to store as JSON</li>
                    </ol>

                    <h3>Editing Existing Content</h3>
                    <ol>
                        <li>Select content type</li>
                        <li>Click "Edit" on the content item</li>
                        <li>Update fields as needed</li>
                        <li>Click "Save Changes"</li>
                    </ol>

                    <h3>Deleting Content</h3>
                    <ol>
                        <li>Select content type</li>
                        <li>Click "Delete" on the content item</li>
                        <li>Confirm deletion</li>
                    </ol>
                </section>

                <!-- Content Fields -->
                <section class="content-section">
                    <h2><i class="fas fa-list"></i> Common Content Fields</h2>

                    <div class="card">
                        <h3>Universal Fields (All Content Types)</h3>
                        <ul>
                            <li><strong>Title:</strong> Main heading for the content</li>
                            <li><strong>Content/Description:</strong> Main body text</li>
                            <li><strong>Featured Image:</strong> Main image (URL or upload)</li>
                            <li><strong>Author:</strong> Content author name</li>
                            <li><strong>Date:</strong> Publication or event date</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Blog Post Specific</h3>
                        <ul>
                            <li><strong>Excerpt:</strong> Short summary (appears in listing pages)</li>
                            <li><strong>Categories:</strong> Organize posts by topic</li>
                            <li><strong>Tags:</strong> Keywords for searching/filtering</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Testimonial Specific</h3>
                        <ul>
                            <li><strong>Quote:</strong> The testimonial text</li>
                            <li><strong>Person Name:</strong> Who said it</li>
                            <li><strong>Title/Organization:</strong> Their role or affiliation</li>
                            <li><strong>Photo:</strong> Portrait of the person</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Endorsement Specific</h3>
                        <ul>
                            <li><strong>Endorser Name:</strong> Organization or person name</li>
                            <li><strong>Title/Position:</strong> Their official title</li>
                            <li><strong>Statement:</strong> Endorsement text</li>
                            <li><strong>Logo/Photo:</strong> Organization logo or person photo</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Issue Specific</h3>
                        <ul>
                            <li><strong>Issue Title:</strong> Name of the issue</li>
                            <li><strong>Description:</strong> Detailed position statement</li>
                            <li><strong>Stance:</strong> Your position (Support, Oppose, Reform, etc.)</li>
                            <li><strong>Supporting Data:</strong> Facts, statistics, sources</li>
                        </ul>
                    </div>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Content Best Practices</h2>

                    <h3>Keep It Minimal for Demo Content</h3>
                    <p>Only add 2-3 items per content type as demo/starter content. You can add more through WordPress admin after deployment.</p>

                    <h3>Use Placeholder Images</h3>
                    <p>If you don't have final images yet, use placeholder services like <a href="https://placeholder.com" target="_blank">placeholder.com</a> or <a href="https://picsum.photos" target="_blank">picsum.photos</a>.</p>

                    <h3>Write Clear, Concise Content</h3>
                    <p>Demo content should showcase layout and structure. You can refine messaging after deployment in WordPress admin.</p>

                    <h3>Test Content Display</h3>
                    <p>After deployment, check how content displays on your live site. Adjust formatting in WordPress admin if needed.</p>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="page-editor.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Page Layout Editor</span>
                    </a>
                    <a href="forms-manager.php" class="btn-nav next">
                        <span>Forms Configuration</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="docs-footer">
            <p>&copy; <?php echo date('Y'); ?> Frontline Framework. Documentation for version 2.0</p>
        </footer>
    </div>
</body>
</html>
