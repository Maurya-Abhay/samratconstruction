<?php 

// privacy_policy.php - Displays dynamic Privacy Policy content

// Includes required files
include 'topheader.php'; 
include 'sidenavbar.php'; 

require_once __DIR__ . '/lib_common.php'; 

// --- Dynamic Content Retrieval ---
// Load default or saved policy details
$policy_title = get_setting('policy_title', 'Privacy Policy - Samrat Construction');
$policy_last_updated = get_setting('policy_last_updated', 'October 17, 2025');

// Mock structured policy content. This structure allows admins to easily update sections.
$mock_policy_data = [
    [
        'title' => '1. Information We Collect',
        'content' => 'We may collect personal information such as your name, email address, phone number, and other identification details when you interact with our website or services. For our **Workers and Staff**, this includes **Aadhaar, address, and facial biometrics** necessary for attendance tracking and payroll.',
        'color' => '#007bff'
    ],
    [
        'title' => '2. How We Use Your Information',
        'content' => 'Your data is used solely to deliver construction services, manage projects, process payroll, **mark attendance (using biometrics)**, improve user experience, and fulfill legal requirements. **We do not sell, rent, or trade your personal data.**',
        'color' => '#007bff'
    ],
    [
        'title' => '3. Data Security & Protection',
        'content' => 'We utilize robust security measures, including **data encryption**, secure server storage, and strict access control, to protect your personal and biometric data. Access to sensitive information is restricted to authorized personnel only.',
        'color' => '#007bff'
    ],
    [
        'title' => '4. Biometrics and Attendance Data',
        'content' => 'Facial biometrics are collected exclusively for secure and accurate **workforce attendance tracking**. This data is stored securely and is used only for verifying presence on site and calculating work hours. It is **not shared externally**.',
        'color' => '#28a745' // Highlight sensitive section
    ],
    [
        'title' => '5. Cookies & Tracking',
        'content' => 'Standard website cookies may be used to enhance your browsing experience and analyze site traffic anonymously. You can manage cookie settings via your browser.',
        'color' => '#007bff'
    ],
    [
        'title' => '6. Your Rights',
        'content' => 'You have the right to access, correct, or request the deletion of your personal data, subject to legal and contractual obligations. Please contact us to exercise these rights.',
        'color' => '#007bff'
    ],
    [
        'title' => '7. Third-Party Services',
        'content' => 'We may use essential third-party services (e.g., cloud hosting, payment gateways). Their usage is limited, and their respective privacy policies apply to their tools.',
        'color' => '#007bff'
    ],
    [
        'title' => '8. Contact Us',
        'content' => 'If you have questions or concerns about this Privacy Policy, please reach out:<br><strong>Email:</strong> <a href="mailto:support@abhay.com" class="text-decoration-none">support@abhay.com</a>',
        'color' => '#ffc107' // Highlight contact section
    ]
];

$policy_sections = $mock_policy_data; 
?>

<style>
    /* Styling copied and slightly adjusted from Terms & Conditions for consistency */
    .policy-container {
        padding-top: 25px;
        padding-bottom: 40px;
    }
    .policy-section {
        border-left: 4px solid var(--section-color, #007bff); /* Dynamic color border */
        padding-left: 15px;
        margin-bottom: 25px;
        transition: border-left-width 0.3s, border-color 0.3s;
    }
    .policy-section:hover {
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

<div class="container-fluid policy-container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body card-body-custom">
                    
                    <h1 class="text-primary mb-2 display-6 fw-bolder">
                        <i class="bi bi-shield-lock-fill me-2"></i> <?= htmlspecialchars($policy_title) ?>
                    </h1>
                    <p class="lead text-muted border-bottom pb-3 mb-4 lead-intro">
                        Samrat Construction - Your privacy is important to us.
                    </p>
                    <p class="text-secondary small mb-4">
                        <strong>Effective Date:</strong> <?= htmlspecialchars($policy_last_updated) ?>
                    </p>

                    <!-- Dynamically Rendered Sections -->
                    <?php 
                    foreach ($policy_sections as $section) {
                        $title = htmlspecialchars($section['title']);
                        // Use the color from the mock data for the border
                        $style = "border-left: 4px solid " . htmlspecialchars($section['color']) . ";";
                        ?>
                        <section class="policy-section" style="<?= $style ?>">
                            <h2 class="text-dark fw-bold"><?= $title ?></h2>
                            <!-- Content is output raw, allowing for safe HTML tags like <br> and <a> -->
                            <p class="text-secondary"><?= $section['content'] ?></p>
                        </section>
                        <?php
                    }
                    ?>
                    
                    <div class="text-center mt-5">
                        <p class="text-muted small">We are committed to protecting your privacy and handling your data transparently.</p>
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
// Close containers opened in topheader/sidenavbar and include footer
include 'downfooter.php'; 
?>