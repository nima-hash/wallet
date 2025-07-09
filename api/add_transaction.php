<?php
// session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $category_id = $_POST['expenseCategory'];
    $wallet_id = $_POST['wallet'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    try {
        // Insert the transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, wallet_id, amount, description, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $category_id, $wallet_id, $amount, $description]);

        // Update the wallet balance
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $wallet_id]);

        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        die("Error adding transaction: " . $e->getMessage());
    }
}
?>