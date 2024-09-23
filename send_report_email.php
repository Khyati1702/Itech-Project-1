<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Path to PHPMailer autoload file
require 'configure.php'; // Include your database connection

session_start();

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

if (!isset($_GET['UserID'])) {
    header('Location: Profile.php');
    exit();
}

// Fetch student details
$UserID = $_GET['UserID'];
$query = $config->prepare("SELECT Name, GoogleEmail FROM users WHERE UserID = ?");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$studentName = $student['Name'];
$studentEmail = $student['GoogleEmail'];

// Generate the report (PDF or Word)
$reportFilePath = 'student_report_' . $studentName . '.pdf';
include('generate_report.php'); // Make sure this generates the PDF report

// Send the email using PHPMailer
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.your-email-server.com'; // Set your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-email@example.com';     // SMTP username
    $mail->Password   = 'your-email-password';        // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('your-email@example.com', 'Your School Name');
    $mail->addAddress($studentEmail, $studentName); // Add student email

    // Attach the report
    $mail->addAttachment($reportFilePath);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Performance Report';
    $mail->Body    = 'Dear ' . $studentName . ',<br><br>Please find attached your performance report.<br><br>Best regards,<br>Your Teacher';

    // Send the email
    $mail->send();
    echo 'Report shared successfully via email!';
} catch (Exception $e) {
    echo "Report could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
