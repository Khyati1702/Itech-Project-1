<?php
require 'configure.php';
require_once 'vendor/autoload.php';
session_start();

// Use environment variables for sensitive information
$clientID = '704453595817-2qrd8v8c2rhgl75qvv8iumu11864mo22.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/DraftWebsite/Mainpage.php'; 


// Create Google Client
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// Google Login URL
$googleLoginUrl = $client->createAuthUrl();

// Handle OAuth response
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        echo "Error fetching token: " . $token['error'];
        exit();
    }
    $client->setAccessToken($token['access_token']);

    // Get profile info
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    $email = $google_account_info->email;
    $name = $google_account_info->name;

    // Check if user exists in the database
    $select = "SELECT * FROM users WHERE GoogleEmail='$email'";
    $query = mysqli_query($config, $select);
    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        $_SESSION['Username'] = $user['Username'];
        $_SESSION['Role'] = $user['Role'];
        $_SESSION['UserID'] = $user['UserID']; // Ensure UserID is set in session
        header('Location: Mainpage.php');
        exit();
    } else {
        // New user, insert into database
        $insert = "INSERT INTO users (Username, GoogleEmail, Role) VALUES ('$name', '$email', 'Student')";
        if (mysqli_query($config, $insert)) {
            $newUserID = mysqli_insert_id($config); // Get the new UserID
            $_SESSION['Username'] = $name;
            $_SESSION['Role'] = 'Student';
            $_SESSION['UserID'] = $newUserID;
            header('Location: Mainpage.php');
            exit();
        } else {
            echo "Error: " . mysqli_error($config);
        }
    }
}

if (isset($_POST['submit_btn'])) {
    $email = $_POST['username'];
    $loginpass = $_POST['password'];
    $select = "SELECT * FROM users WHERE Username='$email' AND Password='$loginpass'";
    $query = mysqli_query($config, $select);
    $row = mysqli_num_rows($query);

    if ($row == 1) {
        $user = mysqli_fetch_array($query);
        $_SESSION['Username'] = $user['Username'];
        $_SESSION['Role'] = $user['Role'];
        $_SESSION['UserID'] = $user['UserID']; // Ensure UserID is set in session
        header('Location: Mainpage.php');
        exit();
    } else {
        $_SESSION['error'] = "<div style='background-color:var(--primary-color); color:black; padding:5px; margin:0px 150px 15px 150px; border:2px white solid'>Invalid Username or Password</div>";
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
    <style>
        .googleicon { margin-bottom: 20px; }
        .or { margin-bottom: 20px; }
    </style>

    <div class="container" id="Login">
        <div class="toplogin">
            <img src="Images/REAL_SACE.png" alt="SACE Logo">
            <h1 id="verticleline"></h1>
            <h1 id="sacename">SACE Portal</h1>
        </div>
        <hr id="horizontalline">

        <h1 id="logintitle">Login</h1>
        <form action="" method="POST">
            <div class="googleicon">
                <a href="<?php echo $googleLoginUrl; ?>"><i class="fab fa-google"></i><b><p id="googlelink">Login With Google</p></b></a>
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
                    $errormsg = $_SESSION['error'];
                    echo $errormsg;
                    unset($_SESSION['error']);
                }
                ?>
                <p id="passwordlost"><a href="#">Lost Password?</a></p>
            </div>
        </form>
    </div>
</body>
</html>
