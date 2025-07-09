<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {

    $userName = null;
    $userImage = PROJECT_ROOT_PATH . '/public/default-avatar.jpg';
} else {
    
    $userName = $_SESSION['username'];
    $userImage = $_SESSION['profile_picture'] ?: PROJECT_ROOT_PATH . '/public/default-avatar.jpg';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">BankPay</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href= <?= PROJECT_ROOT_PATH . "/index.php"?>><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" href=<?= PROJECT_ROOT_PATH . "/views/transaction_form.php"?>><i class="fas fa-exchange-alt"></i> Make a Transaction</a></li>
                <li class="nav-item"><a class="nav-link" href=<?= PROJECT_ROOT_PATH . "/views/transaction_history.php"?>><i class="fas fa-history"></i> Transaction History</a></li>
            </ul>

            <!-- User Account Section -->
            <ul class="navbar-nav ms-auto">
                <?php if ($userName): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= $userImage ?>" alt="Profile" class="profile-img me-2">
                            <?= htmlspecialchars($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href=<?= PROJECT_ROOT_PATH . "/views/profile.php"?>><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href=<?= PROJECT_ROOT_PATH . "/logout.php"?>><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href=<?= PROJECT_ROOT_PATH . "/login.php"?>><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>