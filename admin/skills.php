
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// Escape displayed values

function skillEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Create security token

if (empty($_SESSION['skills_token'])) {
    $_SESSION['skills_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['skills_flash'] ?? '';
unset($_SESSION['skills_flash']);

$error = '';

// Default form values

$formData = [
    'id' => 0,
    'category' => '',
    'skill_name' => '',
    'sort_order' => 0,
    'is_published' => 1
];

// Load selected skill for editing

if (isset($_GET['edit'])) {

    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

    if ($editId) {

        $stmt = $pdo->prepare(
            "SELECT * FROM skills WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $existingSkill = $stmt->fetch();

        if ($existingSkill) {
            $formData = $existingSkill;
        } else {
            $error = 'The selected skill category was not found.';
        }

    }
}

// Handle form submissions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['skills_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['skills_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        // Delete a skill category

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {

                $error = 'Invalid skill category.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "DELETE FROM skills WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    $_SESSION['skills_flash'] =
                        'Skill category deleted successfully!';

                    header("Location: skills.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to delete the skill category.';

                }
            }

        }

        // Add or update a skill category

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $category = trim($_POST['category'] ?? '');
            $skillName = trim($_POST['skill_name'] ?? '');

            $sortOrder = filter_var(
                $_POST['sort_order'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $isPublished = $_POST['is_published'] ?? '1';

            $formData = [
                'id' => $id ?: 0,
                'category' => $category,
                'skill_name' => $skillName,
                'sort_order' => $sortOrder === false ? 0 : $sortOrder,
                'is_published' => $isPublished
            ];

            if ($category === '' || $skillName === '') {

                $error = 'Category name and skills are required.';

            } elseif (strlen($category) > 100 || strlen($skillName) > 100) {

                $error = 'Category and skills must each be within 100 characters.';

            } elseif ($sortOrder === false || $sortOrder < 0) {

                $error = 'Please enter a valid display order.';

            } elseif (!in_array((string) $isPublished, ['0', '1'], true)) {

                $error = 'Please select a valid visibility option.';

            } else {

                try {

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE skills
                             SET category = ?,
                                 skill_name = ?,
                                 sort_order = ?,
                                 is_published = ?
                             WHERE id = ?"
                        );

                        $stmt->execute([
                            $category,
                            $skillName,
                            $sortOrder,
                            (int) $isPublished,
                            $id
                        ]);

                        $successMessage = 'Skill category updated successfully!';

                    } else {

                        $stmt = $pdo->prepare(
                            "INSERT INTO skills
                            (category, skill_name, sort_order, is_published)
                            VALUES (?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $category,
                            $skillName,
                            $sortOrder,
                            (int) $isPublished
                        ]);

                        $successMessage = 'New skill category added successfully!';

                    }

                    $_SESSION['skills_flash'] = $successMessage;

                    header("Location: skills.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to save this skill category. Please try again.';

                }
            }

        }

    }
}

// Load all skill categories

$stmt = $pdo->query(
    "SELECT * FROM skills ORDER BY sort_order ASC, id ASC"
);

$allSkills = $stmt->fetchAll();

// Current sidebar section

$activePage = 'skills';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Skills Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Skills Management</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">TECHNICAL EXPERTISE</p>

            <h2>Manage Your Skills</h2>

            <p>
                Add, edit, organize and control the visibility
                of your technical and professional skills.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo skillEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo skillEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Add or Edit Skill Form -->

        <section class="editor-section" id="skill-form">

            <h2>
                <?php echo !empty($formData['id']) ? 'Edit Skill Category' : 'Add New Skill Category'; ?>
            </h2>

            <p class="editor-note">
                Enter one category and its related skills.
                Separate individual skills with commas.
            </p>

            <form method="POST" action="skills.php#skill-form">

                <input type="hidden" name="skills_token"
                    value="<?php echo skillEscape($_SESSION['skills_token']); ?>">

                <input type="hidden" name="action" value="save">

                <input type="hidden" name="id"
                    value="<?php echo (int) $formData['id']; ?>">

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Category Name *</label>

                        <input
                            type="text"
                            name="category"
                            maxlength="100"
                            placeholder="Example: Programming Languages"
                            value="<?php echo skillEscape($formData['category']); ?>"
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

                        <label>Skills *</label>

                        <textarea
                            name="skill_name"
                            rows="3"
                            maxlength="100"
                            placeholder="Example: C, C++, Java, C#, Python, JavaScript"
                            required
                        ><?php echo skillEscape($formData['skill_name']); ?></textarea>

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

                        <?php echo !empty($formData['id']) ? 'Update Skill Category' : 'Add Skill Category'; ?>

                    </button>

                    <?php if (!empty($formData['id'])): ?>

                        <a href="skills.php" class="back-link">
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Skill Categories -->

        <section class="editor-section">

            <h2>Your Skill Categories</h2>

            <p class="editor-note">
                All existing categories are listed below.
                You can edit, hide or delete them.
            </p>

            <?php if (empty($allSkills)): ?>

                <p>No skill categories have been added yet.</p>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table">

                        <thead>

                            <tr>
                                <th>Category</th>
                                <th>Skills</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allSkills as $skill): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo skillEscape($skill['category']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo skillEscape($skill['skill_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo (int) $skill['sort_order']; ?>
                                    </td>

                                    <td>

                                        <?php if ($skill['is_published']): ?>

                                            <span class="status-published">
                                                Published
                                            </span>

                                        <?php else: ?>

                                            <span class="status-hidden">
                                                Hidden
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="skill-actions">

                                            <a
                                                href="skills.php?edit=<?php echo (int) $skill['id']; ?>#skill-form"
                                                class="edit-link"
                                            >
                                                Edit
                                            </a>

                                            <form method="POST" action="skills.php"
                                                onsubmit="return confirm('Are you sure you want to delete this skill category?');">

                                                <input type="hidden"
                                                    name="skills_token"
                                                    value="<?php echo skillEscape($_SESSION['skills_token']); ?>">

                                                <input type="hidden"
                                                    name="action"
                                                    value="delete">

                                                <input type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $skill['id']; ?>">

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
