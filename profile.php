<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

$username = $_SESSION['Username'];
$role = $_SESSION['Role'];

// Fetch the CourseID for the logged-in user (either a teacher or a student)
$courseQuery = $config->prepare("SELECT CourseID FROM users WHERE Username = ?");
$courseQuery->bind_param("s", $username);
$courseQuery->execute();
$courseResult = $courseQuery->get_result();
$courseData = $courseResult->fetch_assoc();

$CourseID = $courseData['CourseID'] ?? null;

if (!$CourseID) {
    die('Course ID is missing!');
}

if ($role == 'Teacher') {
    // Fetch students in the same course
    $query = "SELECT UserID, Username, Role FROM users WHERE Role IN ('Stage1Students', 'Stage2Students') AND CourseID = ?";
    $stmt = $config->prepare($query);
    $stmt->bind_param("i", $CourseID);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch groups for the teacher
    $groupQuery = $config->prepare("
        SELECT groups.GroupID, groups.GroupName, COUNT(group_members.StudentID) AS StudentCount 
        FROM groups
        LEFT JOIN group_members ON groups.GroupID = group_members.GroupID
        WHERE groups.TeacherID = ?
        GROUP BY groups.GroupID
    ");
    $groupQuery->bind_param("i", $_SESSION['UserID']);
    $groupQuery->execute();
    $groupResult = $groupQuery->get_result();
} else {
    // Fetch only the current user's details
    $query = "SELECT UserID, Username, Role FROM users WHERE Username = ?";
    $stmt = $config->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
}

if (!$result) {
    die('Error in query: ' . mysqli_error($config));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profiles</title>
    <link rel="stylesheet" href="Profile.css">
    <link rel="stylesheet" href="colors.css">
</head>
<body>
<header class="main-header">
    <div class="logo-container">
        <img class="header-title" src="Images/Real_logo.png" alt="SACE Portal Logo">
        <span class="header-title">SACE Portal</span>
    </div>
    <div class="nav-container">
        <span class="menu-toggle" onclick="toggleMenu()">☰</span>
        <nav class="main-nav">
            <a href="Mainpage.php">Home</a>
            <a href="assignment.php">Grading</a>
            <a href="Profile.php">Students</a>
            <a href="#">Contact</a>
            <a href="#">Help</a>
        </nav>
        <div class="search-container">
            <input type="search" placeholder="Search">
            <form action="logout.php" method="post">
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
    </div>
</header>

<main>
    <h1>Students Enrolled</h1>
   <!--<div class="generate-reports-container">
        <a href="generate_all_reports.php?CourseID=<?php echo $CourseID; ?>" class="btn btn-primary">Download All Student Reports</a>
    </div>-->
    <table>
        <thead>
            <tr>
                <th>Names</th>
                <th>Roles</th>
                <th>Profiles</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Username']); ?></td>
                    <td><?php echo htmlspecialchars($row['Role']); ?></td>
                    <td><a href="StudentProfile.php?UserID=<?php echo $row['UserID']; ?>">View</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($role == 'Teacher'): ?>
        <div class="group-management">
            <h2>Manage Student Groups</h2>
            <div class="group-input-container">
                <form action="create_group.php" method="POST">
                    <input type="text" name="GroupName" placeholder="Enter Group Name" required>
                    <button type="submit" class="btn-primary">Create Group</button>
                </form>
            </div>

            <h3>Your Groups</h3>
            <table>
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Number of Students</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($group = $groupResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['GroupName']); ?></td>
                            <td><?php echo htmlspecialchars($group['StudentCount']); ?></td>
                            <td><a href="manage_group.php?GroupID=<?php echo $group['GroupID']; ?>">Manage</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<footer class="main-footer">
    <div class="footer-content">
        <div class="quick-links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">Student Info</a></li>
                <li><a href="#">Contacts</a></li>
                <li><a href="#">Help</a></li>
            </ul>
        </div>
        <div class="contact-us">
            <h3>Contact Us</h3>
            <ul>
                <li><a href="#">Instagram</a></li>
                <li><a href="#">Facebook</a></li>
                <li><a href="#">YouTube</a></li>
            </ul>
        </div>
        <div class="address">
            <h3>Address</h3>
            <p>Level 5/118 King William St<br>Adelaide, SA<br>Phone: (08) 5555 5555</p>
        </div>
    </div>
    <div class="footer-bottom">
        <img src="Images/REAL_SACE.png" alt="SACE Portal Logo">
        <p>&copy; SACE Student Portal</p>
    </div>
</footer>

<script>
    function toggleMenu() {
        const nav = document.querySelector('.main-nav');
        nav.classList.toggle('active');
    }
</script>
</body>
</html>
