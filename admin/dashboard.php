
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

if (empty($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

$username = htmlspecialchars(
    $_SESSION['admin_username'] ?? 'Admin',
    ENT_QUOTES,
    'UTF-8'
);

// Get live database statistics

$projectCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM projects"
)->fetchColumn();

$skillCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM skills"
)->fetchColumn();

$experienceCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM experience"
)->fetchColumn();

$messageCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"
)->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

    <div class="admin-layout">

        <!-- Sidebar -->

        <?php

$activePage = 'dashboard';

include '../includes/admin_sidebar.php';

?>

        <!-- Main Dashboard -->

        <main class="admin-main">

            <header class="topbar">

                <div>

                    <p class="eyebrow">ADMINISTRATION</p>

                    <h1>Dashboard Overview</h1>

                </div>

                <div class="topbar-user">

                    <span class="user-avatar">MS</span>

                    <span><?php echo $username; ?></span>

                    <form
                        action="logout.php"
                        method="POST"
                        class="logout-form"
                    >

                        <input
                            type="hidden"
                            name="logout_token"
                            value="<?php echo htmlspecialchars(
                                $_SESSION['logout_token'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >

                        <button type="submit" class="logout-btn">
                            Logout
                        </button>

                    </form>

                </div>

            </header>

            <!-- Welcome Section -->

            <section class="welcome-panel">

                <p class="welcome-label">YOUR PORTFOLIO AT A GLANCE</p>

                <h2>Welcome back, <?php echo $username; ?>.</h2>

                <p>
                    Manage your portfolio content, update your professional
                    information, and keep your website up to date.
                </p>

            </section>

            <!-- Statistics -->

            <section>

                <h2 class="section-heading">Website Statistics</h2>

                <div class="stats-grid">

                    <div class="stat-card">

                        <div class="stat-icon">▧</div>

                        <p>Total Projects</p>

                        <h3><?php echo $projectCount; ?></h3>

                    </div>

                    <div class="stat-card">

                        <div class="stat-icon">◇</div>

                        <p>Total Skills</p>

                        <h3><?php echo $skillCount; ?></h3>

                    </div>

                    <div class="stat-card">

                        <div class="stat-icon">▤</div>

                        <p>Work Experiences</p>

                        <h3><?php echo $experienceCount; ?></h3>

                    </div>

                    <div class="stat-card">

                        <div class="stat-icon">✉</div>

                        <p>New Messages</p>

                        <h3><?php echo $messageCount; ?></h3>

                    </div>

                </div>

            </section>

            <!-- Management Information -->

            <section class="management-panel">

                <h3>Content Management</h3>

                <p>
                    Your content management modules will be connected here.
                    You will be able to add, edit, delete, publish, and
                    organize your portfolio information without manually
                    changing your website code.
                </p>

            </section>

            <footer class="admin-footer">

                © 2026 Mohammad Shameer. Portfolio CMS.

            </footer>

        </main>

    </div>

</body>

</html>
