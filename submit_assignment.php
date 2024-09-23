<?php
session_start();
if (!isset($_SESSION['Username']) || ($_SESSION['Role'] != 'Stage1Students' && $_SESSION['Role'] != 'Stage2Students')) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignmentID = $_POST['assignment_id'];
    $studentID = $_SESSION['UserID'];

    // Handle file upload
    $filePath = null;
    $uploadDir = 'uploads/submissions/';  // Define the upload directory

    // Check if directory exists, if not, create it
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            die("Failed to create upload directory.");
        }
    }

    // Check if a file was uploaded without errors
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['assignment_file']['tmp_name'];
        $fileName = basename($_FILES['assignment_file']['name']);
        
        // Generate a unique filename to avoid overwriting
        $filePath = $uploadDir . $studentID . '_' . time() . '_' . $fileName;

        // Move the uploaded file to the submissions directory
        if (!move_uploaded_file($fileTmpPath, $filePath)) {
            echo "File upload failed!";
            exit();
        }
    } else {
        echo "No file uploaded or upload error.";
        exit();
    }

    // Set the timezone to Australia/Sydney before recording submission time
    date_default_timezone_set('Australia/Sydney');
    $submissionDate = date('Y-m-d H:i:s'); // Current date and time with correct timezone

    // Insert the submission details into the AssignmentSubmissions table
    $stmt = $config->prepare("INSERT INTO AssignmentSubmissions (AssignmentID, StudentID, FilePath, SubmissionDate) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $assignmentID, $studentID, $filePath, $submissionDate);

    if ($stmt->execute()) {
        echo "Assignment submitted successfully!";
    } else {
        echo "Error submitting assignment.";
    }

    // Redirect back to the assignments page
    header('Location: view_assignments.php');
    exit();
}
?>
