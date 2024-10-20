<?php
session_start();
require 'configure.php';

//This page is used to add the credential for the traditional login or simple username and password login, AFter the user is logged in by google ID. 

if (!isset($_SESSION['UserID'])) {

    // Redirect to login page if the user is not logged in
    header('Location: LoginPage.php');
    exit();
}

// Fetch current user information to display
$userId = $_SESSION['UserID'];
$checkUser = $config->prepare("SELECT Username, GoogleID, PasswordHash FROM users WHERE UserID = ?");
$checkUser->bind_param("i", $userId);
$checkUser->execute();
$result = $checkUser->get_result();
$user = $result->fetch_assoc();

// Determine if the user has set up a traditional login
$isGoogleUser = !empty($user['GoogleID']);
$hasTraditionalLogin = !empty($user['Username']) && !empty($user['PasswordHash']);
$formTitle = $hasTraditionalLogin ? "Update Normal Login" : "Set Up Normal Login";
$buttonText = $hasTraditionalLogin ? "Update" : "Set Up";

// Handle messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $formTitle; ?></title>
    <link rel="stylesheet" href="setup_traditional_login_form.css">
    <link rel="stylesheet" href="colors.css">
    <script>
        function validatePassword() {
            const password = document.getElementById("password").value;
            const passwordPattern = /^(?=.*[\W_]).{8,}$/;
            if (password.length > 0 && !passwordPattern.test(password)) {
                alert("Password must be at least 8 characters long and include at least one special character.");
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="toplogin">
        <h1 id="logintitle"><?php echo $formTitle; ?></h1>
    </div>
    <div class="container" id="Login">
  
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="setup_traditional_login.php" method="POST" onsubmit="return validatePassword()">
            <div class="forumtext">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['Username']); ?>" placeholder="USERNAME" required>
            </div>
            <div class="forumtext">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Enter new password">
            </div>
            <div class="form-group">
                <input class="sbmt" type="submit" value="<?php echo $buttonText; ?>">
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
