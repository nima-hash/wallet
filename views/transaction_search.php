<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: " . __DIR__ . "/../login.php");
    exit();
}

include __DIR__ . '/../config/database.php';

$limit = 10; // Transactions per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$query = "SELECT * FROM transactions WHERE user_id = :user_id";
$params = ['user_id' => $_SESSION['user_id']];

if ($search) {
    $query .= " AND (transaction_type LIKE :search OR payment_method LIKE :search)";
    $params['search'] = "%$search%";
}

if ($payment_method) {
    $query .= " AND payment_method = :payment_method";
    $params['payment_method'] = $payment_method;
}

if ($start_date && $end_date) {
    $query .= " AND transaction_date BETWEEN :start_date AND :end_date";
    $params['start_date'] = $start_date;
    $params['end_date'] = $end_date;
}

$query .= " ORDER BY transaction_date DESC LIMIT $limit OFFSET $offset";
$transaction = new DatabaseConnection;
$stmt = $transaction->prepareStatement($query);

$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total transactions count
$total_stmt = $transaction->prepareStatement("SELECT COUNT(*) FROM transactions WHERE user_id = :user_id");
$total_stmt->execute(['user_id' => $_SESSION['user_id']]);
$total_transactions = $total_stmt->fetchColumn();
$total_pages = ceil($total_transactions / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <base href="https://wallet.bithorizon.de/">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="public/style.css"> 

</head>
<body>
    <header>
        <h1>Transaction History</h1>
        <nav>
            <a href="views/dashboard.php">Dashboard</a>
            <a href="views/transaction_history.php" class="active">Transactions</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2>Recent Transactions</h2>

            <!-- Filters -->
            <form method="GET">
                <input type="text" name="search" placeholder="Search transactions..." value="<?= htmlspecialchars($search) ?>">
                <select name="payment_method">
                    <option value="">All Payment Methods</option>
                    <option value="PayPal" <?= $payment_method == "PayPal" ? 'selected' : '' ?>>PayPal</option>
                    <option value="Stripe" <?= $payment_method == "Stripe" ? 'selected' : '' ?>>Stripe</option>
                    <option value="Bank Transfer" <?= $payment_method == "Bank Transfer" ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="Bitcoin" <?= $payment_method == "Bitcoin" ? 'selected' : '' ?>>Bitcoin</option>
                </select>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                <button type="submit">Filter</button>
                <a href="views/transaction_history.php">Reset</a>
            </form>

            <!-- Export Buttons -->
            <div class="export-buttons">
                <a href="views/export_csv.php" class="btn">Export CSV</a>
                <a href="views/export_pdf.php" class="btn">Export PDF</a>
            </div>

            <!-- Transactions Table -->
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['id']) ?></td>
                            <td><?= htmlspecialchars($transaction['transaction_type']) ?></td>
                            <td>$<?= number_format($transaction['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($transaction['payment_method']) ?></td>
                            <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </main>
</body>
</html>
