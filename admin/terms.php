<?php 

// terms_and_conditions.php - Displays dynamic Terms & Conditions content

// Includes required files
include 'topheader.php'; 
include 'sidenavbar.php'; 

require_once __DIR__ . '/lib_common.php'; 

// --- Dynamic Content Retrieval ---
// In a real application, the full T&C content would be loaded from a setting,
// typically stored as a block of text or Markdown.

$terms_title = get_setting('terms_title', 'Terms & Conditions of Service');
$terms_last_updated = get_setting('terms_last_updated', 'October 17, 2025');

// Mock structured terms content using the render_tpl function's placeholder style
// This simulates loading a structured block of content from the database.
$mock_terms_data = [
    [
        'title' => '1. Acceptance of Terms',
        'content' => 'By accessing or using this website, you agree to be bound by these **Terms and Conditions**. If you do not agree, you are prohibited from using this website or any of our associated services.',
        'color' => '#007bff'
    ],
    [
        'title' => '2. User Responsibilities',
        'content' => 'You agree to use the platform only for lawful and authorized purposes. You must not use the site to infringe on the rights of others, upload harmful content (viruses, malicious code), or perform any activity that disrupts the site\'s operations.',
        'color' => '#007bff'
    ],
    [
        'title' => '3. Intellectual Property',
        'content' => 'All content, including source code, graphics, logos, images, and documents, is the property of Samrat Construction and its licensors, protected by applicable copyright and trademark laws. **You may not reuse or distribute any content without our explicit written consent.**',
        'color' => '#007bff'
    ],
    [
        'title' => '4. Limitation of Liability',
        'content' => 'We strive for accuracy but do not guarantee that the website will be entirely error-free or uninterrupted. Samrat Construction will **not be liable** for any direct, indirect, or consequential damages resulting from the use or inability to use the site or its features.',
        'color' => '#007bff'
    ],
    [
        'title' => '5. Governing Law',
        'content' => 'These terms are governed by and interpreted in accordance with the laws of **[Bihar/India]**. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts in that region.',
        'color' => '#007bff'
    ],
    [
        'title' => '6. Contact Us',
        'content' => 'If you have any questions about these Terms and Conditions, please contact our support team:<br><strong>Email:</strong> <a href="mailto:support@abhay.com" class="text-decoration-none">support@abhay.com</a>',
        'color' => '#ffc107' // Highlight contact section
    ]
];

// In a real scenario, this would load from DB:
// $terms_content_raw = get_setting('terms_structured_content', json_encode($mock_terms_data));
// $terms_sections = json_decode($terms_content_raw, true);

$terms_sections = $mock_terms_data; // Using mock data for display
?>

<style>
    .terms-content-wrapper {
        padding-top: 25px;
        padding-bottom: 40px;
    }
    .terms-section {
        border-left: 4px solid var(--section-color, #007bff); /* Dynamic color border */
        padding-left: 15px;
        margin-bottom: 25px;
        transition: border-color 0.3s;
    }
    .terms-section:hover {
        border-left-width: 6px;
    }
    .card-body-custom {
        padding: 3rem !important;
    }
    h1.display-6 {
        font-size: 2.25rem;
        margin-bottom: 1.5rem;
    }
    h2 {
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
        color: #343a40;
    }
    p.lead-intro {
        font-size: 1.1rem;
    }
    p, .text-secondary {
        font-size: 1rem;
        line-height: 1.6;
    }
</style>

<div class="container-fluid terms-content-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body card-body-custom">
                    
                    <h1 class="text-primary mb-2 display-6 fw-bolder">
                        <i class="bi bi-file-earmark-lock-fill me-2"></i> <?= htmlspecialchars($terms_title) ?>
                    </h1>
                    <p class="lead text-muted border-bottom pb-3 mb-4 lead-intro">
                        Please read these terms carefully before using our services.
                    </p>
                    <p class="text-secondary small mb-4">
                        <strong>Last Updated:</strong> <?= htmlspecialchars($terms_last_updated) ?>
                    </p>

                    <!-- Dynamically Rendered Sections -->
                    <?php 
                    foreach ($terms_sections as $section) {
                        $title = htmlspecialchars($section['title']);
                        // Note: Using inline style for the border-left color from data
                        $style = "border-left: 4px solid " . htmlspecialchars($section['color']) . ";";
                        ?>
                        <section class="terms-section" style="<?= $style ?>">
                            <h2 class="text-dark fw-bold"><?= $title ?></h2>
                            <!-- Content is output raw, assuming it might contain permitted HTML (like <br> or <a>) -->
                            <p class="text-secondary"><?= $section['content'] ?></p>
                        </section>
                        <?php
                    }
                    ?>
                    
                    <div class="text-center mt-5">
                        <p class="text-muted small">By continuing to use our services, you acknowledge that you have read and understood these Terms and Conditions.</p>
                        <a href="dashboard.php" class="btn btn-primary btn-lg rounded-pill px-4 mt-3">
                            <i class="bi bi-house-fill me-2"></i> Go to Dashboard
                        </a>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Include footer (likely closes main containers and adds scripts/footer content)
include 'downfooter.php'; 
?>