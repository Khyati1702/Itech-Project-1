<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="main-header">
    <div class="logo-container">
        <img class="header-logo" src="Images/Real_logo.png" alt="SACE Portal Logo">
        <span class="header-title">SACE Portal</span>
        <link rel="stylesheet" href="Header_footer.css">
        <link rel="stylesheet" href="colors.css">
    </div>
    <div class="nav-container">
        <span class="menu-toggle" onclick="toggleMenu()">☰</span>
        <nav class="main-nav unique-main-nav">
            <a href="Mainpage.php" class="nav-link">Home</a>

            <!-- Conditional display of Grading button based on Role -->
            <?php if ($_SESSION['Role'] != 'Stage1Students' && $_SESSION['Role'] != 'Stage2Students'): ?>
                <a href="assignment.php" class="nav-link">Grading</a>
            <?php endif; ?>

            <!-- Show 'My Grades' for Stage1 and Stage2 students -->
            <?php if ($_SESSION['Role'] == 'Stage1Students' || $_SESSION['Role'] == 'Stage2Students'): ?>
                <a href="student_performance.php?UserID=<?php echo $_SESSION['UserID']; ?>" class="nav-link">My Grades</a>
                <a href="Profile.php" class="nav-link">Profile</a>
            <?php else: ?>
                <a href="Profile.php" class="nav-link">Students</a>
            <?php endif; ?>

            <!-- Dropdown for Assignments (Teachers Only) -->
            <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] == 'Teacher'): ?>
                <div class="dropdown">
                    <button class="dropbtn nav-link">Assignments</button>
                    <div class="dropdown-content">
                        <a href="upload_assignment.php">Create</a>
                        <a href="manage_assignments.php">Manage</a>
                        <a href="submissions.php">Submissions</a> <!-- New Submissions link -->
                    </div>
                </div>
            <?php elseif ($_SESSION['Role'] == 'Stage1Students' || $_SESSION['Role'] == 'Stage2Students'): ?>
                <a href="view_assignments.php" class="nav-link">View Assignments</a>
            <?php endif; ?>

            <a href="#" class="nav-link">Contact</a>
            <a href="#" class="nav-link">Help</a>

            <!-- Show Admin Tool button only if the user has Admin role -->
            <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] == 'Teacher'): ?>
                <a href="admin_tool.php" class="nav-link">Admin Tool</a>
            <?php endif; ?>
        </nav>

        <div class="search-container unique-search-container">
            <div class="search-box unique-search-box">
                <input type="search" class="search-input" placeholder="Search">
            </div>
            <div class="logout-box unique-logout-box">
                <form action="logout.php" method="post" id="logout-form">
                    <button type="submit" id="logout-button" class="logout-button">Logout</button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleMenu() {
        const nav = document.querySelector('.main-nav');
        nav.classList.toggle('active');
    }
</script>
