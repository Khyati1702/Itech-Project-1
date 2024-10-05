<?php
session_start();
require 'configure.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is Admin or Teacher
if (!isset($_SESSION['Role']) || ($_SESSION['Role'] != 'Admin' && $_SESSION['Role'] != 'Teacher')) {
    header('Location: LoginPage.php');
    exit();
}

$loggedInUserID = $_SESSION['UserID']; 


$query = "SELECT UserID, Username, Role, GoogleEmail, Course FROM users WHERE GoogleID IS NOT NULL AND Role != 'Admin' AND UserID != ?";
$stmt = $config->prepare($query);
$stmt->bind_param("i", $loggedInUserID); 
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error fetching users: " . $config->error);
}

// Function to promote a student to Stage 2 and archive Stage 1 grades
function promoteStudentToStage2($UserID, $config) {

    $archiveGradesQuery = $config->prepare("
        INSERT INTO stage1_grades_archive (
            StudentID, TeacherID, Interaction, Text_Analysis, Text_Production, 
            Investigation_Task_Part_A, Investigation_Task_Part_B, GradingTimestamp, 
            Comments, Old_teacherNote, Comments_Interaction, Comments_Text_Analysis, 
            Comments_Text_Production, Comments_Investigation_Task_Part_A, 
            Comments_Investigation_Task_Part_B, Stage)
        SELECT 
            StudentID, TeacherID, Interaction, Text_Analysis, Text_Production, 
            Investigation_Task_Part_A, Investigation_Task_Part_B, GradingTimestamp, 
            Comments, TeacherNote AS Old_teacherNote, Comments_Interaction, Comments_Text_Analysis, 
            Comments_Text_Production, Comments_Investigation_Task_Part_A, 
            Comments_Investigation_Task_Part_B, 'Old_stage1Student'
        FROM gradings
        WHERE StudentID = ? AND Stage = 'Stage1Students'");
    $archiveGradesQuery->bind_param("i", $UserID);
    $archiveGradesQuery->execute();

    // Step 2: End Stage 1 in `student_stages`
    $endStage1Query = $config->prepare("UPDATE student_stages SET EndDate = CURDATE() WHERE UserID = ? AND Stage = 'Stage1Students' AND EndDate IS NULL");
    $endStage1Query->bind_param("i", $UserID);
    $endStage1Query->execute();

    // Step 3: Insert new record for Stage 2 in `student_stages`
    $insertStage2Query = $config->prepare("INSERT INTO student_stages (UserID, Stage, StartDate) VALUES (?, 'Stage2Students', CURDATE())");
    $insertStage2Query->bind_param("i", $UserID);
    $insertStage2Query->execute();

    // Step 4: Update the `users` table to reflect Stage 2
    $updateUserStage = $config->prepare("UPDATE users SET Role = 'Stage2Students', Course = 'SACE Stage 2' WHERE UserID = ?");
    $updateUserStage->bind_param("i", $UserID);
    $updateUserStage->execute();

    return "Student successfully promoted to Stage 2 and Stage 1 grades archived.";
}

// Handle form submission for role and course updates, deletion, or promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        // Handle user deletion
        $userId = $_POST['user_id'];
        $deleteQuery = $config->prepare("DELETE FROM users WHERE UserID = ?");
        $deleteQuery->bind_param("i", $userId);
        
        if ($deleteQuery->execute()) {
            $successMessage = "User deleted successfully!";
        } else {
            $errorMessage = "Failed to delete user.";
        }
        
    } elseif (isset($_POST['update_role_course'])) {
        // Handle role and course update
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];
        $newCourse = $_POST['course'];

        $updateQuery = $config->prepare("UPDATE users SET Role = ?, Course = ? WHERE UserID = ?");
        if (!$updateQuery) {
            die("Prepare failed: (" . $config->errno . ") " . $config->error);
        }
        $updateQuery->bind_param("ssi", $newRole, $newCourse, $userId);
        
        if ($updateQuery->execute()) {
            $successMessage = "Role and Course updated successfully!";
        } else {
            $errorMessage = "Failed to update Role or Course.";
        }
    } elseif (isset($_POST['promote_user'])) {
        // Promote user to Stage 2
        $userId = $_POST['user_id'];
        $successMessage = promoteStudentToStage2($userId, $config);
    }

    // Refresh page to display updated roles and courses
    header("Location: admin_tool.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tools</title>
    <link rel="stylesheet" href="admin_tool.css">
</head>
<body>
<?php include 'navbar.php';?>

<main>
    <h1>Admin Tool - Role and Course Assignment</h1>

    <?php if (isset($successMessage)) : ?>
        <p class="success-message"><?php echo $successMessage; ?></p>
    <?php elseif (isset($errorMessage)) : ?>
        <p class="error-message"><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <section class="admin-tool-section">
        <div class="table-responsive">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Google Email</th>
                        <th>Current Role</th>
                        <th>Current Course</th>
                        <th>Assign New Role & Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['GoogleEmail']); ?></td>
                            <td><?php echo htmlspecialchars($row['Role']); ?></td>
                            <td><?php echo htmlspecialchars($row['Course']); ?></td>
                            <td>
                                <form method="POST" action="admin_tool.php">
                                    <input type="hidden" name="user_id" value="<?php echo $row['UserID']; ?>">

                                    <!-- Role Dropdown -->
                                    <select name="role">
                                        <option value="Teacher" <?php if ($row['Role'] == 'Teacher') echo 'selected'; ?>>Teacher</option>
                                        <option value="Stage1Students" <?php if ($row['Role'] == 'Stage1Students') echo 'selected'; ?>>Stage 1 Student</option>
                                        <option value="Stage2Students" <?php if ($row['Role'] == 'Stage2Students') echo 'selected'; ?>>Stage 2 Student</option>
                                    </select>

                                    <!-- Course Dropdown -->
                                    <select name="course">
                                        <option value="SACE Stage 1" <?php if ($row['Course'] == 'SACE Stage 1') echo 'selected'; ?>>SACE Stage 1</option>
                                        <option value="SACE Stage 2" <?php if ($row['Course'] == 'SACE Stage 2') echo 'selected'; ?>>SACE Stage 2</option>
                                    </select>

                                    <button type="submit" name="update_role_course" class="assign-button">Update Role & Course</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="admin_tool.php">
                                    <input type="hidden" name="user_id" value="<?php echo $row['UserID']; ?>">
                                    <button type="submit" name="promote_user" class="promote-button">Promote to Stage 2</button>
                                    <button type="submit" name="delete_user" class="delete-button" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
