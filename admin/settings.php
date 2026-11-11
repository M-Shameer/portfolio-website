
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// Escape displayed information

function settingsEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Default website settings

$defaults = [
    'website_title' => 'Muhammad Shameer | Portfolio',
    'meta_description' => 'Portfolio of Muhammad Shameer, Computer Science Student and Junior Software Developer.',
    'footer_text' => '© 2026 Muhammad Shameer. All Rights Reserved.',
    'footer_credit' => 'Designed and Developed by Muhammad Shameer'
];

// Security token

if (empty($_SESSION['settings_token'])) {
    $_SESSION['settings_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['settings_flash'] ?? '';
unset($_SESSION['settings_flash']);

$error = '';

// Load existing settings

function loadWebsiteSettings($pdo, $defaults) {

    $stmt = $pdo->query(
        "SELECT setting_key, setting_value FROM site_settings"
    );

    $savedSettings = [];

    foreach ($stmt->fetchAll() as $row) {
        $savedSettings[$row['setting_key']] = $row['setting_value'];
    }

    return array_merge($defaults, $savedSettings);
}

$settings = loadWebsiteSettings($pdo, $defaults);

// Save settings

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['settings_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['settings_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $submitted = is_array($_POST['settings'] ?? null)
            ? $_POST['settings']
            : [];

        foreach ($defaults as $key => $defaultValue) {

            $value = $submitted[$key] ?? '';

            if (!is_string($value)) {
                $value = '';
            }

            $settings[$key] = trim($value);

        }

        if ($settings['website_title'] === '') {

            $error = 'Website title is required.';

        } elseif (strlen($settings['website_title']) > 255) {

            $error = 'Website title must not exceed 255 characters.';

        } elseif (strlen($settings['meta_description']) > 500) {

            $error = 'Meta description must not exceed 500 characters.';

        } elseif (
            strlen($settings['footer_text']) > 255 ||
            strlen($settings['footer_credit']) > 255
        ) {

            $error = 'Footer fields must not exceed 255 characters.';

        } else {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO site_settings
                    (setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value)"
                );

                foreach ($defaults as $key => $defaultValue) {

                    $stmt->execute([
                        $key,
                        $settings[$key]
                    ]);

                }

                $pdo->commit();

                $_SESSION['settings_flash'] =
                    'Website settings saved successfully!';

                header("Location: settings.php");
                exit;

            } catch (PDOException $ex) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log("Website settings error: " . $ex->getMessage());

                $error = 'Unable to save website settings. Please try again.';

            }
        }
    }
}

$activePage = 'settings';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Website Settings | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>

                <p class="eyebrow">WEBSITE CONFIGURATION</p>

                <h1>Website Settings</h1>

            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">GLOBAL WEBSITE SETTINGS</p>

            <h2>Manage Your Website</h2>

            <p>
                Control your website title, search-engine description
                and footer information from this page.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo settingsEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo settingsEscape($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="settings_token"
                value="<?php echo settingsEscape($_SESSION['settings_token']); ?>"
            >

            <!-- General Settings -->

            <section class="editor-section">

                <h2>General Settings</h2>

                <p class="editor-note">
                    Manage the main identity of your portfolio website.
                </p>

                <div class="editor-grid">

                    <div class="editor-field full">

                        <label>Website Title *</label>

                        <input
                            type="text"
                            name="settings[website_title]"
                            maxlength="255"
                            value="<?php echo settingsEscape($settings['website_title']); ?>"
                            required
                        >

                        <small>
                            This appears in the browser tab.
                        </small>

                    </div>

                    <div class="editor-field full">

                        <label>SEO Meta Description</label>

                        <textarea
                            name="settings[meta_description]"
                            rows="4"
                            maxlength="500"
                            placeholder="Describe your portfolio website."
                        ><?php echo settingsEscape($settings['meta_description']); ?></textarea>

                        <small>
                            A short description used by search engines.
                        </small>

                    </div>

                </div>

            </section>

            <!-- Footer Settings -->

            <section class="editor-section">

                <h2>Footer Settings</h2>

                <p class="editor-note">
                    Manage the text displayed at the bottom of your website.
                </p>

                <div class="editor-grid">

                    <div class="editor-field full">

                        <label>Copyright Text</label>

                        <input
                            type="text"
                            name="settings[footer_text]"
                            maxlength="255"
                            value="<?php echo settingsEscape($settings['footer_text']); ?>"
                        >

                    </div>

                    <div class="editor-field full">

                        <label>Footer Attribution</label>

                        <input
                            type="text"
                            name="settings[footer_credit]"
                            maxlength="255"
                            value="<?php echo settingsEscape($settings['footer_credit']); ?>"
                        >

                    </div>

                </div>

            </section>

            <div class="profile-save-area">

                <button type="submit" class="save-btn">
                    Save Website Settings
                </button>

                <p>
                    Your settings are stored in the site_settings
                    database table.
                </p>

            </div>

        </form>

        <footer class="admin-footer">
            © 2026 Muhammad Shameer. Portfolio CMS.
        </footer>

    </main>

</div>

</body>
</html>
