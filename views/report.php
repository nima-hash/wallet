<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch transactions for the report
$transactions = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching transactions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Report Page</h1>

        <!-- Filters -->
        <form id="reportFilters" method="GET" action="report.php">
            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" name="startDate">

            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" name="endDate">

            <label for="searchAmount">Amount:</label>
            <input type="number" id="searchAmount" name="searchAmount" step="0.01">

            <label for="searchDescription">Description:</label>
            <input type="text" id="searchDescription" name="searchDescription">

            <label for="sortBy">Sort By:</label>
            <select id="sortBy" name="sortBy">
                <option value="amount">Amount</option>
                <option value="category">Category</option>
                <option value="wallet">Wallet</option>
                <option value="time">Time</option>
            </select>

            <button type="submit">Generate Report</button>
        </form>

        <!-- Report Table -->
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Wallet</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['timestamp']) ?></td>
                        <td><?= htmlspecialchars($transaction['amount']) ?></td>
                        <td><?= htmlspecialchars($transaction['category_id']) ?></td>
                        <td><?= htmlspecialchars($transaction['wallet_id']) ?></td>
                        <td><?= htmlspecialchars($transaction['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>