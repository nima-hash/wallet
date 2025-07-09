<?php
// session_start();
include __DIR__ . "/config/database.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_user = new User;

    try {

        $username = $new_user->test_input($_POST['user__input']);
        $password = $new_user->test_input($_POST['pass__input']);
        if (!$username ?: null || !$password ?: null) {
            throw new Exception('Username and password are required.', 400);
        }

        $jwt = $new_user->checkPassword($username, $password);
        if ($jwt) {
            sendOutput(json_encode(['success' => true, 'message' => 'Login successful', 'token' => $jwt]), ['Content-Type: application/json', 200]);
            // header("Location: index.php");
            exit();
        }else {
            throw new Exception("Invalid username or password.", 400);
        }
    } catch (Exception $e) {
    
        sendOutput(json_encode(array('error' => $e->getMessage())),
            array('Content-Type: application/json',$e->getCode() ?: 500)
        );
        exit();    
    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/style.css">
    <title>Login</title>
</head>
<body>
    <div class="login__Cont">
        <div class="login_header">
            <h4>Please Login using your Username and Password</h4>
        </div>
        <form class="login__form" method="POST">
            <label class="label" for="user__input">Username: </label>
            <input class="user__input" type="text" autocomplete="username" name="user__input" id="user__input" placeholder="Username" required>
            <label class="label" for="pass__input">Password: </label>
            <input class="pass__input" type="password" name="pass__input" autocomplete="current-password" id="pass__input" placeholder="Password" required>
            <div class="formBtns__div">
                <button type="submit" class="button" id="submitLogin">Login</button>
                <button type="reset" class="button secondary">Cancel</button>
            </div>
        </form>
        <p>Forgot your password? <a href="passReset.php">Reset now!</a></p>
        <p>Don't have an account? <a href="signup.php">Sign up now!</a></p>
    </div>
    <script src="functions.js"></script>
    <script src="login.js"></script>
</body>
</html>