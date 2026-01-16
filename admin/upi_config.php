<?php
// Central UPI configuration now stored in database (app_settings table).
// Legacy text files will be read once (if present) to seed DB.

// Ensure DB connection is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/database.php';
}

// Create settings table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS app_settings (
  `key` VARCHAR(64) PRIMARY KEY,
  `value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Defaults
$UPI_VPA     = 'yourvpa@upi';
$UPI_PAYEE   = 'Samrat Construction';
$UPI_MOBILE  = '';
$UPI_QR_PATH = '';

// Attempt to load from DB
$wantKeys = [ 'upi_vpa', 'upi_payee', 'upi_mobile', 'upi_qr_path' ];
$in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $wantKeys)) . "'";
if ($res = $conn->query("SELECT `key`,`value` FROM app_settings WHERE `key` IN ($in)")) {
    while ($row = $res->fetch_assoc()) {
        switch ($row['key']) {
            case 'upi_vpa':     if (trim($row['value']) !== '') $UPI_VPA = trim($row['value']); break;
            case 'upi_payee':   if (trim($row['value']) !== '') $UPI_PAYEE = trim($row['value']); break;
            case 'upi_mobile':  if (trim($row['value']) !== '') $UPI_MOBILE = trim($row['value']); break;
            case 'upi_qr_path': if (trim($row['value']) !== '') $UPI_QR_PATH = trim($row['value']); break;
        }
    }
}

// One-time migration from legacy text files if DB is empty for any key
$vpaFile    = __DIR__ . '/upi_vpa.txt';
$payeeFile  = __DIR__ . '/upi_payee.txt';
$mobileFile = __DIR__ . '/upi_mobile.txt';
$qrPathFile = __DIR__ . '/upi_qr.txt';

$migrate = [];
if ($UPI_VPA === 'yourvpa@upi' && file_exists($vpaFile)) {
    $val = trim(@file_get_contents($vpaFile));
    if ($val !== '') { $UPI_VPA = $val; $migrate['upi_vpa'] = $val; }
}
if ($UPI_PAYEE === 'Samrat Construction' && file_exists($payeeFile)) {
    $val = trim(@file_get_contents($payeeFile));
    if ($val !== '') { $UPI_PAYEE = $val; $migrate['upi_payee'] = $val; }
}
if ($UPI_MOBILE === '' && file_exists($mobileFile)) {
    $val = trim(@file_get_contents($mobileFile));
    if ($val !== '') { $UPI_MOBILE = $val; $migrate['upi_mobile'] = $val; }
}
if ($UPI_QR_PATH === '' && file_exists($qrPathFile)) {
    $val = trim(@file_get_contents($qrPathFile));
    if ($val !== '') { $UPI_QR_PATH = $val; $migrate['upi_qr_path'] = $val; }
}

if (!empty($migrate)) {
    // Upsert migrated values
    $stmt = $conn->prepare("INSERT INTO app_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    foreach ($migrate as $k=>$v) {
        $stmt->bind_param('ss', $k, $v);
        $stmt->execute();
    }
    $stmt->close();
}