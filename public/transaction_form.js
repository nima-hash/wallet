document.addEventListener("DOMContentLoaded", function () {
    const categoryModal = document.getElementById("categoryModal");
    const walletModal = document.getElementById("walletModal");
    let expenseCategories =[]
    const token = sessionStorage.getItem("token");
    let wallets =[]

    

    //set default date to today
    document.getElementById("transaction_date").value = new Date().toISOString().split("T")[0];

    // Function to display error messages inside the form
    function displayErrorMessage(form, message) {
        let errorDiv = form.querySelector(".error-message");
        if (!errorDiv) {
            errorDiv = document.createElement("p");
            errorDiv.classList.add("error-message");
            errorDiv.style.color = "red";
            form.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }
    
    /**
     * Closes a modal by ID.
     * @param {string} modalId
     */
    function closeModal(modal) {
        modal.style.display = "none";
        const modalId = modal.getAttribute("id")
        refreshPreviousModal(modalId);
    }

    async function getExpense(expenseId) {
        const params = {
            "id": expenseId,
            "action": "getCategory"
        }
        const url = new URL("https://wallet.bithorizon.de/api/controller/ExpenseController.php")
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const response = await fetch (url, {
            method: "GET",
            headers: {
                "Authorization": "Bearer: " + token 
            }
        })

        if(!response.ok) {
            throw new Error("Could not get expense name", 400)
        }

        const result = await response.json()

        if (result.success) {
            return result.data
        }
    }

    async function getWallet(walletId) {
        const params = {
            "id": walletId,
            "action": "getWallet"
        }
        const url = new URL("https://wallet.bithorizon.de/api/controller/WalletController.php")
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const response = await fetch (url, {
            method: "GET",
            headers: {
                "Authorization": "Bearer: " + token 
            }
        })

        if(!response.ok) {
            throw new Error("Could not get wallet name", 400)
        }

        const result = await response.json()

        if (result.success) {
            return result.data
        }
    }

    function populateEditTransactionModal(transaction, wallets, expenses) {
        const transactionUpdateDiv = document.getElementById("transactionUpdateDiv")
        transactionUpdateDiv.innerHTML = ""
        // const tr = document.createElement("tr")
        transactionUpdateDiv.innerHTML=`
            <form id="transactionUpdateForm">
                <label for="update_expense_id">Expense Category:
                    <i class="fas fa-cog manage-icon" id="manageCategoriesBtn"></i>
                </label>
                <select id="update_expense_id" name="expense_id" required></select>

                <label for="update_wallet_id">Wallet:
                    <i class="fas fa-wallet manage-icon" id="manageWalletsBtn"></i>
                </label>
                <select id="update_wallet_id" name="wallet_id" required></select>

                <label for="update_amount">Amount:</label>
                <input type="number" id="update_amount" name="amount" step="0.01" required value="${transaction.amount}">

                <label for="update_payment_method">Payment Method:</label>
                <select id="update_payment_method" name="payment_method">
                    <option value="">Choose a payment method:</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Stripe">Stripe</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Bitcoin">Bitcoin</option>
                </select>

                <label for="update_status">Transaction Status:</label>
                <select id="update_status" name="status">
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                    <option value="Failed">Failed</option>
                </select>

                <label for="update_transaction_date">Date:</label>
                <input type="date" id="update_transaction_date" name="transaction_date" required>

                <label for="update_description">Description:</label>
                <input type="text" id="update_description" name="description" >


                <button id="submitTransactionUpdateBtn" class="btn btn-primary" type="submit">Apply</button>
                <button id="canselTransactionUpdateBtn" class="btn btn-secondary" type="button">Cancel</button>

            </form> 
            ` 
                const expenseSelectElement = document.getElementById("update_expense_id");
                const walletSelectElement = document.getElementById("update_wallet_id");
                const expense = expenses.find(item => item.id == transaction.expense_id)
                const wallet = wallets.find(item => item.id == transaction.wallet_id)
                console.log(wallet)

                // Populate expense select options dynamically
                expenses.forEach(category => {
                    const option = document.createElement("option");
                    option.value = category.id;
                    option.textContent = `${category.expense_name || ""} (${category.currency || ""})`;

                    // Set the selected attribute if it matches the current expense
                    if (category.expense_name === expense.expense_name) {
                        option.selected = true;
                    }

                    expenseSelectElement.appendChild(option);
                });

                // Populate wallet select options dynamically
                wallets.forEach(item => {
                    const option = document.createElement("option");
                    option.value = item.id;
                    option.textContent = `${item.wallet_name || ""} (${item.currency || ""})`;

                    // Set the selected attribute if it matches the current wallet
                    if (item.wallet_name === wallet.wallet_name) {
                        option.selected = true;
                    }

                    walletSelectElement.appendChild(option);
                });
                
                // Populate payment method select options dynamically
                const method = document.getElementById("update_payment_method")
                const methods = method.querySelectorAll("option")
                for (const item of methods) {
                    if(item.value === transaction.payment_method) {
                        item.selected = true
                    }
                }
                
                const status = document.getElementById("update_status")
                const statuses = status.querySelectorAll("option")
                for (const item of statuses) {
                    if(item.value === transaction.status) {
                        item.selected = true
                    }
                }
                
                //set default date to today
                const date = document.getElementById("update_transaction_date")
                date.value = new Date().toISOString().split("T")[0];

                const description = document.getElementById("update_description")
                description.value = transaction.description ?? null

                document.getElementById("transactionUpdateForm").addEventListener("submit", async function (event) {
                    event.preventDefault()
                    const form = event.target.closest("form")
                    const formData = sanitizeFormData(new FormData(form))
                    formData.append("action", "updateTransaction")
                    formData.append("id", transaction.id)
                    const url = new URL("https://wallet.bithorizon.de/api/controller/TransactionController.php")
                    applyUpdate(formData, url)
                    
                })
    }

    async function fetchExpenseCategories() {
        try {

            const url = new URL("https://wallet.bithorizon.de/api/controller/UserController.php");
                    const params = { action: "getExpenses"};
                    // Append parameters
                    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

            const response = await fetch (url, {
                method: 'GET',
                headers: {
                    'Authorization': "Bearer " + token
                }
            })
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: 'Could not get expenses from database.' ${response.status}`);
            }

            const result = await response.json() ?? null
            const expenseCategories = result.data ?? null
            return expenseCategories

        } catch (error) {
            console.error("Error fetching Expenses:", error.message)
        }
    }


    function populateManageWalletModal (wallets) {
        const manageTable = document.getElementById("walletManageTable")
        const walletSelect = document.getElementById("wallet_id");
        if (Array.isArray(wallets) && Object.keys(wallets).length > 0) {
            
            walletSelect.innerHTML = '<option value="">Select Wallet</option>';
            manageTable.innerHTML = "";
            wallets.forEach(wallet => {

                walletSelect.innerHTML += `<option value="${wallet.id}">${wallet.wallet_name || ""} (${wallet.currency || ""})</option>`;

                const tr = document.createElement("tr")
                tr.dataset.walletid = wallet.id
                tr.innerHTML = `
                    <td>${wallet.wallet_name}</td>
                    <td>${wallet.balance}</td>
                    <td>${wallet.currency}</td>
                    <td>${wallet.created_at}</td>
                    <td>
                        <button class="editWalletBtn" data-id=${wallet.id}><i class="fas fa-edit"></i></button>
                        <button class="deleteWalletBtn" data-id=${wallet.id}><i class="fas fa-trash"></i></button>
                        <button class="historyWalletBtn" data-id=${wallet.id}><i class="fas fa-history"></i></button>
                    </td>
                `   
                manageTable.append(tr)             
            });
        } else {
            walletSelect.innerHTML = `<option value="no Wallet">No Wallets were found</option>`;
            manageTable.innerHTML = `<tr>
            <td colspan="5">
                No Wallets were found.
                </td> 
            </tr>
            `
        }
    }

    function populateManageExpenseModal (expenses) {
        const manageTable = document.getElementById("expenseManageTable")
        const expenseSelect = document.getElementById("expense_id");

        if (Array.isArray(expenses) && Object.keys(expenses).length > 0) {
            
            expenseSelect.innerHTML = '<option value="">Select Category</option>';
            manageTable.innerHTML = "";
            expenses.forEach(expense => {

                expenseSelect.innerHTML += `<option value="${expense.id}">${expense.expense_name} (${expense.currency})</option>`;

                const tr = document.createElement("tr")
                tr.dataset.expenseid = expense.id
                tr.innerHTML = `
                    <td>${expense.expense_name || ""}</td>
                    <td>${expense.spent_this_month || ""}</td>
                    <td>${expense.currency || ""}</td>
                    <td>${expense.created_at || ""}</td>
                    <td>
                        <button class="editCategoryBtn" data-id=${expense.id}><i class="fas fa-edit"></i></button>
                        <button class="deleteCategoryBtn" data-id=${expense.id}><i class="fas fa-trash"></i></button>
                        <button class="historyCategoryBtn" data-id=${expense.id}><i class="fas fa-history"></i></button>
                    </td>
                `   
                manageTable.append(tr)             
            });
        } else {
            expenseSelect.innerHTML = `<option value="no expense">No Expenses were found</option>`;
            manageTable.innerHTML = `<tr>
            <td colspan="5">
                No Expenses were found.
                </td> 
            </tr>
            `
        }
    }

    function populateEditExpenseModal (expenseId) {
        populateExpenseTable(expenseId)
        populateExpenseTransactionTable(expenseId)
    }

    function populateUpdateExpenseModal (expenseId) {
        populateUpdateExpenseTable(expenseId)
        populateExpenseTransactionTable(expenseId)
    }

    function populateEditWalletModal (walletId) {
        populateWalletTable(walletId)
        populateWalletTransactionTable(walletId)
    }

    function populateUpdateWalletModal (walletId) {
        populateUpdateWalletTable(walletId)
        populateWalletTransactionTable(walletId)
    }

    async function populateUpdateExpenseTable(expenseId) {
        const expense = await getExpense(expenseId)

        const table = document.getElementById("editCategoryTable")
        table.innerHTML=""
        table.innerHTML = `
            <tr data-expenseid="${expense.id}">
                <td colspan="5">
                    <form id="expenseUpdateForm" class="form-inline">
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <input class="form-control" name="expense_name" type="text" value="${expense.expense_name}">
                                </td>
                                <td>
                                    <input class="form-control" name="spent_this_month" type="text" value="${expense.spent_this_month}">
                                </td>
                                <td>
                                    <input class="form-control" name="currency" type="text" value="${expense.currency}">                               
                                </td>
                                <td>
                                    ${expense.created_at}
                                </td>
                                <td>
                                    <button id="submitExpenseUpdateBtn" class="btn btn-primary" type="submit">Apply</button>
                                    <button id="canselExpenseUpdateBtn" class="btn btn-secondary" type="button">Cancel</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>    
        `
        const submitBtn = document.getElementById("submitExpenseUpdateBtn");
        if (submitBtn) {
            submitBtn.addEventListener("click", async function (event) {
                event.preventDefault()
                const expenseForm = document.getElementById("expenseUpdateForm")
                // event.preventDefault()
                const formData = new FormData(expenseForm);
                formData.append("id", expense.id)
                formData.append("action", "updateExpense")
                formData.append("created_at", expense.created_at)
                const expenseUpdateUrl = 'https://wallet.bithorizon.de/api/controller/ExpenseController.php'
                applyUpdate(formData, expenseUpdateUrl)
            })
        } else {
            throw new Error ("submitExpenseUpdateBtn not found!");
        }
        
        const cancelBtn = document.getElementById("canselExpenseUpdateBtn");
        if (cancelBtn) {
            cancelBtn.addEventListener("click", async function (event) {
                populateExpenseTable(expense.id)
            });
        } else {
            throw new Error ("submitExpenseUpdateBtn not found!");
        }
    }

    async function populateUpdateWalletTable(walletId) {
        const wallet = await getWallet(walletId)

        const table = document.getElementById("editWalletTable")
        table.innerHTML=""
        table.innerHTML = `
            <tr data-walletid="${wallet.id}">
                <td colspan="5">
                    <form id="walletUpdateForm" class="form-inline">
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <input class="form-control" name="wallet_name" type="text" value="${wallet.wallet_name}">
                                </td>
                                <td>
                                    <input class="form-control" name="balance" type="text" value="${wallet.balance}">
                                </td>
                                <td>
                                    <input class="form-control" name="currency" type="text" value="${wallet.currency}">                               
                                </td>
                                <td>
                                    ${wallet.created_at}
                                </td>
                                <td>
                                    <button id="submitWalletUpdateBtn" class="btn btn-primary" type="submit">Apply</button>
                                    <button id="canselWalletUpdateBtn" class="btn btn-secondary" type="button">Cancel</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>    
        `
        const submitBtn = document.getElementById("submitWalletUpdateBtn");
        if (submitBtn) {
            submitBtn.addEventListener("click", async function (event) {
                event.preventDefault()
                const walletForm = document.getElementById("walletUpdateForm")
                // event.preventDefault()
                const formData = new FormData(walletForm);
                formData.append("id", wallet.id)
                formData.append("action", "updateWallet")
                formData.append("created_at", wallet.created_at)
                const walletUpdateUrl = 'https://wallet.bithorizon.de/api/controller/WalletController.php'
                applyUpdate(formData, walletUpdateUrl)
            })
        } else {
            throw new Error ("submitWalletUpdateBtn not found!");
        }
        
        const cancelBtn = document.getElementById("canselWalletUpdateBtn");
        if (cancelBtn) {
            cancelBtn.addEventListener("click", async function (event) {
                populateWalletTable(wallet.id)
            });
        } else {
            throw new Error ("submitWalletUpdateBtn not found!");
        }
    }
    
    async function populateExpenseTable(expenseId) {
        const expense = await getExpense(expenseId)

        const table = document.getElementById("editCategoryTable")
        table.innerHTML=""
        table.innerHTML = `
            <tr data-expenseid="${expense.id}">
                <td>${expense.expense_name}</td>
                <td>${expense.spent_this_month}</td>
                <td>${expense.currency}</td>
                <td>${expense.created_at}</td>
                <td>
                    <button class="editCategoryBtn" data-id="${expense.id}"><i class="fas fa-edit"></i></button>
                    <button class="deleteCategoryBtn" data-id="${expense.id}"><i class="fas fa-trash"></i></button>
                    <button class="historyCategoryBtn" data-id="${expense.id}"><i class="fas fa-history"></i></button>
                </td>
            </tr>
        `
    }

    async function populateWalletTable(walletId) {
        const wallet = await getWallet(walletId)
        console.log(wallet)
        const table = document.getElementById("editWalletTable")
        table.innerHTML=""
        table.innerHTML = `
            <tr data-walletid="${wallet.id}">
                <td>${wallet.wallet_name}</td>
                <td>${wallet.balance}</td>
                <td>${wallet.currency}</td>
                <td>${wallet.created_at}</td>
                <td>
                    <button class="editWalletBtn" data-id="${wallet.id}"><i class="fas fa-edit"></i></button>
                    <button class="deleteWalletBtn" data-id="${wallet.id}"><i class="fas fa-trash"></i></button>
                    <button class="historyWalletBtn" data-id="${wallet.id}"><i class="fas fa-history"></i></button>
                </td>
            </tr>
        `
    }


    async function fetchTransactions(params, filters=[]) {
        try {

            const url = new URL("https://wallet.bithorizon.de/api/controller/TransactionController.php");
                    // Append parameters
                    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

            const respond = await fetch (url, {
                method: 'GET',
                headers: {
                    'Authorization': "Bearer " + token
                }
            })
            
            if (!respond.ok) {
                throw new Error ("could not get the Transactions list")
            }

            const result = await respond.json() ?? null
            const transactions = result.data ?? null
            return transactions

        } catch (error) {
            console.error("Error fetching Transactions:", error.message)
        }
    }
    
    async function populateExpenseTransactionTable(expenseId) {
        const params = {
            "expense_id": expenseId,
            "action": "getExpenseTransactions"
        }
        const transactions = await fetchTransactions(params, filters=[]);
        const table = document.getElementById("editExpenseTransactionTable");
        table.innerHTML="";
        const expenses = await fetchExpenseCategories();
        const wallets = await fetchWallets();
        for (const transaction of transactions) {
            const tr = document.createElement("tr");
            tr.dataset.walletid = transaction.wallet_id;
            tr.dataset.expenseid = transaction.expense_id;
    
            const wallet = wallets.find(item => item.id == transaction.wallet_id)
            const walletName = wallet.wallet_name

            // Apply status-based styling
            const transactionStatus = transaction.status;
            switch (transactionStatus) {
                case "Completed":
                    tr.style.color = "green";
                    break;
                case "Pending":
                    tr.style.color = "orange";
                    break;
                case "Failed":
                    tr.style.color = "red";
                    break;
                default:
                    tr.style.color = "black";
                    break;
            }

            // Set row HTML
            tr.innerHTML = `
                <td>${transaction.transaction_date || ""}</td>
                <td>${transaction.description || ""}</td>
                <td>${transaction.amount || ""}</td>
                <td>${walletName || ""}</td>
                <td>${transaction.transaction_type || ""}</td>
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
    
            // Append row to table
            table.append(tr);
    
            // Event Listeners
            tr.querySelector(".editTransactionBtn").addEventListener("click", function () {
                openModal("editTransactionModal");
                populateEditTransactionModal(transaction, wallets, expenses);
            });
    
            tr.querySelector(".deleteTransactionBtn").addEventListener("click", function () {
                console.log("Delete Transaction:", transaction.id);
            });
    
            tr.querySelector(".moreInfoTransactionBtn").addEventListener("click", function () {
                console.log("More Info Transaction:", transaction.id);
            });
        }

        // put a tracker for filter function
        const filterBtn = document.getElementById("expenseFilterBtn")
        filterBtn.dataset.id = expenseId
    }
    
    async function populateWalletTransactionTable(walletId) {
        const params = {
            "wallet_id": walletId,
            "action": "getWalletTransactions"
        }
        const transactions = await fetchTransactions(params, filters=[]);
        const table = document.getElementById("editWalletTransactionTable");
        table.innerHTML="";
        const expenses = await fetchExpenseCategories();
        const wallets = await fetchWallets();
        const wallet = wallets.find(item => item.id == walletId)
        let balance = wallet.balance ?? 0;

        for (const transaction of transactions) {

            balance += transaction.transaction_type === "Deposit" ? transaction.amount : -transaction.amount;
            const tr = document.createElement("tr");
            tr.dataset.walletid = transaction.wallet_id;
            tr.dataset.expenseid = transaction.expense_id;
    
            
            const expense = expenses.find(item => item.id == transaction.expense_id)
            const expenseName = expense.expense_name

            // Apply status-based styling
            const transactionStatus = transaction.status;
            switch (transactionStatus) {
                case "Completed":
                    tr.style.color = "green";
                    break;
                case "Pending":
                    tr.style.color = "orange";
                    break;
                case "Failed":
                    tr.style.color = "red";
                    break;
                default:
                    tr.style.color = "black";
                    break;
            }

            // Set row HTML
            tr.innerHTML = `
                <td>${transaction.transaction_date || ""}</td>
                <td>${transaction.description || ""}</td>
                <td>${transaction.amount || ""}</td>
                <td>${expenseName || ""}</td>
                <td>${transaction.transaction_type || ""}</td>
                <td>${balance}</td>
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
    
            // Append row to table
            table.append(tr);
    
            // Event Listeners
            tr.querySelector(".editTransactionBtn").addEventListener("click", function () {
                openModal("editTransactionModal");
                populateEditTransactionModal(transaction, wallets, expenses);
            });
    
            tr.querySelector(".deleteTransactionBtn").addEventListener("click", function () {
                console.log("Delete Transaction:", transaction.id);
            });
    
            tr.querySelector(".moreInfoTransactionBtn").addEventListener("click", function () {
                console.log("More Info Transaction:", transaction.id);
            });    
        }
        // filterTransactions(); // Apply filter after data is loaded
        const filterBtn = document.getElementById("walletFilterBtn")
        filterBtn.dataset.id = walletId
    }

    document.getElementById("expenseFilterBtn").addEventListener("click", async function(event) {
        populateFilterDiv(event)
    })

    async function populateFilterDiv(event) {

        const div = event.target.closest("div")
        const divId = div.getAttribute("id")
        const  id = event.target.dataset.id
        let transactions = []
        div.innerHTML=""
        if (divId === "walletFilterSection") {
            const expenses = await getExpenses()
            div.innerHTML = `
                <form class="form-inline" id="expenseFilterForm">
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
                </form>
            `
            const filterExpenseCategory = document.getElementById("filterExpenseCategory")
            expenses.forEach(expense => {
                const option = document.createElement("option")
                option.value = expense.id
                option.textContent = expense.expense_name
                filterExpenseCategory.append(option)
            })

            const applyBtn = document.getElementById("walletFilterFormBtn")
            applyBtn.addEventListener("click", async function(event) {
                event.preventDefault()
                const form = event.target.closest("form")
                const formData = sanitizeFormData(new FormData(form))
            const params = {
                "wallet_id": id,
                "action": "getWalletTransactions"
            } 
            transactions = await fetchTransactions(params, formData)
            })
        }
        if (divId === "expenseFilterSection") {
            const wallets = await fetchWallets()
            div.innerHTML = `
                <form class="form-inline" id="expenseFilterForm">
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
                    <select class="form-control" id="filterWallet"></select>
                    <select class="form-control" id="filterDeposit">
                        <option value="">Deposit Type</option>
                        <option value="Deposit">Deposit</option>
                        <option value="Withdrawl">Withdrawl</option>
                    </select>
                    <button type="submit" class="btn btn-primary" id="expenseFilterFormBtn">Apply</button>
                </form>
            `
            const filterWallet = document.getElementById("filterwallet")
            wallets.forEach(wallet => {
                const option = document.createElement("option")
                option.value = wallet.id
                option.textContent = wallet.wallet_name
                filterWallet.append(option)
            })

            const applyBtn = document.getElementById("expenseFilterFormBtn")
            applyBtn.addEventListener("click", async function(event) {
                event.preventDefault()
                const form = event.target.closest("form")
                const formData = sanitizeFormData(new FormData(form))
                const params = {
                    "expense_id": id,
                    "action": "getExpenseTransactions"
                } 
            transactions = await fetchTransactions(params, formData)
            })
        }

        return transactions ?? null
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

            const matchesDate = (!startDate || new Date(date) >= new Date(startDate)) && (!endDate || new Date(date) <= new Date(endDate));
            const matchesSearch = description.includes(searchDescription);
            const matchesAmount = (!minAmount || amount >= minAmount) && (!maxAmount || amount <= maxAmount);
            const matchesPaymentType = !filterPaymentType || paymentType === filterPaymentType;
            const matchesExpenseCategory = !filterExpenseCategory || expenseCategory === filterExpenseCategory;
            const matchesDeposit = !filterDeposit || type === filterDeposit;

            const shouldShow = matchesDate && matchesSearch && matchesAmount && matchesPaymentType && matchesExpenseCategory && matchesDeposit;

            row.style.display = shouldShow ? "" : "none";
        });
    }

    /**
     * Refreshes the modal that triggered the opening of another modal.
     * @param {string} closedModalId
     */
    async function refreshPreviousModal(closedModalId) {
        if (closedModalId === "addCategoryModal" || closedModalId === "editCategoryModal") {
            const expenses = await fetchExpenseCategories();
            console.log(expenses)
            populateManageExpenseModal(expenses)
        }
        if (closedModalId === "addWalletModal" || closedModalId === "editWalletModal") {
            const wallets = await fetchWallets();
            populateManageWalletModal(wallets)
        }     
    }

    /**
     * Opens a modal by ID.
     * @param {string} modalId
     */
    function openModal(modalId) {
        const modal = document.getElementById(modalId)
        modal.style.display = "block";
    }
    
    /**
     * Fetch updated wallets from the server and update the UI.
     */
    async function fetchWallets() {
        try {

            const url = new URL("https://wallet.bithorizon.de/api/controller/UserController.php");
                    const params = { action: "getWallets"};
                    // Append parameters
                    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

            const respond = await fetch (url, {
                method: 'GET',
                headers: {
                    'Authorization': "Bearer " + token
                }
            })
            
            if (!respond.ok) {
                throw new Error ("could not get the Wallets list")
            }

            const result = await respond.json() ?? null
            const wallets = result.data ?? null
            return wallets

        } catch (error) {
            console.error("Error fetching wallets:", error.message)
        }
    }

    //*check*
    const populateInputError = (parentInputId ,errorDivId, message) => {
        const previouseErrors = document.querySelectorAll(".invalid-input__err")
        if (previouseErrors.length > 0) {
            previouseErrors.forEach(element => {
                element.remove();
            })
        }
        const input = document.getElementById(parentInputId);
        const errorDiv = document.createElement('div');
        errorDiv.setAttribute('class', 'invalid-input__err');
        errorDiv.setAttribute('id', errorDivId);
        errorDiv.innerHTML = message;
        console.log(errorDiv)

        input.insertAdjacentElement("afterend", errorDiv);
    }

    //*check*
    function populateDropdowns() {
        // Expense Categories
        const expenseCategorySelect = document.getElementById('expenseCategory');
        expenseCategorySelect.innerHTML = '<option value="">Select Category</option>';
        if (expenseCategories.length > 0) {
            expenseCategories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                expenseCategorySelect.appendChild(option);
            });
        }
        
        
    
        // Wallets
        const walletSelect = document.getElementById('wallet');
        walletSelect.innerHTML = '<option value="">Select Wallet</option>';
        wallets.forEach(wallet => {
            const option = document.createElement('option');
            option.value = wallet.id;
            option.textContent = `${wallet.name} (${wallet.currency})`;
            walletSelect.appendChild(option);
        });
    }

    //*check*
    async function populateManageModal(type) {
        if (type === 'expense') {
            const expenseManageTable = document.getElementById('expenseManageTable')
            const newExpenses = await fetchExpenseCategories();
            expenseManageTable.innerHTML = "";
            if (Object.keys(newExpenses).length > 0) {
                newExpenses.forEach (expense=>{
                    const tr = document.createElement('tr');
                    tr.dataset.id = expense.id
                    
                    // Object.keys(expense).forEach((key) => {
                        const td0 = document.createElement('td')
                        td0.textContent = expense['expense_name']

                        const td1 = document.createElement('td')
                        td1.textContent = expense['currency']

                        const td2 = document.createElement('td')
                        td2.textContent = expense['spent_this_month']

                        const td3 = document.createElement('td')
                        td3.textContent = expense['created_at']

                        const td4 = document.createElement('td')
                        const editButton = document.createElement('button')
                        const editIcon = document.createElement('i')
                        const deleteButton = document.createElement('button')
                        const deleteIcon = document.createElement('i')
                        const reportButton = document.createElement('button')
                        const reportIcon = document.createElement('i')
                        editButton.classList.add("editCategoryBtn") 
                        editButton.dataset.id = expense['id']
                        editIcon.classList.add('fas', 'fa-edit')
                        deleteButton.classList.add("deleteCategoryBtn")
                        deleteButton.dataset.id = expense['id']
                        deleteIcon.classList.add('fas', 'fa-trash')
                        reportButton.classList.add("historyCategoryBtn")
                        reportButton.dataset.id  = expense['id']
                        reportIcon.classList.add('fas', 'fa-history')
                        editButton.append(editIcon)
                        deleteButton.append(deleteIcon)
                        reportButton.append(reportIcon)
                        td4.append(editButton)
                        td4.append(deleteButton)
                        td4.append(reportButton)

                        tr.append(td0)
                        tr.append(td1)
                        tr.append(td2)
                        tr.append(td3)
                        tr.append(td4)


                        // switch (key) {
                        //     case 'id':
                        //         const editButton = document.createElement('button')
                        //         const editIcon = document.createElement('i')
                        //         const deleteButton = document.createElement('button')
                        //         const deleteIcon = document.createElement('i')
                        //         const reportButton = document.createElement('button')
                        //         const reportIcon = document.createElement('i')
                        //         editButton.classList = "editCategoryBtn"
                        //         editButton.dataset.id = expense[key]
                        //         editIcon.classList = ('fas', 'fa-edit')
                        //         editButton.classList = "deleteCategoryBtn"
                        //         editButton.dataset.id = expense[key]
                        //         editIcon.classList = ('fas', 'fa-trash')
                        //         editButton.classList = "historyCategoryBtn"
                        //         editButton.dataset.id  = expense[key]
                        //         editIcon.classList = ('fas', 'fa-history')
                        //         editButton.append(editIcon)
                        //         deleteButton.append(deleteIcon)
                        //         reportButton.append(reportIcon)
                        //         break;
                        //     case 'expense_name':
                        //         td = document.createElement('td')
                        //         td.textContent = expense[key]
                        //         break
                        //     case 'currency':
                        //         td = document.createElement('td')
                        //         td.textContent = expense[key]
                        //         break
                        //     case 'spent_this_month':
                        //         td = document.createElement('td')
                        //         td.textContent = expense[key]
                        //         break 
                        //         case 'created_at':
                        //         td = document.createElement('td')
                        //         td.textContent = expense[key]
                        //         break      
                        //     default:
                        //         break;
                        // }
                        
                        
                        // tr.append(td)
                        
                    // })
                    expenseManageTable.append(tr)

                })
            }
            
            
        } else if (type === "wallet") {
            const walletManageTable = document.getElementById('walletManageTable')
            const newWallets = await getWallets();
            walletManageTable.innerHTML = "";
            if (Object.keys(newWallets).length > 0) {
                newWallets.forEach (wallet=>{
                    const tr = document.createElement('tr');
                    tr.dataset.id = escapeHTML(wallet.id)
                    
                    wallet.forEach((value) => {
                        let td = document.createElement('td')
                        if (value === 'id'){
                            const editButton = document.createElement('button')
                            const editIcon = document.createElement('i')
                            const deleteButton = document.createElement('button')
                            const deleteIcon = document.createElement('i')
                            const reportButton = document.createElement('button')
                            const reportIcon = document.createElement('i')
                            editButton.classList = "editWalletBtn"
                            editButton.dataset.id = escapeHTML(value)
                            editIcon.classList = ('fas', 'fa-edit')
                            editButton.classList = "deleteWalletBtn"
                            editButton.dataset.id = escapeHTML(value)
                            editIcon.classList = ('fas', 'fa-trash')
                            editButton.classList = "historyWalletBtn"
                            editButton.dataset.id  = escapeHTML(value)
                            editIcon.classList = ('fas', 'fa-history')
                            editButton.append(editIcon)
                            deleteButton.append(deleteIcon)
                            reportButton.append(reportIcon)

                        } else {
                            td.textContent = escapeHTML(value)
                        }
                        
                        tr.append(td)
                    })
                    walletManageTable.append(tr)

                })
            }
        }
    }

    async function saveTransaction(url, formData) {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Authorization": "Bearer " + token // Send token 
            },
            body: formData
        })
        if (!response.ok) {
            throw new Error ("could not save the Transaction",  501)
        } else {
            result = await response.json()
            return result
        }
    }

    //*delete*
    async function getExpenses() {

        const url = new URL("https://wallet.bithorizon.de/api/controller/UserController.php");
                    // const categoryName = sanitizeInput(this.closest('tr').firstElementChild.textContent)
                    // const params = { id: sanitizeInput(this.dataset.id), expense_name: categoryName};
                    // // Append parameters
                    // Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        const result = await fetch (url, {
            method: 'GET',
            headers: {
                'Authentication': "Bearer " + token
            }
        })

        const response = await result.json();
        if (!response.ok) {
            throw new Error(`HTTP error! Status: 'Could not get expenses from database.' ${response.status}`);
        } else {
            expenseCategories = response.data
            return expenseCategories
        }
        
    }
    
    function sanitizeFormData (formData) {
        //sanitize and validate data
        formData.forEach((value, key) => {
            formData.set(key, sanitizeInput(value));
        });
        return formData;
       
    }

    //*check/
    function showRow (row) {
        const originalRow = row.cloneNode(true); 
        const parentId = row.parentNode?.getAttribute("id")
        console.log(parentId)

        
        switch (parentId) {
            case "expenseManageTable":
                const expenseUpdate = document.getElementById("editCategoryTable")
                expenseUpdate.innerHTML = ""
                expenseUpdate.append(row)
                break;
            case "editCategoryTable":

                const name = row.children[0]?.textContent.trim() || ""
                const currency = row.children[2]?.textContent.trim() || ""
                const spent = row.children[1]?.textContent.trim() || ""
                const date = row.children[3]?.textContent.trim() || ""
                const newRow = document.createElement("tr")
                const id = row.dataset.expenseid
                newRow.dataset.expenseid = id; 
                newRow.innerHTML = `
                <td colspan="5">
                    <form id="expenseUpdateForm" class="form-inline">
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <input class="form-control" name="expense_name" type="text" value="${name}">
                                </td>
                                <td>
                                    <input class="form-control" name="spent_this_month" type="text" value="${spent}">
                                </td>
                                <td>
                                    <input class="form-control" name="currency" type="text" value="${currency}">                               
                                </td>
                                <td>
                                    ${date}
                                </td>
                                <td>
                                    <button id="submitExpenseUpdateBtn" class="btn btn-primary" type="submit">Apply</button>
                                    <button id="canselExpenseUpdateBtn" class="btn btn-secondary" type="button">Cancel</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>    
                `
                if (row.isConnected) {
                    row.replaceWith(newRow);
                } else {
                    throw new Error ("Row was removed from the DOM before replacing.");
                }

                const submitBtn = document.getElementById("submitExpenseUpdateBtn");
                if (submitBtn) {
                    submitBtn.addEventListener("click", async function (event) {
                        event.preventDefault()
                        const expenseForm = document.getElementById("expenseUpdateForm")
                        // event.preventDefault()
                        const formData = new FormData(expenseForm);
                        formData.append("id", id)
                        formData.append("action", "updateExpense")
                        formData.append("created_at", date)
                        const expenseUpdateUrl = 'https://wallet.bithorizon.de/api/controller/ExpenseController.php'
                        applyUpdate(formData, expenseUpdateUrl)
                    })
                }
                 else {
                    throw new Error ("submitExpenseUpdateBtn not found!");
                }
                
                const cancelBtn = document.getElementById("canselExpenseUpdateBtn");
                if (cancelBtn) {
                    cancelBtn.addEventListener("click", async function (event) {
                        newRow.replaceWith(originalRow);  // Restore the original row
                });
                } else {
                    throw new Error ("submitExpenseUpdateBtn not found!");
                }
                break;
            case "walletManageTable":
                const walletUpdate = document.getElementById("editWalletTable")
                walletUpdate.innerHTML = ""
                walletUpdate.append(row)
                break;
            case "editWalletTable":
                console.log(row)
                const walletName = row.children[0]?.textContent.trim() || ""
                const walletCurrency = row.children[2]?.textContent.trim() || ""
                const balance = row.children[1]?.textContent.trim() || ""
                const walletDate = row.children[3]?.textContent.trim() || ""
                const newRow2 = document.createElement("tr")
                const walletId = row.dataset.walletid
                newRow2.dataset.walletid = walletId; 
                console.log(newRow2)

                if (row.isConnected) {
                    row.replaceWith(newRow2);
                } else {
                    throw new Error ("Row was removed from the DOM before replacing.");
                }
                newRow2.innerHTML = `
                <td colspan="5">
                    <form id="walletUpdateForm" class="form-inline">
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <input class="form-control" name="wallet_name" type="text" value="${walletName}">
                                </td>
                                <td>
                                    <input class="form-control" name="balance" type="text" value="${balance}">
                                </td>
                                <td>                                    
                                    <input class="form-control" name="currency" type="text" value="${walletCurrency}">
                                </td>
                                <td>
                                    ${walletDate}
                                </td>
                                <td>
                                    <button id="submitWalletUpdateBtn" class="btn btn-primary" type="submit">Apply</button>
                                    <button id="canselWalletUpdateBtn" class="btn btn-secondary" type="button">Cancel</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>    
                `
                

                const walletSubmitBtn = document.getElementById("submitWalletUpdateBtn");
                if (walletSubmitBtn) {
                    walletSubmitBtn.addEventListener("click", async function (event) {
                        event.preventDefault()
                        const walletForm = document.getElementById("walletUpdateForm")
                        const formData = new FormData(walletForm);
                        formData.append("id", walletId)
                        formData.append("action", "updateWallet")
                        formData.append("created_at", walletDate)
                        const walletUpdateUrl = 'https://wallet.bithorizon.de/api/controller/WalletController.php'
                        applyUpdate(formData, walletUpdateUrl)
                    })
                }
                 else {
                    throw new Error ("submitWalletUpdateBtn not found!");
                }
                
                const walletCancelBtn = document.getElementById("canselWalletUpdateBtn");
                if (walletCancelBtn) {
                    walletCancelBtn.addEventListener("click", async function (event) {
                        newRow2.replaceWith(originalRow);  // Restore the original row
                });
                } else {
                    throw new Error ("submitWalletUpdateBtn not found!");
                }

                
                break;
            default:
                break;
        }
    }

    const applyUpdate = async function (formData, url) {
        
        // // event.preventDefault()
        // const formData = new FormData(expenseForm);
        // formData.append("id", id)
        // formData.append("action", "updateExpense")
        // formData.append("created_at", date)
        const sanitizedData = sanitizeFormData(formData)
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    "Authorization": "Bearer " + token // Send token 
                },
                body: sanitizedData
            })
            if (!response.ok) {
                throw new Error(`Server Error: ${response.status}`);
            }

            const result = await response.json()

            if (result.success) {
                if (formData.get("action") === "updateWallet") {

                    const walletForm = document.getElementById("walletUpdateForm")
                    const updatedRow = document.createElement("tr");
                    updatedRow.dataset.walletid = formData.get("id")
                    updatedRow.innerHTML = `
                        <td>${formData.get("wallet_name") || ""}</td>
                        <td>${formData.get("balance") || ""}</td>
                        <td>${formData.get("currency") || ""}</td>
                        <td>${formData.get("created_at") || ""}</td>
                        <td>
                            <button class="editWalletBtn" data-id=${formData.get("id") || ""}><i class="fas fa-edit"></i></button>
                            <button class="deleteWalletBtn" data-id=${formData.get("id") || ""}><i class="fas fa-trash"></i></button>
                            <button class="historyWalletBtn" data-id=${formData.get("id") || ""}><i class="fas fa-history"></i></button>
                        </td>
                    `;

                    walletForm.closest("tr").replaceWith(updatedRow);
                } else if (formData.get("action") === "updateExpense") {

                    const expenseForm = document.getElementById("expenseUpdateForm")
                    const updatedRow = document.createElement("tr");
                    updatedRow.dataset.expenseid = formData.get("id")
                    updatedRow.innerHTML = `
                        <td>${formData.get("expense_name") || ""}</td>
                        <td>${formData.get("spent_this_month") || ""}</td>
                        <td>${formData.get("currency") || ""}</td>
                        <td>${formData.get("created_at") || ""}</td>
                        <td>
                            <button class="editCategoryBtn" data-id=${formData.get("id")}><i class="fas fa-edit"></i></button>
                            <button class="deleteCategoryBtn" data-id=${formData.get("id")}><i class="fas fa-trash"></i></button>
                            <button class="historyCategoryBtn" data-id=${formData.get("id")}><i class="fas fa-history"></i></button>
                        </td>
                    `;

                    expenseForm.closest("tr").replaceWith(updatedRow);

                } else if (formData.get("action") === "udpateTransaction") {
                    closeModal(document.getElementById("editTransactionModal"))
                }
            } 
        } catch(error) {
            console.log(error.message)
            displayErrorMessage(walletForm, error.message);

        }
    }

    

    // Close modal when clicking outside modal content
    window.addEventListener("click", function (event) {
        document.querySelectorAll(".modal").forEach((modal) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.getElementById("manageCategoriesBtn").addEventListener("click", async function () {
        openModal("categoryModal");
        refreshPreviousModal("editCategoryModal")
        // fetchExpenseCategories(); // Fetch fresh data when opening  
    });

    document.getElementById("transactionForm").addEventListener("submit", async function (e) {
        e.preventDefault()
        const form = document.getElementById("transactionForm")
        const formData = new FormData(form)
        const cleanFormData = sanitizeFormData(formData)
        cleanFormData.append("action", "addTransaction")
        cleanFormData.append("transaction_type", "Withdrawl")
        const url = new URL("https://wallet.bithorizon.de/api/controller/TransactionController.php");
        if (await saveTransaction(url, cleanFormData)) {
            form.querySelectorAll("input").forEach(input => input.value ="")
            form.querySelectorAll("select").forEach(select => select.value ="")
            form.querySelector("#transaction_date").value = new Date().toISOString().split("T")[0];
        }

    });

    document.getElementById("manageWalletsBtn").addEventListener("click", async function () {
        openModal("walletModal");
        fetchWallets(); // Fetch fresh data when opening    
    });

    // Handle closing modals and refreshing data
    document.querySelectorAll(".modal .close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function () {
            let parentModal = this.closest(".modal");
            parentModal.style.display = "none";
            refreshPreviousModal(parentModal.id);
        });
    });

    // Handle Wallet form submission
    document.getElementById("addWalletForm").addEventListener("submit",async function (e) {
        try {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append("action", "addWallet")

            //sanitize and validate data
            const sanitizedData = sanitizeFormData(formData);

            const response = await fetch("api/controller/WalletController.php", { 
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token // Send token 
                },
                body: sanitizedData
            })
            const result = await response.json();

            if(!response.ok) {
                if (result.error && result.error.fieldName) { 
                    switch (result.error.fieldName) {
                        case "wallet_name":
                            populateInputError("newWalletName", "wallet__name__input__err", result.error.message)
                            throw new Error (result.error.message)
                        break;
                        case "currency":
                            populateInputError("newWalletCurrency", "wallet__currency__input__err", result.error.message)
                            throw new Error (result.error.message)
                        break;
                        case "balance":
                            populateInputError("newWalletBalance", "wallet__balance__input__err", result.error.message)
                            throw new Error (result.error.message)
                        break;
                    
                        default:
                            throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                }
            } else {
                const inputs = this.querySelectorAll("input")
                inputs.forEach( input => input.value = "");
                closeModal(this.closest(".modal"))
                // fetchWallets(); // Refresh categories in original modal
                
            }      
        } catch (error) {
            console.error('Error: save failed', error.message);
        }
    });

    document.getElementById("addCategoryForm").addEventListener("submit", async function (event) {
        event.preventDefault();

        try {
            const formData = new FormData(this);
            formData.append("action", "addCategory")

            //sanitize and validate data
            const sanitizedData = sanitizeFormData(formData);
            
            
            const response = await fetch('api/controller/ExpenseController.php', {
                method: 'POST',
                headers: {
                    // "Content-Type": "multipart/form-data",
                    "Authorization": "Bearer " + token // Send token 
                },
                body: sanitizedData
            })
            
            if (!response.ok) {
                const result = await response.json();
                if (result.error && result.error.fieldName) { 
                    switch (result.error.fieldName) {
                        case "expense_name":
                            populateInputError("newCategoryName", "category__name__input__err", result.error.message)
                            throw new Error (result.error.message)
                        break;
                        case "currency":
                            populateInputError("newCategoryCurrency", "category__currency__input__err", result.error.message)
                            throw new Error (result.error.message)
                        break;
                        case "spent_this_month":
                            populateInputError("newCategorySpent", "category__balance__input__err", result.error.message)
                            throw new Error (result.error.message)
                        break;
                    
                        default:
                            throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    // throw new Error(result.error ||  'Please try again later.'); // Show error message
                }
            } else {
                const inputs = this.querySelectorAll("input")
                inputs.forEach( input => input.value = "");

                closeModal(this.closest(".modal"))
                // await populateManageModal('wallet')
                // // expenseCategories = result;
                // populateDropdowns();
            }
        } catch (error) {
           
            console.error('Error: save failed', error.message);
        }
    });
    

    document.addEventListener("click", async function (event) {
        if (event.target.closest(".editCategoryBtn")) {
            const rowId = event.target.closest(".editCategoryBtn").dataset.id;
            const tbody = event.target.closest("tbody").getAttribute("id")

            if (tbody === "expenseManageTable") {
                openModal("editCategoryModal")
                populateEditExpenseModal(rowId)
            }
            if (tbody === "editCategoryTable") {
                populateUpdateExpenseModal(rowId)
            }
            
        }
        if (event.target.closest(".editWalletBtn")) {
            const rowId = event.target.closest(".editWalletBtn").dataset.id;
            const tbody = event.target.closest("tbody").getAttribute("id")

            if (tbody === "walletManageTable") {
                openModal("editWalletModal")
                populateEditWalletModal(rowId)
            }
            if (tbody === "editWalletTable") {
                populateUpdateWalletModal(rowId)
            }
            // console.log(rowId)
            // const row = document.querySelector(`tr[data-walletid="${rowId}"]`);
            // if (!row) {
            //     console.error("Row not found for ID:", rowId);
            //     return;
            // }
            // // document.getElementById("editWalletModal").style.display = "block";
            // openModal("editWalletModal")
            // showRow(row);
        }
        if (event.target.closest(".deleteWalletBtn")) {
            if ( confirm("Are you sure?")) {
                try {
                    const rowId = event.target.closest(".deleteWalletBtn").dataset.id;
                    const row = event.target.closest("tr");
                    if (!row) {
                        console.error("Row not found for ID:", rowId);
                        return;
                    }
                    const url = new URL("https://wallet.bithorizon.de/api/controller/WalletController.php");
                    const walletName = sanitizeInput(row.firstElementChild.textContent)
                    const params = { 
                        id: sanitizeInput(rowId),
                        wallet_name: walletName,
                        "action": "deleteWallet"
                    };
                    deleteRow(event, url, params)

                } catch (error) {
                        console.log(error.message)
                }          
                    
            }
        }
        if (event.target.closest(".deleteCategoryBtn")) {
            if (confirm("Are you sure?")) {
                try {
                    const rowId = event.target.closest(".deleteCategoryBtn").dataset.id;
                    const row = event.target.closest("tr");
                    if (!row) {
                        console.error("Row not found for ID:", rowId);
                        return;
                    }
                    const url = new URL("https://wallet.bithorizon.de/api/controller/ExpenseController.php");
                    const expenseName = sanitizeInput(row.firstElementChild.textContent)
                    const params = { 
                        id: sanitizeInput(rowId),
                        expense_name: expenseName,
                        "action": "deleteExpense"
                    };
                    
                    deleteRow(event, url, params)
                } catch (error) {
                        console.log(error.message)
                }          
                    
            }
        }
        if (event.target.closest(".historyCategoryBtn")) {
            console.log(event.target)
        }
        if (event.target.closest(".historyWalletBtn")) {
            console.log(event.target)

        }
    });

    async function deleteRow (event, url, params) {
        // Append parameters
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const result = await fetch(url, { 
            method: "DELETE",
            headers: {
                // "Content-Type": "application/json",
                "Authorization": "Bearer " + token // Send token
            }
        })

        if (!result.ok) {
                throw new Error(`HTTP error! Status: ${result.status}`);
            }
        const message = await result.json();
        
        if (message.data === 'Record successfully deleted.') {
            const modalId = event.target.closest(".modal").getAttribute("id")

            switch (modalId) {
                case "walletModal":
                    event.target.closest('tr').remove()
                    break;
                case "editWalletModal":
                    closeModal(event.target.closest(".modal"))
                    break;
                case "categoryModal":
                    event.target.closest('tr').remove()
                    break;
                case "editCategoryModal":
                    closeModal(event.target.closest(".modal"))
                    break;
                default:
                    throw new Error ("Unknown command", 400)
            }
            
        } else {
            throw new Error ("Error deleting record:", message.error || "Unknown error")
        }
    }

    document.getElementById("openAddCategoryModal").addEventListener("click", function () {
        openModal("addCategoryModal")
        // document.getElementById("addCategoryModal").style.display = "block";
    });
    
    document.getElementById("openAddWalletModal").addEventListener("click", function () {
        openModal("addWalletModal")
        // document.getElementById("addWalletModal").style.display = "block";
    });
    
    
    
    
});
