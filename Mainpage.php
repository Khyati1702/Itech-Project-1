<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page</title>
    <link rel="stylesheet" href="Mainpage.css">
    <link rel="stylesheet" href="colors.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <main>
        <h1 class="animated-text">いらっしゃいませ <?php echo htmlspecialchars($_SESSION['Name']); ?> <span class="emoji">&#128075;</span></h1>

        <section class="courses-grid">
            <!-- Course Card 1 -->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Background)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 1</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
            <!-- Course Card 2-->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Background)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 2</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
            <!-- Course Card 3 -->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Continuers)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 1</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
            <!-- Course Card 4 -->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Continuers)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 2</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
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
