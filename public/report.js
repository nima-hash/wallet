// Sample Data (Replace with API calls in a real application)
const transactions = [
    { id: 1, date: '2023-10-01', amount: 100, category: 'Groceries', wallet: 'Bank A', description: 'Supermarket' },
    { id: 2, date: '2023-10-05', amount: 50, category: 'Entertainment', wallet: 'Credit Card', description: 'Movie' },
];

// DOM Elements
const reportFilters = document.getElementById('reportFilters');
const reportTable = document.querySelector('#reportTable tbody');

// Generate Report
function generateReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const searchAmount = parseFloat(document.getElementById('searchAmount').value);
    const searchDescription = document.getElementById('searchDescription').value.trim().toLowerCase();
    const sortBy = document.getElementById('sortBy').value;

    let filteredTransactions = transactions.filter(transaction => {
        const matchesDate = (!startDate || transaction.date >= startDate) && (!endDate || transaction.date <= endDate);
        const matchesAmount = isNaN(searchAmount) || transaction.amount === searchAmount;
        const matchesDescription = !searchDescription || transaction.description.toLowerCase().includes(searchDescription);
        return matchesDate && matchesAmount && matchesDescription;
    });

    // Sort Transactions
    if (sortBy === 'amount') {
        filteredTransactions.sort((a, b) => a.amount - b.amount);
    } else if (sortBy === 'category') {
        filteredTransactions.sort((a, b) => a.category.localeCompare(b.category));
    } else if (sortBy === 'wallet') {
        filteredTransactions.sort((a, b) => a.wallet.localeCompare(b.wallet));
    } else if (sortBy === 'time') {
        filteredTransactions.sort((a, b) => new Date(a.date) - new Date(b.date));
    }

    // Populate Table
    reportTable.innerHTML = '';
    filteredTransactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${transaction.date}</td>
            <td>${transaction.amount}</td>
            <td>${transaction.category}</td>
            <td>${transaction.wallet}</td>
            <td>${transaction.description}</td>
        `;
        reportTable.appendChild(row);
    });
}

// Event Listener
reportFilters.addEventListener('submit', (e) => {
    e.preventDefault();
    generateReport();
});

// Initial Load
generateReport();