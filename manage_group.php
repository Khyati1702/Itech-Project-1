<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

$GroupID = $_GET['GroupID'];

// Fetch group details
$groupQuery = $config->prepare("SELECT GroupName FROM groups WHERE GroupID = ?");
$groupQuery->bind_param("i", $GroupID);
$groupQuery->execute();
$groupResult = $groupQuery->get_result();
$group = $groupResult->fetch_assoc();

// Fetch students in the group
$studentsQuery = $config->prepare("
    SELECT users.UserID, users.Username FROM group_members
    JOIN users ON group_members.StudentID = users.UserID
    WHERE group_members.GroupID = ?
");
$studentsQuery->bind_param("i", $GroupID);
$studentsQuery->execute();
$studentsResult = $studentsQuery->get_result();

// Fetch students not in the group
$allStudentsQuery = $config->prepare("
    SELECT UserID, Username FROM users 
    WHERE Role IN ('Stage1Students', 'Stage2Students') AND CourseID = ? AND UserID NOT IN (
        SELECT StudentID FROM group_members WHERE GroupID = ?
    )
");
$allStudentsQuery->bind_param("ii", $_SESSION['CourseID'], $GroupID);
$allStudentsQuery->execute();
$allStudentsResult = $allStudentsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Group: <?php echo htmlspecialchars($group['GroupName']); ?></title>
    <link rel="stylesheet" href="Profile.css">
    <link rel="stylesheet" href="colors.css">
    
</head>
<body>
<?php include 'navbar.php'; ?>

<main>
    <h1>Manage Group: <?php echo htmlspecialchars($group['GroupName']); ?></h1>

    <!-- Students in the Group -->
    <h2>Students in Group</h2>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Remove from Group</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['Username']); ?></td>
                    <td><a href="remove_from_group.php?GroupID=<?php echo $GroupID; ?>&StudentID=<?php echo $student['UserID']; ?>" class="btn btn-danger">Remove</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Add Students to the Group -->
    <h2>Add Students to Group</h2>
    <div class="group-input-container">
    <form action="add_to_group.php" method="POST">
        <input type="hidden" name="GroupID" value="<?php echo $GroupID; ?>">
        <select name="StudentID" required>
            <option value="">Select a Student</option>
            <?php while ($student = $allStudentsResult->fetch_assoc()): ?>
                <option value="<?php echo $student['UserID']; ?>"><?php echo htmlspecialchars($student['Username']); ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Add to Group</button>
    </form>
</div>

</main>

<?php include 'footer.php'; ?>


<script>
    function toggleMenu() {
        const nav = document.querySelector('.main-nav');
        nav.classList.toggle('active');
        console.log('Menu toggled.');
    }
</script>
</body>
</html>
