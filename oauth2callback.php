<?php
session_start();
require 'configure.php';
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

//This code handles the google callback, uses env variable for security.

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


define('SESSION_TIMEOUT', 1800);

if (isset($_SESSION['Username'])) {
    if (isset($_SESSION['last_activity'])) {
        $session_duration = time() - $_SESSION['last_activity'];

        // If the session has timed out, log the user out
        if ($session_duration > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header('Location: LoginPage.php?timeout=1');
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
}

$googleClient = new Google_Client();
$googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$googleClient->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$googleClient->addScope("email");
$googleClient->addScope("profile");

// Handle the callback from Google
if (isset($_GET['code'])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        echo 'Error fetching token: ' . $token['error'];
        exit();
    }

    $googleClient->setAccessToken($token['access_token']);

    // Get user profile information from Google
    $googleOauth = new Google_Service_Oauth2($googleClient);
    $googleAccountInfo = $googleOauth->userinfo->get();
    $email = $googleAccountInfo->email;
    $name = $googleAccountInfo->name;
    $googleId = $googleAccountInfo->id;

    // Check if user exists in the database
    $query = $config->prepare("SELECT * FROM users WHERE GoogleEmail = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        session_regenerate_id(true); 

        // Update the user's Name, GoogleID, and Username if not already set
        $username = !empty($user['Username']) ? $user['Username'] : strtolower(str_replace(' ', '', $name));

        $updateUser = $config->prepare("UPDATE users SET Name = ?, GoogleID = ?, Username = ?, CourseID = 1 WHERE UserID = ?");
        $updateUser->bind_param("sssi", $name, $googleId, $username, $user['UserID']);
        $updateUser->execute();

        // Set session variables
        $_SESSION['Username'] = $username;
        $_SESSION['Role'] = $user['Role'];
        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['CourseID'] = $user['CourseID'];

        // Redirect to Mainpage
        header('Location: Mainpage.php');
        exit();
    } else {
        // If user does not exist, deny access
        $_SESSION['error'] = "Access denied. Please contact the administrator.";
        header('Location: LoginPage.php');
        exit();
    }
}
?>
