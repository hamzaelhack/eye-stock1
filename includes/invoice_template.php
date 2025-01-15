<?php
require_once '../config/config.php';
require_once '../config/database.php';

class InvoiceTemplate {
    private $db;
    private $ownerInfo;
    private static $units = [
        0 => '',
        1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre', 5 => 'cinq',
        6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf', 10 => 'dix',
        11 => 'onze', 12 => 'douze', 13 => 'treize', 14 => 'quatorze',
        15 => 'quinze', 16 => 'seize', 17 => 'dix-sept', 18 => 'dix-huit',
        19 => 'dix-neuf'
    ];
    
    private static $tens = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante',
        6 => 'soixante', 7 => 'soixante-dix', 8 => 'quatre-vingt',
        9 => 'quatre-vingt-dix'
    ];

    public function __construct() {
        $this->db = new Database();
        $conn = $this->db->getConnection();
        
        // Get owner info
        $stmt = $conn->query("SELECT * FROM owner_info LIMIT 1");
        $this->ownerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function numberToWords($number) {
        $number = number_format($number, 2, '.', '');
        list($integer, $decimal) = explode('.', $number);
        
        $output = $this->convertToWords((int)$integer);
        
        if ($decimal > 0) {
            $output .= ' dinars et ' . $this->convertToWords((int)$decimal) . ' centimes';
        } else {
            $output .= ' dinars';
        }
        
        return ucfirst($output);
    }

    private function convertToWords($number) {
        if ($number < 20) {
            return self::$units[$number];
        }
        
        if ($number < 100) {
            $ten = floor($number / 10);
            $unit = $number % 10;
            
            $output = self::$tens[$ten];
            if ($unit > 0) {
                $output .= '-' . self::$units[$unit];
            }
            return $output;
        }
        
        if ($number < 1000) {
            $hundred = floor($number / 100);
            $remainder = $number % 100;
            
            $output = self::$units[$hundred] . ' cent';
            if ($remainder > 0) {
                $output .= ' ' . $this->convertToWords($remainder);
            }
            return $output;
        }
        
        if ($number < 1000000) {
            $thousand = floor($number / 1000);
            $remainder = $number % 1000;
            
            $output = $this->convertToWords($thousand) . ' mille';
            if ($remainder > 0) {
                $output .= ' ' . $this->convertToWords($remainder);
            }
            return $output;
        }
        
        return 'nombre trop grand';
    }

    public function generateInvoice($data) {
        $invoiceNumber = 'FA' . date('Y') . 
                        str_pad($data['distributor']['id'], 4, '0', STR_PAD_LEFT) . 
                        strtoupper(substr(uniqid(), -5));
        
        $totalHT = 0;
        foreach ($data['requests'] as $request) {
            $totalHT += $request['quantity'] * $request['sell_price'];
        }
        $totalTVA = $totalHT * 0.19;
        $totalTTC = $totalHT + $totalTVA;

        $amountInWords = $this->numberToWords($totalTTC);

        $html = '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    size: A4;
                    margin: 0;
                }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 2cm;
                    font-size: 12pt;
                }
                .invoice-container {
                    max-width: 21cm;
                    min-height: 29.7cm;
                    margin: 0 auto;
                    background: white;
                    position: relative;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 20px;
                }
                .company-info {
                    margin-bottom: 20px;
                    font-size: 11pt;
                }
                .invoice-title {
                    font-size: 24pt;
                    font-weight: bold;
                    margin: 20px 0;
                    text-decoration: underline;
                }
                .invoice-details {
                    margin: 20px 0;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border: 1px solid #000;
                }
                .client-info {
                    margin: 20px 0;
                    padding: 15px;
                    border: 1px solid #000;
                    background-color: #f8f9fa;
                }
                .invoice-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .invoice-table th,
                .invoice-table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                }
                .invoice-table th {
                    background-color: #f8f9fa;
                }
                .amount-section {
                    margin: 20px 0;
                    border: 1px solid #000;
                    padding: 15px;
                    background-color: #f8f9fa;
                }
                .amount-in-words {
                    font-style: italic;
                    margin: 10px 0;
                    font-size: 11pt;
                }
                .totals {
                    margin-left: auto;
                    width: 50%;
                }
                .totals table {
                    width: 100%;
                    margin-top: 20px;
                }
                .totals td {
                    padding: 5px;
                }
                .footer {
                    margin-top: 50px;
                    page-break-inside: avoid;
                }
                .signature-section {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 40px;
                }
                .payment-info {
                    margin: 20px 0;
                    padding: 10px;
                    border: 1px solid #000;
                }
                .legal-mention {
                    font-size: 8pt;
                    text-align: center;
                    margin-top: 30px;
                    font-style: italic;
                }
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <div class="header">
                    <div class="company-info">
                        <h1 style="margin:0;font-size:18pt;">' . htmlspecialchars($this->ownerInfo['company_name']) . '</h1>
                        <p style="margin:5px 0;">
                            ' . htmlspecialchars($this->ownerInfo['address']) . '<br>
                            Tél: ' . htmlspecialchars($this->ownerInfo['phone']) . '<br>
                            Email: ' . htmlspecialchars($this->ownerInfo['email']) . '
                        </p>
                    </div>
                    <div class="invoice-title">FACTURE</div>
                </div>

                <div class="invoice-details">
                    <table style="width:100%">
                        <tr>
                            <td style="width:50%">
                                <strong>N° Facture:</strong> ' . htmlspecialchars($invoiceNumber) . '<br>
                                <strong>Date:</strong> ' . date('d/m/Y') . '
                            </td>
                            <td style="width:50%">
                                <strong>RC:</strong> ' . htmlspecialchars($this->ownerInfo['rc']) . '<br>
                                <strong>NIF:</strong> ' . htmlspecialchars($this->ownerInfo['nif']) . '<br>
                                <strong>NIC:</strong> ' . htmlspecialchars($this->ownerInfo['nic']) . '<br>
                                <strong>Art.:</strong> ' . htmlspecialchars($this->ownerInfo['art']) . '
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="client-info">
                    <h3 style="margin-top:0">CLIENT</h3>
                    <strong>' . htmlspecialchars($data['distributor']['company']) . '</strong><br>
                    Adresse: ' . htmlspecialchars($data['distributor']['address']) . '<br>
                    NIF: ' . htmlspecialchars($data['distributor']['nif']) . '<br>
                    NIC: ' . htmlspecialchars($data['distributor']['nic']) . '<br>
                    Art.: ' . htmlspecialchars($data['distributor']['art']) . '
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th style="width:40%">Désignation</th>
                            <th style="width:15%" class="text-center">Quantité</th>
                            <th style="width:20%" class="text-end">Prix unitaire HT</th>
                            <th style="width:25%" class="text-end">Total HT</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['requests'] as $request) {
            $total = $request['quantity'] * $request['sell_price'];
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($request['product_name']) . '</td>
                            <td style="text-align:center">' . htmlspecialchars($request['quantity']) . '</td>
                            <td style="text-align:right">' . number_format($request['sell_price'], 2, ',', ' ') . ' DA</td>
                            <td style="text-align:right">' . number_format($total, 2, ',', ' ') . ' DA</td>
                        </tr>';
        }

        $html .= '
                    </tbody>
                </table>

                <div class="totals">
                    <table>
                        <tr>
                            <td style="text-align:right"><strong>Total HT:</strong></td>
                            <td style="text-align:right;width:150px">' . number_format($totalHT, 2, ',', ' ') . ' DA</td>
                        </tr>
                        <tr>
                            <td style="text-align:right"><strong>TVA (19%):</strong></td>
                            <td style="text-align:right">' . number_format($totalTVA, 2, ',', ' ') . ' DA</td>
                        </tr>
                        <tr style="font-size:14pt">
                            <td style="text-align:right"><strong>Total TTC:</strong></td>
                            <td style="text-align:right"><strong>' . number_format($totalTTC, 2, ',', ' ') . ' DA</strong></td>
                        </tr>
                    </table>
                </div>

                <div class="amount-section">
                    <strong>Arrêtée la présente facture à la somme de:</strong><br>
                    <div class="amount-in-words">
                        ' . $amountInWords . ' Dinars Algériens
                    </div>
                </div>

                <div class="payment-info">
                    <strong>Mode de règlement:</strong> _______________<br>
                    <strong>Date de règlement:</strong> _______________
                </div>

                <div class="footer">
                    <div class="signature-section">
                        <div>
                            <strong>Le Client</strong><br>
                            (Signature et cachet)
                        </div>
                        <div style="text-align:right">
                            <strong>Le Vendeur</strong><br>
                            (Signature et cachet)
                        </div>
                    </div>
                    
                    <div class="legal-mention">
                        ' . htmlspecialchars($this->ownerInfo['company_name']) . ' - ' . htmlspecialchars($this->ownerInfo['address']) . '<br>
                        RC: ' . htmlspecialchars($this->ownerInfo['rc']) . ' - NIF: ' . htmlspecialchars($this->ownerInfo['nif']) . ' - Art.: ' . htmlspecialchars($this->ownerInfo['art']) . '
                    </div>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }
}
?>
