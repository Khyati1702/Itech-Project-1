<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

$GroupID = $_GET['GroupID'];
$StudentID = $_GET['StudentID'];

$deleteMember = $config->prepare("
    DELETE FROM group_members WHERE GroupID = ? AND StudentID = ?
");
$deleteMember->bind_param("ii", $GroupID, $StudentID);

if ($deleteMember->execute()) {
    header("Location: manage_group.php?GroupID=" . $GroupID);
    exit();
} else {
    die('Error removing student from group: ' . $config->error);
}
?>
