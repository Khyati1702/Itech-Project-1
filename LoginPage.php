<?php
session_start();
require 'configure.php';
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$googleClient = new Google_Client();
$googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$googleClient->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
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

            header('Location: Mainpage.php'); 
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
                <a href="?google_login=true" class="gsi-material-button">
                    <div class="gsi-material-button-state"></div>
                    <div class="gsi-material-button-content-wrapper">
                        <div class="gsi-material-button-icon">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                <path fill="none" d="M0 0h48v48H0z"></path>
                            </svg>
                        </div>
                        <span class="gsi-material-button-contents">Sign in with Google</span>
                    </div>
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
