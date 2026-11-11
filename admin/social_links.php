
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

function socialEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function socialValidUrl($url) {

    if ($url === '') {
        return false;
    }

    $scheme = strtolower(
        parse_url($url, PHP_URL_SCHEME) ?? ''
    );

    return filter_var($url, FILTER_VALIDATE_URL)
        && in_array($scheme, ['http', 'https'], true);
}

// Security token

if (empty($_SESSION['social_token'])) {
    $_SESSION['social_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['social_flash'] ?? '';
unset($_SESSION['social_flash']);

$error = '';

$formData = [
    'id' => 0,
    'platform' => '',
    'url' => '',
    'sort_order' => 0,
    'is_published' => 1
];

// Load selected social link

if (isset($_GET['edit'])) {

    $editId = filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM social_links WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $existingLink = $stmt->fetch();

        if ($existingLink) {
            $formData = $existingLink;
        } else {
            $error = 'The selected social link was not found.';
        }
    }
}

// Handle form submissions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['social_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['social_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        // Delete social link

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id < 1) {

                $error = 'Invalid social link.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "DELETE FROM social_links WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    $_SESSION['social_flash'] =
                        'Social link deleted successfully!';

                    header("Location: social_links.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to delete this social link.';

                }
            }
        }

        // Add or update social link

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $platform = trim($_POST['platform'] ?? '');
            $url = trim($_POST['url'] ?? '');

            $sortOrder = filter_var(
                $_POST['sort_order'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $isPublished = $_POST['is_published'] ?? '1';

            $formData = [
                'id' => $id === false ? 0 : $id,
                'platform' => $platform,
                'url' => $url,
                'sort_order' => $sortOrder === false ? 0 : $sortOrder,
                'is_published' => $isPublished
            ];

            if ($id === false || $id < 0) {

                $error = 'Invalid social link.';

            } elseif ($platform === '' || $url === '') {

                $error = 'Platform name and URL are required.';

            } elseif (strlen($platform) > 100 || strlen($url) > 255) {

                $error = 'Platform name or URL is too long.';

            } elseif (!socialValidUrl($url)) {

                $error = 'Enter a valid HTTP or HTTPS URL.';

            } elseif ($sortOrder === false || $sortOrder < 0) {

                $error = 'Enter a valid display order.';

            } elseif (
                !in_array((string) $isPublished, ['0', '1'], true)
            ) {

                $error = 'Select a valid visibility option.';

            } else {

                try {

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE social_links SET
                                platform = ?,
                                url = ?,
                                sort_order = ?,
                                is_published = ?
                            WHERE id = ?"
                        );

                        $stmt->execute([
                            $platform,
                            $url,
                            $sortOrder,
                            (int) $isPublished,
                            $id
                        ]);

                        $successMessage =
                            'Social link updated successfully!';

                    } else {

                        $stmt = $pdo->prepare(
                            "INSERT INTO social_links
                            (platform, url, sort_order, is_published)
                            VALUES (?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $platform,
                            $url,
                            $sortOrder,
                            (int) $isPublished
                        ]);

                        $successMessage =
                            'Social link added successfully!';

                    }

                    $_SESSION['social_flash'] = $successMessage;

                    header("Location: social_links.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to save the social link.';

                }
            }
        }
    }
}

// Load all social links

$stmt = $pdo->query(
    "SELECT * FROM social_links ORDER BY sort_order ASC, id ASC"
);

$allLinks = $stmt->fetchAll();

$activePage = 'social_links';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Social Links Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Social Links Management</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">CONNECT WITH ME</p>

            <h2>Manage Your Social Profiles</h2>

            <p>
                Add, update, hide or delete your social media
                and professional profile links.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo socialEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo socialEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Add or Edit Social Link -->

        <section class="editor-section" id="social-form">

            <h2>
                <?php echo !empty($formData['id']) ? 'Edit Social Link' : 'Add Social Link'; ?>
            </h2>

            <p class="editor-note">
                Enter the platform name and complete profile URL.
            </p>

            <form method="POST" action="social_links.php#social-form">

                <input type="hidden" name="social_token"
                    value="<?php echo socialEscape($_SESSION['social_token']); ?>">

                <input type="hidden" name="action" value="save">

                <input type="hidden" name="id"
                    value="<?php echo (int) $formData['id']; ?>">

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Platform Name *</label>

                        <input
                            type="text"
                            name="platform"
                            maxlength="100"
                            value="<?php echo socialEscape($formData['platform']); ?>"
                            placeholder="Example: LinkedIn"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Display Order</label>

                        <input
                            type="number"
                            name="sort_order"
                            min="0"
                            value="<?php echo (int) $formData['sort_order']; ?>"
                        >

                    </div>

                    <div class="editor-field full">

                        <label>Profile URL *</label>

                        <input
                            type="url"
                            name="url"
                            maxlength="255"
                            value="<?php echo socialEscape($formData['url']); ?>"
                            placeholder="https://example.com/your-profile"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Visibility</label>

                        <select name="is_published">

                            <option value="1"
                                <?php echo (string) $formData['is_published'] === '1' ? 'selected' : ''; ?>>
                                Published
                            </option>

                            <option value="0"
                                <?php echo (string) $formData['is_published'] === '0' ? 'selected' : ''; ?>>
                                Hidden
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="save-btn">
                        <?php echo !empty($formData['id']) ? 'Update Social Link' : 'Add Social Link'; ?>
                    </button>

                    <?php if (!empty($formData['id'])): ?>

                        <a href="social_links.php" class="back-link">
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Social Links -->

        <section class="editor-section">

            <h2>Your Social Links</h2>

            <p class="editor-note">
                Manage the social links currently stored in your database.
            </p>

            <?php if (empty($allLinks)): ?>

                <p>No social links have been added yet.</p>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table">

                        <thead>

                            <tr>
                                <th>Platform</th>
                                <th>Profile URL</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allLinks as $link): ?>

                                <tr>

                                    <td>
                                        <strong><?php echo socialEscape($link['platform']); ?></strong>
                                    </td>

                                    <td>
                                        <?php echo socialEscape($link['url']); ?>
                                    </td>

                                    <td>
                                        <?php echo (int) $link['sort_order']; ?>
                                    </td>

                                    <td>

                                        <?php if ($link['is_published']): ?>

                                            <span class="status-published">Published</span>

                                        <?php else: ?>

                                            <span class="status-hidden">Hidden</span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="skill-actions">

                                            <a
                                                href="social_links.php?edit=<?php echo (int) $link['id']; ?>#social-form"
                                                class="edit-link"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="social_links.php"
                                                onsubmit="return confirm('Delete this social link?');"
                                            >

                                                <input type="hidden" name="social_token"
                                                    value="<?php echo socialEscape($_SESSION['social_token']); ?>">

                                                <input type="hidden" name="action" value="delete">

                                                <input type="hidden" name="id"
                                                    value="<?php echo (int) $link['id']; ?>">

                                                <button type="submit" class="delete-btn">
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

        <footer class="admin-footer">
            © 2026 Muhammad Shameer. Portfolio CMS.
        </footer>

    </main>

</div>

</body>
</html>
