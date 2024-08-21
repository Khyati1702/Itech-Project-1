<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $GroupName = $_POST['GroupName'];
    $TeacherID = $_SESSION['UserID'];
    $CourseID = $_SESSION['CourseID'] ?? null;

    // Validate the input
    if (empty($GroupName)) {
        die('Group name is required.');
    }

    if (empty($CourseID)) {
        die('Course ID is missing!');
    }

    // Prepare the insert query
    $insertGroup = $config->prepare("
        INSERT INTO groups (GroupName, TeacherID, CourseID) VALUES (?, ?, ?)
    ");
    if ($insertGroup === false) {
        die('Prepare failed: ' . htmlspecialchars($config->error));
    }

    // Bind parameters
    $bind = $insertGroup->bind_param("sii", $GroupName, $TeacherID, $CourseID);
    if ($bind === false) {
        die('Bind failed: ' . htmlspecialchars($insertGroup->error));
    }

    // Execute the query
    $exec = $insertGroup->execute();
    if ($exec === false) {
        die('Execute failed: ' . htmlspecialchars($insertGroup->error));
    }

    // Redirect on success
    header('Location: Profile.php');
    exit();
} else {
    die('Invalid request method.');
}
?>
