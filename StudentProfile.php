<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

if (isset($_GET['UserID'])) {
    $UserID = $_GET['UserID'];

    // Fetch user details directly from the users table, including the Course column
    $query = "SELECT * FROM users WHERE UserID = ?";
    $stmt = $config->prepare($query);
    $stmt->bind_param("i", $UserID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    header('Location: Profile.php');
    exit();
}

// Handle missing or null contact and course fields
$contact = isset($user['Contact']) && !empty($user['Contact']) ? $user['Contact'] : "Not Provided";
$course = isset($user['Course']) && !empty($user['Course']) ? $user['Course'] : "Not Assigned"; // Fetch the course field
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="StudentProfile.css">
    <link rel="stylesheet" href="colors.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <main>
    <div class="profile-card">
        <h1>Student Profile</h1>
        <div class="profile-content"> <!-- Same container class for layout consistency -->
            <div class="user-details"> <!-- User details section -->
                <h2>User details</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?></p>
                <p><strong>Email address:</strong> <?php echo htmlspecialchars($user['GoogleEmail']); ?></p>
                <!-- Contact info only if available, else show 'Not Provided' -->
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($contact); ?></p>
            </div>

            <div class="course-details"> <!-- Course details section with previous class -->
                <h2>Course details</h2>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($course); ?></p> <!-- Display course -->
            </div>

            <div class="reports"> <!-- Reports section with previous class -->
                <h2>Reports</h2>
                <ul>
                    <li><a href="student_performance.php?UserID=<?php echo $UserID; ?>">Performance</a></li>
                </ul>
            </div>
        </div>
</div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        function toggleMenu() {
            const nav = document.querySelector('.main-nav');
            nav.classList.toggle('active');
        }
    </script>
</body>
</html>
