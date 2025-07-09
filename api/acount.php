<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Wallet</title>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <style>
        .modal { display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; padding: 20px; margin: 10% auto; width: 80%; border-radius: 10px; }
        .close { float: right; cursor: pointer; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
        .filter-container { margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 10px; }
        input, select, button { padding: 5px; }
        button { cursor: pointer; }
    </style>
</head>
<body>

<div id="editWalletModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        <h2>Manage Wallet</h2>

        <!-- Filters -->
        <div class="filter-container">
            <input type="date" id="startDate" placeholder="Start Date">
            <input type="date" id="endDate" placeholder="End Date">
            <input type="text" id="searchDescription" placeholder="Search by description">
            <input type="number" id="minAmount" placeholder="Min Amount">
            <input type="number" id="maxAmount" placeholder="Max Amount">
            <select id="filterPaymentType">
                <option value="">Payment Type</option>
                <option value="Cash">Cash</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>
            <select id="filterExpenseCategory">
                <option value="">Expense Category</option>
                <option value="Food">Food</option>
                <option value="Rent">Rent</option>
                <option value="Entertainment">Entertainment</option>
            </select>
            <select id="filterDeposit">
                <option value="">Deposit Type</option>
                <option value="Deposit">Deposit</option>
                <option value="Withdrawl">Withdrawl</option>
            </select>
            <button onclick="fetchTransactions()">Refresh Data</button>
        </div>

        <!-- Transactions Table -->
        <h2>Manage Transaction</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Payment Type</th>
                    <th>Expense Category</th>
                    <th>Remaining Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="editWalletTransactionTable"></tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", fetchTransactions);

    // Fetch updated transactions from the server
    async function fetchTransactions() {
        try {
            const response = await fetch("your-api-endpoint-here"); // Replace with actual API URL
            const transactions = await response.json();
            populateTransactionTable(transactions);
        } catch (error) {
            console.error("Error fetching transactions:", error);
        }
    }

    // Populate transaction table
    function populateTransactionTable(transactions) {
        const tbody = document.getElementById("editWalletTransactionTable");
        tbody.innerHTML = "";

        let balance = 0;

        transactions.forEach(transaction => {
            balance += transaction.transaction_type === "Deposit" ? transaction.amount : -transaction.amount;

            const tr = document.createElement("tr");
            tr.dataset.walletid = transaction.wallet_id;
            tr.dataset.expenseid = transaction.expense_id;

            tr.innerHTML = `
                <td>${transaction.transaction_date}</td>
                <td>${transaction.description || ''}</td>
                <td>${transaction.amount.toFixed(2)}</td>
                <td>${transaction.transaction_type}</td>
                <td>${transaction.payment_method || 'N/A'}</td>
                <td>${transaction.expense_category || 'N/A'}</td>
                <td>${balance.toFixed(2)}</td>
                <td>
                    <button class="editTransactionBtn" data-id="${transaction.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="deleteTransactionBtn" data-id="${transaction.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="moreInfoTransactionBtn" data-id="${transaction.id}">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        filterTransactions(); // Apply filter after data is loaded
    }

    // Filter function
    function filterTransactions() {
        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;
        const searchDescription = document.getElementById("searchDescription").value.toLowerCase();
        const minAmount = document.getElementById("minAmount").value;
        const maxAmount = document.getElementById("maxAmount").value;
        const filterPaymentType = document.getElementById("filterPaymentType").value;
        const filterExpenseCategory = document.getElementById("filterExpenseCategory").value;
        const filterDeposit = document.getElementById("filterDeposit").value;

        const rows = document.querySelectorAll("#editWalletTransactionTable tr");

        rows.forEach(row => {
            const date = row.children[0].textContent;
            const description = row.children[1].textContent.toLowerCase();
            const amount = parseFloat(row.children[2].textContent);
            const type = row.children[3].textContent;
            const paymentType = row.children[4].textContent;
            const expenseCategory = row.children[5].textContent;

            const matchesDate = (!startDate || date >= startDate) && (!endDate || date <= endDate);
            const matchesSearch = description.includes(searchDescription);
            const matchesAmount = (!minAmount || amount >= minAmount) && (!maxAmount || amount <= maxAmount);
            const matchesPaymentType = !filterPaymentType || paymentType === filterPaymentType;
            const matchesExpenseCategory = !filterExpenseCategory || expenseCategory === filterExpenseCategory;
            const matchesDeposit = !filterDeposit || type === filterDeposit;

            const shouldShow = matchesDate && matchesSearch && matchesAmount && matchesPaymentType && matchesExpenseCategory && matchesDeposit;

            row.style.display = shouldShow ? "" : "none";
        });
    }

    // Close modal function
    function closeModal() {
        document.getElementById("editWalletModal").style.display = "none";
    }

    // Attach filter event listeners
    document.querySelectorAll(".filter-container input, .filter-container select").forEach(input => {
        input.addEventListener("change", filterTransactions);
    });

</script>

</body>
</html>
