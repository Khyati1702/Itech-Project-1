<?php
session_start();

// This is the main page of the website or home page. 

// Ensure the user is logged in
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
} 

// Get the user's role
$role = $_SESSION['Role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Main Page</title>
    <link rel="stylesheet" href="Mainpage.css">
    <link rel="stylesheet" href="colors.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<main>
    <h1 class="animated-text">いらっしゃいませ <?php echo htmlspecialchars($_SESSION['Name']); ?> <span class="emoji">&#128075;</span></h1>
    <section class="courses-grid">
        <?php if ($role == 'Teacher'): ?>
            <!-- Card 1 -->
            <a href="assignment.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>SACE Japanese (Grading)</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>Stage 1 & 2</p>
                    </div>
                </article>
            </a>
            <!-- Card 2-->
            <a href="profile.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>SACE Japanese (Students)</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>Stage 1 & 2</p>

                    </div>
                </article>
            </a>
            <!-- Card 3 -->
            <a href="upload_assignment.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>SACE Japanese (Assignments)</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>Stage 1 & 2</p>
                      
                    </div>
                </article>
            </a>
            <!--  Card 4 -->
            <a href="submissions.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>SACE Japanese (Submissions)</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>Stage 1 & 2</p>

                    </div>
                </article>
            </a>
        <?php else: ?>
    
           
            <!-- Card 1-->
            <a href="profile.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>Profile</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>Your personal information</p>

                    </div>
                </article>
            </a>
            <!-- Card 2 -->
            <a href="view_assignments.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>View Assignments</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>See your assignments</p>
                      
                    </div>
                </article>
            </a>
            <!--  Card 3 -->
            <a href="setup_traditional_login_form.php" class="course-link">
                <article class="course-card">
                    <header class="course-header background-blue">
                        <h2>Account Settings</h2>
                        <button>...</button>
                    </header>
                    <div class="course-body">
                        <p>Manage your account</p>

                    </div>
                </article>
            </a>
        <?php endif; ?>
    </section>
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
