<?php
// session_start();
include 'config/database.php';

$newUser = new User;



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Payment System</title>
    <base href="https://wallet.bithorizon.de/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="public/style.css">

</head>
<body>
<?php include __DIR__ . "/views/navbar.php"; ?>
    <div class="container mt-5">
        <div class="text-center">
            <h1>Welcome to BankPay</h1>
            <p>Secure and reliable payment transactions.</p>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow-lg p-4">
                    <h4>Make a Payment</h4>
                    <p>Send or refund payments securely.</p>
                    <a href="views/transaction_form.php" class="btn btn-primary">Proceed</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-lg p-4">
                    <h4>Transaction History</h4>
                    <p>View all past transactions.</p>
                    <a href="views/transaction_history.php" class="btn btn-secondary">View Transactions</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
