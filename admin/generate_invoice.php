<?php
require_once '../config/config.php';
require_once '../includes/invoice_template.php';

// Check if the request is POST and contains JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
        $invoice = new InvoiceTemplate();
        echo $invoice->generateInvoice($data);
    } else {
        http_response_code(400);
        echo "Invalid data format";
    }
} else {
    http_response_code(405);
    echo "Method not allowed";
}
?>
