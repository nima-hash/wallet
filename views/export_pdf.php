<?php
session_start();
require '../vendor/autoload.php'; // Install mpdf with composer: composer require mpdf/mpdf
include '../config/database.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>Transaction History</h1>');

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '<table border="1" cellpadding="5"><tr><th>ID</th><th>Type</th><th>Amount</th><th>Payment</th><th>Date</th></tr>';
foreach ($transactions as $row) {
    $html .= "<tr>
                <td>{$row['id']}</td>
                <td>{$row['transaction_type']}</td>
                <td>\${$row['amount']}</td>
                <td>{$row['payment_method']}</td>
                <td>{$row['transaction_date']}</td>
              </tr>";
}
$html .= '</table>';

$mpdf->WriteHTML($html);
$mpdf->Output('transactions.pdf', 'D');
exit();
?>
