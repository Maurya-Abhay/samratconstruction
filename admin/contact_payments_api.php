<?php

include "lib_common.php"; // Assumes $conn is the database connection object

$contact_id = intval($_GET['contact_id'] ?? 0); // Safely convert to integer
$html = '';

if ($contact_id > 0) {
    // ⚠️ Security FIX: Use a prepared statement to safely execute the query.

    $stmt = $conn->prepare("SELECT payment_date, amount, notes FROM contact_payments WHERE contact_id = ? ORDER BY payment_date DESC, id DESC");
    
    // Bind the integer parameter
    $stmt->bind_param("i", $contact_id); 
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $html .= '<table class="table table-sm table-bordered mb-3">';
        $html .= '<thead class="table-light"><tr><th>Date</th><th>Amount (₹)</th><th>Notes</th></tr></thead><tbody>';
        
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            // Data is already being correctly sanitized for HTML output using htmlspecialchars()
            $html .= '<td>' . htmlspecialchars($row['payment_date']) . '</td>';
            $html .= '<td>₹' . number_format($row['amount'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['notes']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
    } else {
        $html .= '<div class="alert alert-secondary">No payments found.</div>';
    }
    
    $stmt->close();

} else {
    // Handle case where no valid ID is provided
    $html = '<div class="alert alert-danger">Invalid contact ID.</div>';
}

echo $html;

?>