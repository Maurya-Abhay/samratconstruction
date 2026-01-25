<?php
// documentation.php - Modern Documentation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Documentation | JP Construction</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fdfdfe;
            color: #333;
            line-height: 1.6;
        }

        /* Sidebar */
        .doc-sidebar {
            position: sticky;
            top: 2rem;
            height: calc(100vh - 4rem);
            overflow-y: auto;
            border-right: 1px solid #eee;
            padding-right: 2rem;
        }
        .doc-link {
            display: block;
            padding: 10px 15px;
            color: var(--secondary);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            font-weight: 500;
            transition: 0.2s;
        }
        .doc-link:hover, .doc-link.active {
            background: #eef2ff;
            color: var(--primary);
        }
        .doc-link i { margin-right: 10px; }

        /* Content Area */
        .doc-section {
            margin-bottom: 4rem;
            scroll-margin-top: 2rem;
        }
        .section-title {
            font-weight: 800;
            color: #111;
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 1rem;
            border-left: 5px solid var(--primary);
        }

        /* Feature Cards */
        .feature-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 1.5rem;
            transition: 0.3s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
            border-color: var(--primary);
        }
        .feature-icon {
            width: 40px; height: 40px;
            background: #eef2ff;
            color: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        /* Visual Flow Diagram CSS */
        .flow-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 0;
        }
        .flow-box {
            background: white;
            border: 2px solid var(--primary);
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            color: var(--primary);
            position: relative;
            box-shadow: 0 5px 15px rgba(78,115,223,0.1);
            text-align: center;
            min-width: 200px;
        }
        .flow-row {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        .flow-child {
            background: #fff;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 10px;
            width: 220px;
            text-align: center;
            position: relative;
        }
        .flow-child::before {
            content: '';
            position: absolute;
            top: -3rem; left: 50%;
            width: 2px; height: 3rem;
            background: #ddd;
            transform: translateX(-50%);
        }
        .flow-box::after {
            content: '';
            position: absolute;
            bottom: -2rem; left: 50%;
            width: 2px; height: 2rem;
            background: var(--primary);
            transform: translateX(-50%);
        }
        /* Connector Line */
        .flow-connector {
            height: 2px;
            background: #ddd;
            width: 75%; /* Width of connector spanning children */
            margin: 0 auto;
            position: relative;
            top: -1rem; /* Adjust to align with vertical lines */
        }

        /* Timeline Steps */
        .timeline-step {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .step-num {
            width: 35px; height: 35px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        @media (max-width: 991px) {
            .doc-sidebar { display: none; }
            .flow-row { flex-direction: column; align-items: center; gap: 1rem; margin-top: 1rem; }
            .flow-child::before { display: none; } /* Hide complex connectors on mobile */
            .flow-connector { display: none; }
            .flow-box::after { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-book-half me-2"></i> SamratDocs
        </a>
        <span class="text-white-50 small">v1.0</span>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation (Desktop) -->
        <div class="col-lg-3">
            <div class="doc-sidebar">
                <h6 class="text-uppercase text-muted fw-bold small mb-3 px-3">Guide</h6>
                <a href="#overview" class="doc-link active"><i class="bi bi-house"></i> Overview</a>
                <a href="#architecture" class="doc-link"><i class="bi bi-diagram-3"></i> Architecture</a>
                <a href="#features" class="doc-link"><i class="bi bi-grid"></i> Key Features</a>
                <a href="#installation" class="doc-link"><i class="bi bi-download"></i> Installation</a>
                <a href="#modules" class="doc-link"><i class="bi bi-collection"></i> Module Details</a>
                <a href="#security" class="doc-link"><i class="bi bi-shield-lock"></i> Security</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            
            <!-- 1. Overview -->
            <div id="overview" class="doc-section">
                <h1 class="display-5 fw-bold mb-3">Samrat Construction Software</h1>
                <p class="lead text-secondary">A comprehensive, secure, and modern ERP solution designed for construction workforce management, attendance tracking, and financial operations.</p>
                <div class="alert alert-light border-start border-4 border-primary p-3">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>
                    <strong>Goal:</strong> Simplify operations by digitizing worker management, payments, and client interactions in one unified platform.
                </div>
            </div>

            <!-- 2. Architecture Diagram -->
            <div id="architecture" class="doc-section">
                <h3 class="section-title">System Architecture</h3>
                <p class="mb-4">A modern overview of how all modules and services interact in the Samrat Construction ecosystem.</p>
                <div class="bg-light p-5 rounded-4 border">
                    <div class="d-flex flex-column align-items-center">
                        <div class="mb-4">
                            <span class="badge bg-primary px-3 py-2 fs-5"><i class="bi bi-hdd-network me-2"></i> Central Database</span>
                        </div>
                        <div class="row w-100 justify-content-center mb-4">
                            <div class="col-md-3 text-center">
                                <div class="card shadow-sm border-0 mb-3">
                                    <div class="card-body">
                                        <div class="mb-2"><i class="bi bi-shield-lock-fill text-primary fs-3"></i></div>
                                        <h6 class="fw-bold">Admin Panel</h6>
                                        <div class="small text-muted">User & System Management<br>Financial Approvals<br>Settings & Security</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card shadow-sm border-0 mb-3">
                                    <div class="card-body">
                                        <div class="mb-2"><i class="bi bi-person-badge-fill text-success fs-3"></i></div>
                                        <h6 class="fw-bold">Worker Portal</h6>
                                        <div class="small text-muted">Attendance & Leave<br>Profile & Salary<br>Notifications</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card shadow-sm border-0 mb-3">
                                    <div class="card-body">
                                        <div class="mb-2"><i class="bi bi-qr-code-scan text-warning fs-3"></i></div>
                                        <h6 class="fw-bold">Attendance System</h6>
                                        <div class="small text-muted">QR/Face Scan<br>Geo-tagging<br>Daily Logs</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card shadow-sm border-0 mb-3">
                                    <div class="card-body">
                                        <div class="mb-2"><i class="bi bi-building text-info fs-3"></i></div>
                                        <h6 class="fw-bold">Client Portal</h6>
                                        <div class="small text-muted">Project Status<br>UPI Payments<br>Support & Docs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="w-100 text-center mb-2">
                            <span class="badge bg-secondary px-3 py-2"><i class="bi bi-arrow-left-right"></i> Secure API & Service Layer</span>
                        </div>
                        <div class="row w-100 justify-content-center">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm mb-2">
                                    <div class="card-body text-center">
                                        <i class="bi bi-cloud-arrow-up-down text-dark fs-4"></i>
                                        <div class="fw-bold">Data Sync & Backup</div>
                                        <div class="small text-muted">Automated backups, real-time sync, and recovery tools</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm mb-2">
                                    <div class="card-body text-center">
                                        <i class="bi bi-shield-check text-success fs-4"></i>
                                        <div class="fw-bold">Security & Audit</div>
                                        <div class="small text-muted">Access control, audit logs, and compliance monitoring</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Key Features Grid -->
            <div id="features" class="doc-section">
                <h3 class="section-title">Core Features</h3>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                            <h5>Workforce Management</h5>
                            <p class="small text-muted mb-0">Add, edit, and manage worker profiles including Aadhaar, photos, and salary details.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-fingerprint"></i></div>
                            <h5>Smart Attendance</h5>
                            <p class="small text-muted mb-0">Mark attendance via QR codes or Face Recognition. Auto-calculates daily wages.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-cash-stack"></i></div>
                            <h5>Payment & Receipts</h5>
                            <p class="small text-muted mb-0">Track payments, generate professional PDF receipts, and manage UPI proofs.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                            <h5>Reporting System</h5>
                            <p class="small text-muted mb-0">Generate detailed PDF reports for worker history, payments, and attendance logs.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Installation Guide -->
            <div id="installation" class="doc-section">
                <h3 class="section-title">Installation & Setup</h3>
                <div class="card border-0 shadow-sm p-4">
                    
                    <div class="timeline-step">
                        <div class="step-num">1</div>
                        <div>
                            <h5>Server Requirements</h5>
                            <ul class="small text-muted mb-0">
                                <li>XAMPP / WAMP / LAMP Stack</li>
                                <li>PHP 7.4 or higher</li>
                                <li>MySQL / MariaDB</li>
                                <li>Enabled Extensions: <code>mysqli</code>, <code>json</code>, <code>openssl</code>, <code>gd</code></li>
                            </ul>
                        </div>
                    </div>

                    <div class="timeline-step">
                        <div class="step-num">2</div>
                        <div>
                            <h5>Deploy Files</h5>
                            <p class="small text-muted mb-0">Copy the <code>smrt</code> folder to your web root (e.g., <code>c:/xampp/htdocs/</code>). Ensure the <code>uploads/</code> folder has write permissions.</p>
                        </div>
                    </div>

                    <div class="timeline-step">
                        <div class="step-num">3</div>
                        <div>
                            <h5>Database Setup</h5>
                            <p class="small text-muted mb-0">Import the provided SQL file into phpMyAdmin. Configure connection details in <code>admin/database.php</code>.</p>
                        </div>
                    </div>

                    <div class="timeline-step">
                        <div class="step-num">4</div>
                        <div>
                            <h5>Launch</h5>
                            <p class="small text-muted mb-0">Navigate to <code>http://localhost/smrt/</code> or your live domain.</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 5. Modules -->
            <div id="modules" class="doc-section">
                <h3 class="section-title">Module Breakdown</h3>
                <div class="accordion" id="modulesAccordion">
                    <!-- Admin -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#modAdmin">
                                <i class="bi bi-shield-lock me-2"></i> Admin Panel (admin/)
                            </button>
                        </h2>
                        <div id="modAdmin" class="accordion-collapse collapse show" data-bs-parent="#modulesAccordion">
                            <div class="accordion-body text-muted small">
                                <b>Main Functions:</b> Add, edit, and remove users (workers, clients), set salary, upload Aadhaar/photo/face data.<br>
                                <b>Worker Onboarding:</b> Admin uses the <i>Add Worker</i> page to enter name, Aadhaar, salary, and upload photo/face data.<br>
                                <b>Payment Approval:</b> After a client uploads UPI payment proof, Admin reviews it in the <i>Payments</i> section and marks it as 'Approved'.<br>
                                <b>Dashboard:</b> View status of all modules, notifications, and quick actions.<br>
                                <b>Missing:</b> If a worker's Aadhaar or photo is missing, the system shows a warning.<br>
                                <b>Modern Features:</b> Audit logs, notifications, mobile-friendly UI, bulk import/export.<br>
                                <hr>
                                <b>Admin Settings Features (Full Explanation):</b><br>
                                <ul>
                                    <li><b>Profile Info:</b> Update your name, Aadhaar, date of birth, gender, city, state, address, and profile photo.</li>
                                    <li><b>Professional:</b> Set your designation, department, and joining date.</li>
                                    <li><b>Security & Preferences:</b> Change password, enable two-factor authentication (2FA), set max login attempts, enable dark mode, and email notifications.</li>
                                    <li><b>Advanced Settings:</b>
                                        <ul>
                                            <li>Password policy (minimum length, require special character)</li>
                                            <li>Session timeout for admin and other panels</li>
                                            <li>Maintenance mode (show custom message to users)</li>
                                            <li>Email/SMS notification settings</li>
                                            <li>Data export/import (CSV)</li>
                                            <li>Audit log viewer</li>
                                            <li>Theme/appearance selection (light/dark/custom)</li>
                                            <li>API key management</li>
                                            <li>IP whitelist/blacklist</li>
                                            <li>Custom dashboard widgets</li>
                                        </ul>
                                    </li>
                                    <li><b>Control Panels:</b> Set any panel (Worker, Attendance, Contact) to DOWN for maintenance, schedule downtime, and show custom messages.</li>
                                    <li><b>Session Management:</b> Set session timeouts, close all admin or panel sessions instantly.</li>
                                    <li><b>System Tools:</b> Download database backup, open backups tool, view audit logs, roles matrix, payslips, billing, aging report, bulk import/export, reminders, system health, help/docs.</li>
                                    <li><b>Emergency Lock:</b> Instantly lock the entire system, show a custom message, notify admin by email, view and export recent security alerts, block/unblock IPs.</li>
                                    <li><b>Reminders:</b> Configure email and WhatsApp reminder templates, set Brevo API key and sender, manage notification channels and templates for payment reminders.</li>
                                    <li><b>Localization:</b> Set currency symbol, date format, and number format (Indian/US).</li>
                                    <li><b>UPI Settings:</b> Set UPI VPA, payee name, mobile number, upload/replace QR image for payments.</li>
                                    <li><b>Delete Account:</b> Securely delete your admin account after confirmation.</li>
                                </ul>
                                <b>All features are designed for security, flexibility, and ease of use. Every setting is accessible from the Admin Settings panel, with instant feedback and help available.</b>
                            </div>
                        </div>
                    </div>
                    <!-- Worker -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#modWorker">
                                <i class="bi bi-helmet me-2"></i> Worker Portal (worker/)
                            </button>
                        </h2>
                        <div id="modWorker" class="accordion-collapse collapse" data-bs-parent="#modulesAccordion">
                            <div class="accordion-body text-muted small">
                                <b>Attendance Marking:</b> Worker को QR code <i>Dashboard</i> या <i>Attendance</i> पेज पर मिलेगा। QR कोड को मोबाइल से स्कैन करें या <i>Face Recognition</i> बटन दबाएं।<br>
                                <b>Face Recognition:</b> पहली बार फोटो/फेस डेटा अपलोड करना जरूरी है। कैमरा: कोई भी लैपटॉप/मोबाइल कैमरा (720p+ recommended)।<br>
                                <b>Leave Requests:</b> Worker <i>Leave</i> पेज पर जाकर छुट्टी के लिए request डाल सकता है।<br>
                                <b>Missing:</b> अगर QR कोड स्कैन नहीं हो रहा, तो कैमरा/ब्राउज़र परमिशन चेक करें।<br>
                                <b>Modern Features:</b> Mobile optimization, instant notifications, attendance analytics.<br>
                            </div>
                        </div>
                    </div>
                    <!-- Contact/Client -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#modContact">
                                <i class="bi bi-building me-2"></i> Client/Contact Portal (contact/)
                            </button>
                        </h2>
                        <div id="modContact" class="accordion-collapse collapse" data-bs-parent="#modulesAccordion">
                            <div class="accordion-body text-muted small">
                                <b>UPI Payment:</b> Client <i>Payments</i> पेज पर जाकर QR कोड स्कैन करता है, पेमेंट करता है, और UPI प्रूफ अपलोड करता है।<br>
                                <b>Project Status:</b> Client <i>Dashboard</i> पर प्रोजेक्ट की प्रगति देख सकता है।<br>
                                <b>Support:</b> किसी भी समस्या के लिए <i>Support/Contact</i> पेज से ticket raise कर सकता है।<br>
                                <b>Missing:</b> अगर UPI प्रूफ अपलोड नहीं हो रहा, तो फाइल टाइप/साइज चेक करें।<br>
                                <b>Modern Features:</b> Real-time payment status, support ticketing, document uploads.<br>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Usage Flows -->
                <div class="mt-5">
                    <h4 class="fw-bold mb-3">1. Detailed Usage Flows</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">
                            <b>Worker Onboarding (Admin):</b> <br>Admin opens the <i>Add Worker</i> page → enters name, Aadhaar, salary, photo/face data → clicks <i>Submit</i> → Worker is added to the system.
                        </li>
                        <li class="list-group-item">
                            <b>Attendance Marking (Worker):</b> <br>Worker opens the <i>Attendance</i> page → scans the QR code or clicks the <i>Face Recognition</i> button → Attendance is logged.
                        </li>
                        <li class="list-group-item">
                            <b>Payment Approval (Admin):</b> <br>Client uploads UPI proof on the <i>Payments</i> page → Admin reviews the proof in the <i>Payments</i> section → clicks <i>Approve</i> → Payment status changes to 'Approved'.
                        </li>
                    </ul>
                </div>

                <!-- Technology & Hardware Details -->
                <div class="mt-5">
                    <h4 class="fw-bold mb-3">2. Technology & Hardware Details</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">
                            <b>Attendance Setup:</b> Any smartphone or laptop camera can be used for QR code scanning. For Face Recognition, a 720p or better camera is recommended. No extra hardware is required.
                        </li>
                        <li class="list-group-item">
                            <b>PDF Generation:</b> Reports and receipts are generated using <b>FPDF</b> or <b>Dompdf</b> PHP libraries.
                        </li>
                        <li class="list-group-item">
                            <b>Mobile Optimization:</b> All pages are responsive and work smoothly on mobile and tablet devices.
                        </li>
                        <li class="list-group-item">
                            <b>Notifications:</b> Instant notifications are sent for attendance, payments, and approvals.
                        </li>
                    </ul>
                </div>

                <!-- Database Schema Summary -->
                <div class="mt-5">
                    <h4 class="fw-bold mb-3">3. Database Schema Summary</h4>
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr><th>Table</th><th>Fields</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><b>users</b></td>
                                <td>id, name, role, aadhaar, salary, password_hash, photo, face_data</td>
                                <td>Information for all users (admin, worker, client)</td>
                            </tr>
                            <tr>
                                <td><b>attendance_logs</b></td>
                                <td>id, worker_id, time_in, time_out, method (QR/Face), geo_location</td>
                                <td>Attendance history and marking details</td>
                            </tr>
                            <tr>
                                <td><b>payments</b></td>
                                <td>id, client_id, amount, status, upi_reference, proof_file, approved_by</td>
                                <td>Payments, UPI proof, approval status</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Expanded Troubleshooting -->
                <div class="mt-5">
                    <h4 class="fw-bold mb-3">4. Troubleshooting</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">
                            <b>Login Issue:</b> <br>If you see 'Invalid credentials', check your username and password.<br>If you see 'Account Locked due to Brute Force', wait 15 minutes and try again or contact the admin.
                        </li>
                        <li class="list-group-item">
                            <b>Upload Issue:</b> <br>If you see 'Max file size exceeded' or 'Invalid file type', check the file size (up to 2MB) and type (jpg, png, pdf).<br>Upload the correct file again.
                        </li>
                        <li class="list-group-item">
                            <b>Attendance Issue:</b> <br>If QR code is not scanning, allow camera permission or try a different browser.<br>If Face Recognition is not working, check your photo and lighting conditions.
                        </li>
                        <li class="list-group-item">
                            <b>Payment Issue:</b> <br>If UPI proof is not uploading, check the file type/size.<br>If payment status is 'Pending', contact the admin.
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 6. Security -->
            <div id="security" class="doc-section">
                <h3 class="section-title">Security Features</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light h-100">
                            <h6 class="fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Input Sanitization</h6>
                            <p class="small text-muted mb-0">All inputs are validated and sanitized to prevent XSS and Injection attacks.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light h-100">
                            <h6 class="fw-bold"><i class="bi bi-database-lock text-success me-2"></i>Prepared Statements</h6>
                            <p class="small text-muted mb-0">Database queries use prepared statements to block SQL Injection.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light h-100">
                            <h6 class="fw-bold"><i class="bi bi-key text-success me-2"></i>Access Control</h6>
                            <p class="small text-muted mb-0">Strict session checks on every page prevent unauthorized access.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0 small opacity-50">&copy; 2025 Samrat Construction. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>