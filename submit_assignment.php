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

   
    $filePath = null;
    $uploadDir = 'uploads/submissions/'; 

   
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            die("Failed to create upload directory.");
        }
    }

  
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['assignment_file']['tmp_name'];
        $fileName = basename($_FILES['assignment_file']['name']);
        
      
        $filePath = $uploadDir . $studentID . '_' . time() . '_' . $fileName;


        if (!move_uploaded_file($fileTmpPath, $filePath)) {
            echo "File upload failed!";
            exit();
        }
    } else {
        echo "No file uploaded or upload error.";
        exit();
    }

   
    date_default_timezone_set('Australia/Sydney');
    $submissionDate = date('Y-m-d H:i:s'); 

 
    $stmt = $config->prepare("INSERT INTO AssignmentSubmissions (AssignmentID, StudentID, FilePath, SubmissionDate) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $assignmentID, $studentID, $filePath, $submissionDate);

    if ($stmt->execute()) {
        echo "Assignment submitted successfully!";
    } else {
        echo "Error submitting assignment.";
    }

   
    header('Location: view_assignments.php');
    exit();
}
?>
