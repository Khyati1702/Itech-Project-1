<?php
session_start();
if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $stage = $_POST['stage'];  // Stage 1 or Stage 2
    $startDate = $_POST['start_date']; // Start Date
    $dueDate = $_POST['due_date']; // Due Date
    $teacherID = $_SESSION['UserID'];

    // Handle file upload
    $filePath = null;
    $uploadDir = 'uploads/assignments/';  // Define the upload directory

    // Check if directory exists, if not, create it
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['assignment_file']['tmp_name'];
        $fileName = $_FILES['assignment_file']['name'];
        $filePath = $uploadDir . basename($fileName);

        if (!move_uploaded_file($fileTmpPath, $filePath)) {
            $filePath = null;  // If upload fails
            echo "File upload failed!";
        }
    }

    // Insert the assignment with the Stage info, Start Date, and Due Date
    $stmt = $config->prepare("INSERT INTO assignments (TeacherID, Stage, Title, Description, FilePath, StartDate, DueDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $teacherID, $stage, $title, $description, $filePath, $startDate, $dueDate);

    if ($stmt->execute()) {
        echo "Assignment created successfully!";
    } else {
        echo "Error creating assignment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment</title>
    <link rel="stylesheet" href="upload_assignment.css">

</head>
<body>
<?php include 'navbar.php'; ?>

    <h1 class="create-assignment-title">Create Assignment</h1>

    <form class="create-assignment-form" action="upload_assignment.php" method="POST" enctype="multipart/form-data">
        <label for="title" class="create-assignment-label">Assignment Title:</label>
        <input type="text" name="title" id="title" class="create-assignment-input" required>

        <label for="description" class="create-assignment-label">Description:</label>
        <textarea name="description" id="description" class="create-assignment-textarea" required></textarea>

        <label for="stage" class="create-assignment-label">Select Stage:</label>
        <select name="stage" id="stage" class="create-assignment-select" required>
            <option value="Stage1">Stage 1</option>
            <option value="Stage2">Stage 2</option>
        </select>

        <label for="start_date" class="create-assignment-label">Start Date:</label>
        <input type="date" name="start_date" id="start_date" class="create-assignment-input" required>

        <label for="due_date" class="create-assignment-label">Due Date:</label>
        <input type="date" name="due_date" id="due_date" class="create-assignment-input" required>

        <label for="assignment_file" class="create-assignment-label">Upload File:</label>
        <input type="file" name="assignment_file" id="assignment_file" class="create-assignment-file">

        <button type="submit" class="create-assignment-btn">Create Assignment</button>
    </form>

<?php include 'footer.php'; ?>
</body>
</html>
