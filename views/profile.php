<?php
session_start();
include '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newName = $_POST['name'];

    // File Upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES["profile_picture"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$newName, $targetFilePath, $_SESSION['user_id']]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$newName, $_SESSION['user_id']]);
    }

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <div class="container">
        <h2>Profile Settings</h2>
        <form action="profile.php" method="post" enctype="multipart/form-data">
            <label for="name">Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

            <label>Profile Picture:</label>
            <input type="file" name="profile_picture">
            <img src="<?= $user['profile_picture'] ?: 'public/default-avatar.png' ?>" class="profile-img-preview">

            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>
