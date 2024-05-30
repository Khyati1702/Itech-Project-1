<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Student') {
    echo json_encode([]);
    exit();
}

$UserID = $_SESSION['UserID'];

$query = $config->prepare("SELECT Message, NotificationTimestamp FROM notifications WHERE UserID = ? ORDER BY NotificationTimestamp DESC");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
?>
