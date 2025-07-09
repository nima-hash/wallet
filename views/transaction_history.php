<?php 
include '../config/database.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: " . PROJECT_ROOT_PATH . "/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - My Bank</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="../index.php">Home</a>
        <a href="../api/transactions.php">Transactions</a>
        <a href="../transfer.php">Transfer</a>
        <a href="../contact.php">Contact</a>
    </div>

    <!-- Transactions Section -->
    <div class="container">
        <h2 class="section-title">Transaction History</h2>

        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="transaction-list">
                <!-- Transactions will be loaded dynamically here -->
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2025 My Bank. All Rights Reserved.
    </div>

    <script src="transactions.js"></script>

</body>
</html>
