<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $GroupID = $_POST['GroupID'];
    $StudentID = $_POST['StudentID'];

    $insertMember = $config->prepare("
        INSERT INTO group_members (GroupID, StudentID) VALUES (?, ?)
    ");
    $insertMember->bind_param("ii", $GroupID, $StudentID);

    if ($insertMember->execute()) {
        header("Location: manage_group.php?GroupID=" . $GroupID);
        exit();
    } else {
        die('Error adding student to group: ' . $config->error);
    }
}
?>
