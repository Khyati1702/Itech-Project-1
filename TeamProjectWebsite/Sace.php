<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="Sace.css">
    <link rel="stylesheet" href="SaceColor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php
require_once 'vendor/autoload.php';

// init configuration
$clientID = '704453595817-2qrd8v8c2rhgl75qvv8iumu11864mo22.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/DraftWebsite/Mainpage.html';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");
$googleLoginUrl = $client->createAuthUrl();

// authenticate code from Google OAuth Flow
if (isset($_GET['code'])) {
  $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
  $client->setAccessToken($token['access_token']);

  // get profile info
  $google_oauth = new Google_Service_Oauth2($client);
  $google_account_info = $google_oauth->userinfo->get();
  $email =  $google_account_info->email;
  $name =  $google_account_info->name;

  // now you can use this profile info to create account in your website and make user logged in.
} else {
?>

    <div class="container" id="Login">
       <div class="toplogin"><img src="Eximages/Sace_logo.png" alt="SACE Logo">
        <h1 id="verticleline"></h1>
        <h1 id="sacename"><i>SACE Portal</i></h1>
    </div>
    <hr id="horizontalline">
        
       
         <h1 id="logintitle">Login</h1>

         <form action="" method="post">
        <div class="forumtext">
            <i class="fas fa-envelope"></i>
            <input type="username" id="username" name="username" placeholder="USERNAME" required> 
        </div>
        <div class="forumtext">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="PASSWORD" required>
            <i class="fas fa-sign-in"></i>  
            <input type="submit" id="submitbtn" name="login" value="Log in">
            <p id="passwordlost">
                <a href="#">Lost Password?</a>
            </p>
            <p class="or">---------OR----------</p>
         <div class="googleicon">
            <a href="<?php echo $client->createAuthUrl() ?>"><i class="fab fa-google">  
            </i><b><p id="googlelink">Login With Google </p></b> </a>  
        </div> 
        </div>
        <?php } ?>
         
    </form>


    
</div>
<!--<div id="googlebtn">
            <input type="submit" id="googlebtn" name="googlebutton" value="Sign up with google">
            <i class="fab fa-google"></i>
        </div>-->
         <!--
             <div class="container" id="Login">
        <h1 id="verticleline"></h1>
        <h1>SACE Portal</h1>
         <h1 id="logintitle">Login</h1>
         <form action="" method="post">
            
            
            <form action="" method="post">
           <div class="nametext">
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" placeholder="Ichiro" required> 
                <i class="fas fa-user"></i>  
            </div>
            <div class="forumtext">
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" placeholder="Suzuki" required> 
                <i class="fas fa-user"></i>
            </div>
            <div class="forumtext">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="IchiroSuzuki.gmail.com" required> 
                <i class="fas fa-envelope"></i>
            </div>
            <div class="forumtext">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="************" required> 
                <i class="fas fa-lock"></i>
            </div>

            <div class="submitbtn">
                <input type="submit" id="button" name="signup" value="Sign up">
            </div>
            <p id="horizontalline"></p>

           <div id="googlebtn">
                <input type="submit" id="googlebtn" name="googlebutton" value="Sign up with google">
                <i class="fab fa-google"></i>
            </div>
            <div class="Alreadyacc">

                <p>Aklready have an account</p>
                <button id="signinbtn">Sign In</button>
                </div>
            </forum>
         </div>-->
</body>
</html>