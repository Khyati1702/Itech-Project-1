<?php
session_start();
require 'configure.php';

//This code hanles the form logic and encrypting the password for security.

if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if the user is not logged in
    header('Location: LoginPage.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['UserID'];
    $newUsername = $_POST['username'];
    $newPassword = $_POST['password'];

    // Check if the new username is already taken by another user
    $checkUsernameQuery = $config->prepare("SELECT * FROM users WHERE Username = ? AND UserID != ?");
    $checkUsernameQuery->bind_param("si", $newUsername, $userId);
    $checkUsernameQuery->execute();
    $usernameResult = $checkUsernameQuery->get_result();

    if ($usernameResult->num_rows > 0) {
        $_SESSION['message'] = "The username is already taken. Please choose a different one.";
        header('Location: setup_traditional_login_form.php');
        exit();
    }

    // Prepare to update username ot password
    $updateQuery = null;
    if (!empty($newPassword)) {
        // Check if the password meets the length and character requirements
        if (strlen($newPassword) < 8 || !preg_match('/[\W_]/', $newPassword)) {
            $_SESSION['message'] = "Password must be at least 8 characters long and include at least one special character.";
            header('Location: setup_traditional_login_form.php');
            exit();
        }

        // Hash the new password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateQuery = $config->prepare("UPDATE users SET Username = ?, PasswordHash = ? WHERE UserID = ?");
        $updateQuery->bind_param("ssi", $newUsername, $passwordHash, $userId);
    } else {
        // Only update the username
        $updateQuery = $config->prepare("UPDATE users SET Username = ? WHERE UserID = ?");
        $updateQuery->bind_param("si", $newUsername, $userId);
    }

    $updateQuery->execute();

    if ($updateQuery->affected_rows > 0) {
        $_SESSION['Username'] = $newUsername; 
        $_SESSION['message'] = $hasTraditionalLogin ? " Login details updated successfully." : "Login setup successfully.";
    } else {
        $_SESSION['message'] = "An error occurred. Please try again.";
    }

    header('Location: setup_traditional_login_form.php');
    exit();
}
?>
