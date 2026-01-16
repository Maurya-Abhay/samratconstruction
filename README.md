# Samrat Construction Management Software

## Features
- Admin, Worker, Attendance, and Contact management modules
- Secure login system with brute-force protection
- User management (add, edit, delete)
- Attendance marking and reporting
- Payment management and UPI integration
- PDF report generation
- Secure file uploads
- CSRF, SQL injection, XSS protection
- Audit logging for login attempts

## Requirements
- Windows OS (recommended)
- XAMPP (Apache, MySQL, PHP >= 7.4)
- PHP extensions: mysqli, json, openssl
- Browser: Chrome, Edge, Firefox

## Installation
1. Install XAMPP and start Apache & MySQL.
2. Copy the `smrt` folder to `c:/xampp/htdocs/`.
3. Import the database SQL file (if provided) into phpMyAdmin.
4. Update database credentials in `admin/database.php`.
5. Set folder permissions for `uploads/` and `admin/uploads/`.
6. Open browser and go to `http://localhost/smrt/`.

## Usage
- Admin login: `http://localhost/smrt/admin/`
- Worker login: `http://localhost/smrt/worker/`
- Attendance: `http://localhost/smrt/attendence/`
- Contact/Client: `http://localhost/smrt/contact/`
- Use dashboard for all management features.

## Security
- All modules protected against common web attacks.
- Sensitive files and directories are blocked from direct access.
- Rate limiting and audit logs for login attempts.

---
For any issues, contact your IT administrator.
