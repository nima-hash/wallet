document.addEventListener("DOMContentLoaded", function () {
    fetch("../api/transactions.php") // Replace with your actual API endpoint
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById("transaction-list");
            tableBody.innerHTML = "";

            data.forEach(transaction => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${transaction.date}</td>
                    <td>${transaction.payment_method}</td>
                    <td>${transaction.type}</td>
                    <td>$${transaction.amount.toFixed(2)}</td>
                    <td>${transaction.status}</td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => console.error("Error loading transactions:", error));
});
