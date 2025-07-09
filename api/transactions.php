<?php
header("Content-Type: application/json");
include '../config.php';

try {
    $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_DATABASE_NAME;charset=utf8mb4", $DB_USERNAME, $DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM transactions ORDER BY transaction_date DESC");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($transactions);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
