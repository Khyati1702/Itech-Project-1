<?php
session_start();
if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

// Handle delete request
if (isset($_POST['delete_assignment'])) {
    $assignmentID = $_POST['assignment_id'];

    // First, delete the file associated with the assignment
    $fileQuery = $config->prepare("SELECT FilePath FROM assignments WHERE AssignmentID = ?");
    $fileQuery->bind_param("i", $assignmentID);
    $fileQuery->execute();
    $fileResult = $fileQuery->get_result();
    $fileData = $fileResult->fetch_assoc();

    // Check if FilePath is not empty and exists before attempting to delete the file
    if (!empty($fileData['FilePath']) && file_exists($fileData['FilePath'])) {
        unlink($fileData['FilePath']); // Delete the file from the server
    }

    // Now delete the assignment from the database
    $deleteStmt = $config->prepare("DELETE FROM assignments WHERE AssignmentID = ?");
    $deleteStmt->bind_param("i", $assignmentID);
    
    if ($deleteStmt->execute()) {
        echo "Assignment deleted successfully!";
    } else {
        echo "Error deleting assignment.";
    }
}

// Handle date update request
if (isset($_POST['update_dates'])) {
    $assignmentID = $_POST['assignment_id'];
    $newStartDate = $_POST['start_date'];
    $newDueDate = $_POST['due_date'];

    // Update the dates in the database
    $updateStmt = $config->prepare("UPDATE assignments SET StartDate = ?, DueDate = ? WHERE AssignmentID = ?");
    $updateStmt->bind_param("ssi", $newStartDate, $newDueDate, $assignmentID);

    if ($updateStmt->execute()) {
        echo "Dates updated successfully!";
    } else {
        echo "Error updating dates.";
    }
}

// Fetch assignments for the logged-in teacher
$teacherID = $_SESSION['UserID'];
$stmt = $config->prepare("SELECT AssignmentID, Title, Description, Stage, FilePath, UploadDate, StartDate, DueDate FROM assignments WHERE TeacherID = ?");
$stmt->bind_param("i", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Assignments</title>
    <link rel="stylesheet" href="manage_assignment.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <h1>Your Uploaded Assignments</h1>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Stage</th>
                <th>File</th>
                <th>Start and Due Date</th> <!-- Updated single column for dates -->
                <th>Upload Date</th>
                <th>Actions</th> <!-- Added Actions column -->
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Title']); ?></td>
                <td><?php echo htmlspecialchars($row['Description']); ?></td>
                <td><?php echo htmlspecialchars($row['Stage']); ?></td>
                <td><?php if (!empty($row['FilePath'])): ?>
                    <a href="<?php echo htmlspecialchars($row['FilePath']); ?>" download>Download</a>
                    <?php else: ?>
                    No file uploaded
                    <?php endif; ?>
                </td>
                <td>
                    <!-- Combine Start Date and Due Date -->
                    <form method="POST" action="">
                        <input type="hidden" name="assignment_id" value="<?php echo $row['AssignmentID']; ?>">
                        <input type="date" class="Start_date" name="start_date" value="<?php echo htmlspecialchars($row['StartDate'] ?? ''); ?>" required>
                        <input type="date" class="due_date" name="due_date" value="<?php echo isset($row['DueDate']) ? htmlspecialchars($row['DueDate']) : ''; ?>">
                        <button type="submit" name="update_dates" class="update_dates_btn">Update Dates</button>
                    </form>
                </td>
                <td><?php echo htmlspecialchars($row['UploadDate']); ?></td>
                <td>
                    <!-- Delete button -->
                    <form method="POST" action="">
                        <input type="hidden" name="assignment_id" value="<?php echo $row['AssignmentID']; ?>">
                        <button type="submit" class="delete_assignment_btn" name="delete_assignment" onclick="return confirm('Are you sure you want to delete this assignment?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php include 'footer.php'; ?>
</body>
</html>

