<?php
session_start();
require 'configure.php';
require_once 'vendor/autoload.php'; 

// Add Google Client configuration here 
$googleClient = new Google_Client();
$googleClient->setClientId();
$googleClient->setClientSecret();
$googleClient->setRedirectUri(); 
$googleClient->addScope("email");
$googleClient->addScope("profile");


if (isset($_GET['google_login'])) {
    $googleLoginUrl = $googleClient->createAuthUrl(); // Generate Google OAuth URL
    header('Location: ' . filter_var($googleLoginUrl, FILTER_SANITIZE_URL)); 
    exit();
}



if (isset($_POST['submit_btn'])) {
    $username = $_POST['username'];
    $loginpass = $_POST['password'];

    // Check if user exists in the database
    $select = "SELECT * FROM users WHERE Username=?";
    $stmt = $config->prepare($select);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verify password (if hashed)
        if (password_verify($loginpass, $user['PasswordHash']) || $loginpass === $user['Password']) {
            // Set session and redirect based on role
            $_SESSION['Username'] = $user['Username'];
            $_SESSION['Role'] = $user['Role'];
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['CourseID'] = $user['CourseID'];

            if ($user['Role'] == 'Admin') {
                header('Location: Mainpage.php'); 
            } else {
                header('Location: Mainpage.php'); 
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid Username or Password";
            header('Location: LoginPage.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid Username or Password";
        header('Location: LoginPage.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="LoginPage.css">
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="container" id="Login">
        <div class="toplogin">
            <img src="Images/Real_logo.png" alt="SACE Logo">
            <h1 id="verticleline"></h1>
            <h1 id="sacename">SACE Portal</h1>
        </div>
        <hr id="horizontalline">

        <h1 id="logintitle">Login</h1>
        <form action="" method="POST">
            <div class="googleicon">
                
                <a href="?google_login=true">
                    <img src="https://developers.google.com/identity/images/btn_google_signin_dark_normal_web.png" alt="Sign in with Google">
                </a>
            </div>
            <p class="or">---------OR----------</p>
            <div class="forumtext">
                <i class="fas fa-envelope"></i>
                <input type="email" id="username" name="username" placeholder="USERNAME" required>
            </div>
            <div class="forumtext">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="PASSWORD" required>
                <i class="fas fa-sign-in"></i>
                <input class="sbmt" type="submit" id="submitbtn" name="submit_btn" value="Log in">
                <?php
                if (isset($_SESSION['error'])) {
                    echo "<div class='error'>" . $_SESSION['error'] . "</div>";
                    unset($_SESSION['error']);
                }
                ?>
                <p id="passwordlost"><a href="#">Lost Password?</a></p>
            </div>
        </form>
    </div>
</body>
</html>
