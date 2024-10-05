<?php
session_start();
require 'configure.php';
require_once 'vendor/autoload.php';
use Dotenv\Dotenv;

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
    // Update the last activity time stamp
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

    // If user exists, log them in
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // If username is missing, let's generate one and update it
        if (empty($user['Username'])) {
            $username = strtolower(str_replace(' ', '', $name)); 
            $updateUsername = $config->prepare("UPDATE users SET Username = ? WHERE UserID = ?");
            $updateUsername->bind_param("si", $username, $user['UserID']);
            $updateUsername->execute();
            $user['Username'] = $username; 
        }

        $_SESSION['Username'] = $user['Username'];
        $_SESSION['Role'] = $user['Role'];
        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['CourseID'] = $user['CourseID'];

        // Assign CourseID = 1 if it's missing
        if (empty($user['CourseID'])) {
            $updateCourseID = $config->prepare("UPDATE users SET CourseID = 1 WHERE UserID = ?");
            $updateCourseID->bind_param("i", $user['UserID']);
            $updateCourseID->execute();
            $_SESSION['CourseID'] = 1; 
        }

        // Redirect based on role
        if ($user['Role'] == 'Admin') {
            header('Location: Mainpage.php');
        } elseif ($user['Role'] == 'Teacher') {
            header('Location: Mainpage.php');
        } else {
            header('Location: Mainpage.php'); 
        }
        exit();
    } else {
       
        $role = 'Stage1Students'; 
        $course = 'Stage 1'; 
        $username = strtolower(str_replace(' ', '', $name)); 
        
       
        $googlePasswordHash = password_hash($googleId, PASSWORD_BCRYPT);
        
  
        $insert = $config->prepare("INSERT INTO users (GoogleID, GoogleEmail, Name, Username, Role, GooglePasswordHash, CourseID, Course) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        $insert->bind_param("sssssss", $googleId, $email, $name, $username, $role, $googlePasswordHash, $course);
        $insert->execute();

        // Fetch the newly inserted user
        $newUserQuery = $config->prepare("SELECT * FROM users WHERE GoogleEmail = ?");
        $newUserQuery->bind_param("s", $email);
        $newUserQuery->execute();
        $newUserResult = $newUserQuery->get_result();
        $newUser = $newUserResult->fetch_assoc();

        $_SESSION['Username'] = $newUser['Username'] ?? $name; 
        $_SESSION['Role'] = $newUser['Role'];
        $_SESSION['UserID'] = $newUser['UserID'];
        $_SESSION['CourseID'] = $newUser['CourseID'];

        // Redirect based on role
        if ($newUser['Role'] == 'Admin') {
            header('Location: Mainpage.php');
        } elseif ($newUser['Role'] == 'Teacher') {
            header('Location: Mainpage.php');
        } else {
            header('Location: Mainpage.php'); 
        }
        exit();
    }
}
?>

