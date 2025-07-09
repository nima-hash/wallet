<?php
// session_start();
require __DIR__ . "/config/database.php";

$userErr = $verPasErr = $emailErr = $phoneErr = $addressErr = $birthdayErr = $passErr = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $new_user = new User;
        $username = $new_user->test_input($_POST['user__input']);
        $password = $new_user->test_input($_POST['pass__input']);
        $verifyPassword = $new_user->test_input($_POST['pass-verify__input']);
        $email = $new_user->test_input($_POST['email__input']);
        $phone = $new_user->test_input($_POST['phone__input']);
        $address = $new_user->test_input($_POST['address__input']);
        

        // Validate inputs
        if (empty($username)) {
            $userErr = "Username is required.";
            throw new Exception($userErr);
        }
        if (empty($password)) {
            $passErr = "Password is required.";
            throw new Exception($passErr);
        }
        $new_user->validatePassword($password);
        if ($password !== $verifyPassword) {
            $verPasErr = "Passwords do not match.";
            throw new Exception($verPasErr);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format.";
            throw new Exception($emailErr);
        }
        if (!preg_match("/^[0-9]{10}$/", $phone)) {
            $phoneErr = "Phone number must be 10 digits.";
            throw new Exception($phoneErr);
        }

        // If no errors, proceed with registration
        if (empty($userErr) && empty($passErr) && empty($verPasErr) && empty($emailErr) && empty($phoneErr)) {
            $result = $new_user->registerUser($username, $password, $email, $phone, $address);
            
            
            if ($result === "The user was successfully added.") {
                sendOutput(json_encode(['success' => true, 'message' => 'Registeration successful']), ['Content-Type: application/json', 201]);
                // header("Location: login.php");
                exit();
            } else {
                $error_message = json_encode($result);
                // Send a JSON response indicating failure
                sendOutput(json_encode(['success' => false, 'error' => $result]), ['Content-Type: application/json', 400]);
                exit();
            }
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
    <title>Signup</title>
</head>
<body>
    <div class="login__Cont">
        <div class="login_header">
            <h4>Please fill out your information</h4>
        </div>
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <form class="login__form" id="signup__form" method="POST">
            <label class="label" for="user__input">Username: </label>
            <input class="user__input" type="text" autocomplete="username" name="user__input" id="user__input" placeholder="Username" required>
            <div class="invalid-input__err" id="user__input__err"><?php echo htmlspecialchars($userErr); ?></div>

            <label class="label" for="pass__input">Password: </label>
            <input class="pass__input" type="password" name="pass__input" autocomplete="off" id="pass__input" placeholder="Password" required>
            <div class="invalid-input__err" id="pass__input__err"><?php echo htmlspecialchars($passErr); ?></div>

            <label class="label" for="pass-verify__input">Verify Password: </label>
            <input class="pass__input" type="password" name="pass-verify__input" autocomplete="off" id="pass-verify__input" placeholder="Enter password again" required>
            <div class="invalid-input__err" id="pass-verify__input__err"><?php echo htmlspecialchars($verPasErr); ?></div>

            <label class="label" for="email__input">Email: </label>
            <input class="user__input" type="email" name="email__input" autocomplete="off" id="email__input" placeholder="Email" required>
            <div class="invalid-input__err" id="email__input__err"><?php echo htmlspecialchars($emailErr); ?></div>

            <label class="label" for="phone__input">Phone: </label>
            <input class="user__input" type="tel" name="phone__input" autocomplete="off" id="phone__input" placeholder="Telephone" required>
            <div class="invalid-input__err" id="phone__input__err"><?php echo htmlspecialchars($phoneErr); ?></div>

            <label class="label" for="address__input">Address: </label>
            <input class="user__input" type="text" name="address__input" autocomplete="off" id="address__input" placeholder="Address" required>
            <div class="invalid-input__err" id="address__input__err"><?php echo htmlspecialchars($addressErr); ?></div>

            <div class="formBtns__div">
                <button id="submit_Btn" type="submit" class="button">Register</button>
                <button type="reset" class="button secondary">Cancel</button>
            </div>
        </form>
        <p>Already have an account? <a href="login.php">Sign in now!</a></p>
    </div>
    <script src="functions.js"></script>
    <script src="signup.js"></script>
</body>
</html>