<?php
session_start();
require 'configure.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is Teacher
if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

$loggedInUserID = $_SESSION['UserID'];

// Fetch teachers (including the current teacher)
$teacherQuery = "SELECT UserID, Name, Role, GoogleEmail FROM users WHERE Role = 'Teacher'";
$teacherStmt = $config->prepare($teacherQuery);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();

if (!$teacherResult) {
    die("Error fetching teachers: " . $config->error);
}

// Fetch students (excluding the 'Teacher' role)
$studentQuery = "SELECT UserID, Name, Role, GoogleEmail FROM users WHERE Role != 'Teacher'";
$studentStmt = $config->prepare($studentQuery);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if (!$studentResult) {
    die("Error fetching students: " . $config->error);
}

// Handle form submission for adding new users, updating roles, deleting, or promoting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Handle adding a new user
        $googleEmail = $_POST['google_email'];
        $role = $_POST['role'];

        // Automatically assign Course based on Role
        if ($role == 'Stage1Students') {
            $course = 'SACE Stage 1';
        } elseif ($role == 'Stage2Students') {
            $course = 'SACE Stage 2';
        } elseif ($role == 'Teacher') {
            $course = null; // Teachers may not have a course assigned
        } else {
            $course = null;
        }

        // Set CourseID to 1 for everyone
        $courseID = 1;

        // Check if the email already exists
        $checkEmailQuery = $config->prepare("SELECT * FROM users WHERE GoogleEmail = ?");
        $checkEmailQuery->bind_param("s", $googleEmail);
        $checkEmailQuery->execute();
        $emailResult = $checkEmailQuery->get_result();

        if ($emailResult->num_rows > 0) {
            $errorMessage = "This Google email is already registered.";
        } else {
            // Insert the new user with the provided information
            $insertUserQuery = $config->prepare("INSERT INTO users (GoogleEmail, Role, Course, CourseID) VALUES (?, ?, ?, ?)");
            $insertUserQuery->bind_param("sssi", $googleEmail, $role, $course, $courseID);

            if ($insertUserQuery->execute()) {
                $successMessage = "User added successfully!";
            } else {
                $errorMessage = "Failed to add user.";
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        // Handle user deletion
        $userId = $_POST['user_id'];
        $deleteQuery = $config->prepare("DELETE FROM users WHERE UserID = ?");
        $deleteQuery->bind_param("i", $userId);

        if ($deleteQuery->execute()) {
            $successMessage = "User deleted successfully!";
        } else {
            $errorMessage = "Failed to delete user.";
        }

    } elseif (isset($_POST['update_role'])) {
        // Handle role update
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];

        // Automatically assign Course based on Role
        if ($newRole == 'Stage1Students') {
            $newCourse = 'SACE Stage 1';
        } elseif ($newRole == 'Stage2Students') {
            $newCourse = 'SACE Stage 2';
        } elseif ($newRole == 'Teacher') {
            $newCourse = null; // Teachers may not have a course assigned
        } else {
            $newCourse = null;
        }

        // Set CourseID to 1 for everyone
        $newCourseID = 1;

        $updateQuery = $config->prepare("UPDATE users SET Role = ?, Course = ?, CourseID = ? WHERE UserID = ?");
        if (!$updateQuery) {
            die("Prepare failed: (" . $config->errno . ") " . $config->error);
        }
        $updateQuery->bind_param("ssii", $newRole, $newCourse, $newCourseID, $userId);

        if ($updateQuery->execute()) {
            $successMessage = "Role updated successfully!";
        } else {
            $errorMessage = "Failed to update Role.";
        }
    } elseif (isset($_POST['promote_user'])) {
        // Promote user to Stage 2
        $userId = $_POST['user_id'];
        $successMessage = promoteStudentToStage2($userId, $config);
    }

    // Refresh page to display updated roles
    header("Location: admin_tool.php");
    exit();
}

// Function to promote a student to Stage 2 and archive Stage 1 grades
function promoteStudentToStage2($UserID, $config) {

    // Step 1: Archive Stage 1 grades
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

    if (!$archiveGradesQuery) {
        die("Prepare failed for archiving grades: (" . $config->errno . ") " . $config->error);
    }

    $archiveGradesQuery->bind_param("i", $UserID);
    if (!$archiveGradesQuery->execute()) {
        die("Execute failed for archiving grades: (" . $archiveGradesQuery->errno . ") " . $archiveGradesQuery->error);
    }

    // Step 2: (Removed deletion of Stage 1 grades from 'gradings' table)

    // Step 3: End Stage 1 in student_stages
    $endStage1Query = $config->prepare("UPDATE student_stages SET EndDate = CURDATE() WHERE UserID = ? AND Stage = 'Stage1Students' AND EndDate IS NULL");
    if (!$endStage1Query) {
        die("Prepare failed for ending Stage 1: (" . $config->errno . ") " . $config->error);
    }
    $endStage1Query->bind_param("i", $UserID);
    if (!$endStage1Query->execute()) {
        die("Execute failed for ending Stage 1: (" . $endStage1Query->errno . ") " . $endStage1Query->error);
    }

    // Step 4: Insert new record for Stage 2 in student_stages
    $insertStage2Query = $config->prepare("INSERT INTO student_stages (UserID, Stage, StartDate) VALUES (?, 'Stage2Students', CURDATE())");
    if (!$insertStage2Query) {
        die("Prepare failed for inserting Stage 2: (" . $config->errno . ") " . $config->error);
    }
    $insertStage2Query->bind_param("i", $UserID);
    if (!$insertStage2Query->execute()) {
        die("Execute failed for inserting Stage 2: (" . $insertStage2Query->errno . ") " . $insertStage2Query->error);
    }

    // Step 5: Update the users table to reflect Stage 2
    $updateUserStage = $config->prepare("UPDATE users SET Role = 'Stage2Students', Course = 'SACE Stage 2' WHERE UserID = ?");
    if (!$updateUserStage) {
        die("Prepare failed for updating user role: (" . $config->errno . ") " . $config->error);
    }
    $updateUserStage->bind_param("i", $UserID);
    if (!$updateUserStage->execute()) {
        die("Execute failed for updating user role: (" . $updateUserStage->errno . ") " . $updateUserStage->error);
    }

    return "Student successfully promoted to Stage 2 and Stage 1 grades archived.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Tools</title>
    <link rel="stylesheet" href="admin_tool.css">
</head>
<body>
<?php include 'navbar.php';?>

<main id="admin-main">
    <h1 id="admin-title">Admin Tool - User Management</h1>

    <?php if (isset($successMessage)) : ?>
        <p class="success-message"><?php echo $successMessage; ?></p>
    <?php elseif (isset($errorMessage)) : ?>
        <p class="error-message"><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <!-- Section to Add New Users -->
    <section id="add-user-section" class="admin-tool-section">
        <h2 id="add-user-title">Add New User</h2>
        <form method="POST" action="admin_tool.php" class="add-user-form">
            <div class="form-group">
                <label for="google_email">Google Email:</label>
                <input type="email" name="google_email" id="google_email" required placeholder="Enter Google Email">
            </div>
            <div class="form-group">
                <label for="role">Assign Role:</label>
                <select name="role" id="role" onchange="confirmRoleChange(this)">
                    <option value="Teacher">Teacher</option>
                    <option value="Stage1Students">Stage 1 Student</option>
                    <option value="Stage2Students">Stage 2 Student</option>
                </select>
            </div>
            <button type="submit" name="add_user"  class="assign-button">Add User</button>
        </form>
    </section>

    <!-- Section to Manage Teachers -->
    <section id="manage-teachers-section" class="admin-tool-section">
        <h2 id="manage-teachers-title">Manage Teachers</h2>
        <div class="table-responsive">
            <table id="teacher-table" class="user-table">
                <thead>
                    <tr>
                        <th>Google Email</th>
                        <th>Name</th>
                        <th>Current Role</th>
                        <th>Assign New Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Re-fetch teachers to get the latest data
                    $teacherStmt->execute();
                    $teacherResult = $teacherStmt->get_result();
                    while ($row = $teacherResult->fetch_assoc()) :
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['GoogleEmail'] ?? 'Not provided'); ?></td>
                            <td><?php echo htmlspecialchars($row['Name'] ?? 'Not provided'); ?></td>
                            <td><?php echo htmlspecialchars($row['Role'] ?? 'Not provided'); ?></td>
                            <td>
                                <form method="POST" action="admin_tool.php" class="update-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['UserID']; ?>">

                                    <!-- Role Dropdown -->
                                    <select name="role" class="role-select" onchange="confirmRoleChange(this)">
                                        <option value="Teacher" <?php if ($row['Role'] == 'Teacher') echo 'selected'; ?>>Teacher</option>
                                        <option value="Stage1Students" <?php if ($row['Role'] == 'Stage1Students') echo 'selected'; ?>>Stage 1 Student</option>
                                        <option value="Stage2Students" <?php if ($row['Role'] == 'Stage2Students') echo 'selected'; ?>>Stage 2 Student</option>
                                    </select>

                                    <button type="submit" name="update_role" class="assign-button">Update Role</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="admin_tool.php" class="action-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['UserID']; ?>">
                                    <button type="submit" name="delete_user" class="delete-button" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Section to Manage Students -->
    <section id="manage-students-section" class="admin-tool-section">
        <h2 id="manage-students-title">Manage Students</h2>
        <div class="table-responsive">
            <table id="student-table" class="user-table">
                <thead>
                    <tr>
                        <th>Google Email</th>
                        <th>Name</th>
                        <th>Current Role</th>
                        <th>Assign New Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Re-fetch students to get the latest data
                    $studentStmt->execute();
                    $studentResult = $studentStmt->get_result();
                    while ($row = $studentResult->fetch_assoc()) :
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['GoogleEmail'] ?? 'Not provided'); ?></td>
                            <td><?php echo htmlspecialchars($row['Name'] ?? 'Not provided'); ?></td>
                            <td><?php echo htmlspecialchars($row['Role'] ?? 'Not provided'); ?></td>
                            <td>
                                <form method="POST" action="admin_tool.php" class="update-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['UserID']; ?>">

                                    <!-- Role Dropdown -->
                                    <select name="role" class="role-select" onchange="confirmRoleChange(this)">
                                        <option value="Stage1Students" <?php if ($row['Role'] == 'Stage1Students') echo 'selected'; ?>>Stage 1 Student</option>
                                        <option value="Stage2Students" <?php if ($row['Role'] == 'Stage2Students') echo 'selected'; ?>>Stage 2 Student</option>
                                        <option value="Teacher">Teacher</option>
                                    </select>

                                    <button type="submit" name="update_role" class="assign-button">Update Role</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="admin_tool.php" class="action-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['UserID']; ?>">
                                    <?php if ($row['Role'] == 'Stage1Students') : ?>
                                        <button type="submit" name="promote_user" class="promote-button">Promote to Stage 2</button>
                                    <?php endif; ?>
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

<!-- JavaScript to confirm role change -->
<script>
function confirmRoleChange(selectElement) {
    var previousValue = selectElement.getAttribute('data-previous-value') || selectElement.defaultValue;
    var newValue = selectElement.value;

    if (previousValue !== newValue) {
        var confirmChange = confirm('Are you sure you want to change the role from "' + previousValue + '" to "' + newValue + '"?');
        if (!confirmChange) {
            // Revert to previous value
            selectElement.value = previousValue;
        } else {
            // Update the previous value attribute
            selectElement.setAttribute('data-previous-value', newValue);
        }
    }
}
</script>

</body>
</html>
