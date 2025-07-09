<?php
require_once __DIR__ . "/../config/constant.php";
include __DIR__ . '/../config/database.php'; // Include your database connection file

// $categoryNameErr = $categoryBalanceErr = $categoryCurrencyErr = $walletNameErr = $walletBalanceErr =$walletCurrencyErr = '';
// Fetch expense categories and wallets from the database
$user_id = $_SESSION['user_id'];
try {
    $walletsObject = new Wallet;
    $expenseCategoryObject = new ExpenseCategory;

    $expenseCategories = $expenseCategoryObject->getCategories();
    $wallets= $walletsObject->getWallets();
    
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Page</title>
    <base href="https://wallet.bithorizon.de/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="public/transaction_styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>Transaction Page</h1>

        <!-- Transaction Form -->
        <form id="transactionForm" action="api/add_transaction.php" method="POST">
            <label for="expense_id">Expense Category:
                <i class="fas fa-cog manage-icon" id="manageCategoriesBtn"></i>
            </label>
            <select id="expense_id" name="expense_id" required>
                <option value="">Select Category</option>
                <?php foreach ($expenseCategories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['expense_name']) ?> (<?= htmlspecialchars($category['currency']) ?>)</option>
                <?php endforeach; ?>
            </select>

            <label for="wallet_id">Wallet:
                <i class="fas fa-wallet manage-icon" id="manageWalletsBtn"></i>
            </label>
            <select id="wallet_id" name="wallet_id" required>
                <option value="">Select Wallet</option>
                <?php foreach ($wallets as $wallet): ?>
                    <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['wallet_name']) ?> (<?= htmlspecialchars($wallet['currency']) ?>)</option>
                <?php endforeach; ?>
            </select>

            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" required>

            <label for="payment_method">Payment Method:</label>
            <select id="payment_method" name="payment_method">
                <option value="">Select a Method</option>
                <option value="PayPal">PayPal</option>
                <option value="Stripe">Stripe</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Bitcoin">Bitcoin</option>
            </select>

            <label for="status">Transaction Status:</label>
            <select id="status" name="status">
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
                <option value="Failed">Failed</option>
            </select>

            <label for="transaction_date">Date:</label>
            <input type="date" id="transaction_date" name="transaction_date" required>

            <label for="description">Description:</label>
            <input type="text" id="description" name="description" >

            <button type="submit">Add Transaction</button>
        </form>   

        <!-- Category Modal -->
        <div id="categoryModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Manage Expense Categories</h2>
                <button id="openAddCategoryModal"><i class="fas fa-plus"></i> Add New Category</button>
                <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Spent</th>
                                <th>Currency</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="expenseManageTable">
                            <?php foreach ($expenseCategories as $category): ?>
                                <tr data-expenseid="<?= htmlspecialchars($category['id']) ?>">
                                    <td><?= htmlspecialchars($category['expense_name']) ?></td>
                                    <td><?= htmlspecialchars($category['spent_this_month']) ?></td>
                                    <td><?= htmlspecialchars($category['currency']) ?></td>
                                    <td><?= htmlspecialchars($category['created_at']) ?></td>
                                    <td>
                                        <button class="editCategoryBtn" data-id="<?= $category['id'] ?>"><i class="fas fa-edit"></i></button>
                                        <button class="deleteCategoryBtn" data-id="<?= $category['id'] ?>"><i class="fas fa-trash"></i></button>
                                        <button class="historyCategoryBtn" data-id="<?= $category['id'] ?>"><i class="fas fa-history"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
        </div>

         <!-- Wallet Modal -->
        <div id="walletModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Manage Wallets</h2>
                <button id="openAddWalletModal"><i class="fas fa-plus"></i> Add New Wallet</button>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Balance</th>
                            <th>Currency</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="walletManageTable" >
                        <?php foreach ($wallets as $wallet): ?>
                            <tr data-walletId="<?= htmlspecialchars($wallet['id']) ?>">
                                <td><?= htmlspecialchars($wallet['wallet_name']) ?></td>
                                <td><?= htmlspecialchars($wallet['balance']) ?></td>
                                <td><?= htmlspecialchars($wallet['currency']) ?></td>
                                <td><?= htmlspecialchars($wallet['created_at']) ?></td>
                                <td>
                                    <button class="editWalletBtn" data-id="<?= $wallet['id'] ?>"><i class="fas fa-edit"></i></button>
                                    <button class="deleteWalletBtn" data-id="<?= $wallet['id'] ?>"><i class="fas fa-trash"></i></button>
                                    <button class="historyWalletBtn" data-id="<?= $wallet['id'] ?>"><i class="fas fa-history"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add Category Modal -->
        <div id="addCategoryModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add New Expense Category</h2>
                <form id="addCategoryForm" >
                    <input type="text" id="newCategoryName" name="expense_name" placeholder="Category Name" required>
                    <input type="text" id="newCategoryDescription" name="description" placeholder="Description...">
                    <input type="text" id="newCategoryCurrency" name="currency" placeholder="Currency" required>
                    <input type="number" id="newCategorySpent" name="spent_this_month" placeholder="Already spent this month">
                    <button type="submit" id="saveCategoryBtn">Save</button>
                </form>
            </div>
        </div>

        <!-- Add Wallet Modal -->
        <div id="addWalletModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add New Wallet</h2>
                <form id="addWalletForm" id="newWalletForm">
                    <input type="text" id="newWalletName" name="wallet_name" placeholder="Wallet Name" required>
                    <input type="text" id="newWalletDescription" name="description" placeholder="Description...">
                    <input type="text" id="newWalletCurrency" name="currency" placeholder="Currency" required>
                    <input type="number" id="newWalletBalance" name="balance" placeholder="Balance">
                    <button type="submit" id="saveWalletBtn">Save</button>
                </form>
            </div>
        </div>

        


        <!-- Edit category modal -->
        <div id="editCategoryModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Manage Expense Category</h2>
                <!-- <button id="openAddWalletModal"><i class="fas fa-plus"></i> Add New Wallet</button> -->
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Balance</th>
                            <th>Currency</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="editCategoryTable">
                        
                    </tbody>
                </table>

                <h2>Manage Transaction</h2>

                <button id = "expenseFilterBtn" >Filters</button>

                <!-- Filters Section (Initially Hidden) -->
                <div class="filter-container" id="filterSection">
                    <!-- <form class="form-inline" id="expenseFilterForm">
                        <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                        <input type="date" class="form-control" id="endDate" placeholder="End Date">
                        <input type="text" class="form-control" id="searchDescription" placeholder="Search by description">
                        <input type="number" class="form-control" id="minAmount" placeholder="Min Amount">
                        <input type="number" class="form-control" id="maxAmount" placeholder="Max Amount">
                        <select class="form-control" id="filterPaymentType">
                            <option value="">Select a Method</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Stripe">Stripe</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Bitcoin">Bitcoin</option>
                        </select>
                        <select class="form-control" id="filterStatus">
                            <option value="">Select a Status</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                        </select>
                        <select class="form-control" id="filterExpenseCategory"></select>
                        <select class="form-control" id="filterDeposit">
                            <option value="">Deposit Type</option>
                            <option value="Deposit">Deposit</option>
                            <option value="Withdrawl">Withdrawl</option>
                        </select>
                        <button type="submit" class="btn btn-primary" id="expenseFilterFormBtn">Apply</button>
                    </form> -->
                </div>

                <!-- Transactions Table -->
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="editExpenseTransactionTable"></tbody>
                </table>
            </div>
        </div>

        <!-- Edit wallet modal -->
        <div id="editWalletModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Manage Wallet</h2>
                <!-- <button id="openAddWalletModal"><i class="fas fa-plus"></i> Add New Wallet</button> -->
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Balance</th>
                            <th>Currency</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="editWalletTable">
                        
                    </tbody>
                </table>

                <h2>Manage Transaction</h2>
                <button id = "walletFilterBtn" >Filters</button>

                <!-- Filters Section (Initially Hidden) -->
                <div class="filter-container" id="walletFilterSection">
                    <!-- <form class="form-inline" id="expenseFilterForm">
                        <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                        <input type="date" class="form-control" id="endDate" placeholder="End Date">
                        <input type="text" class="form-control" id="searchDescription" placeholder="Search by description">
                        <input type="number" class="form-control" id="minAmount" placeholder="Min Amount">
                        <input type="number" class="form-control" id="maxAmount" placeholder="Max Amount">
                        <select class="form-control" id="filterPaymentType">
                            <option value="">Select a Method</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Stripe">Stripe</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Bitcoin">Bitcoin</option>
                        </select>
                        <select class="form-control" id="filterStatus">
                            <option value="">Select a Status</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                        </select>
                        <select class="form-control" id="filterExpenseCategory"></select>
                        <select class="form-control" id="filterDeposit">
                            <option value="">Deposit Type</option>
                            <option value="Deposit">Deposit</option>
                            <option value="Withdrawl">Withdrawl</option>
                        </select>
                        <button type="submit" class="btn btn-primary" id="expenseFilterFormBtn">Apply</button>
                    </form> -->
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="editWalletTransactionTable">
                        
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit transaction modal -->
        <div id="editTransactionModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Manage Transaction</h2>
                <!-- <button id="openAddTransactionModal"><i class="fas fa-plus"></i> Add New Transaction</button> -->
                <div id="transactionUpdateDiv">
                            
                </div> 
    
                <!-- <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Wallet</th>
                            <th>Expense</th>
                            <th>Description</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>T-Type</th>
                        </tr>
                    </thead>
                    <tbody id="editTransactionTable">
                        
                    </tbody>
                </table> -->

                <!-- <h2>Manage Transaction</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="editTransactionTransactionTable">
                        
                    </tbody>
                </table> -->
            </div>
        </div>

    </div>

    <script src="functions.js"></script>
    <script src="public/transaction_form.js"></script>
</body>
</html>