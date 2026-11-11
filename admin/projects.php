
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';
require_once '../includes/upload_helper.php';

// Escape displayed information

function projectEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate website links

function projectValidUrl($url) {

    if ($url === '') {
        return true;
    }

    $scheme = strtolower(
        parse_url($url, PHP_URL_SCHEME) ?? ''
    );

    return filter_var($url, FILTER_VALIDATE_URL)
        && in_array($scheme, ['http', 'https'], true);
}

// Security token

if (empty($_SESSION['projects_token'])) {
    $_SESSION['projects_token'] = bin2hex(random_bytes(32));
}

// Notifications

$message = $_SESSION['projects_flash'] ?? '';
unset($_SESSION['projects_flash']);

$error = '';

// Empty form

$formData = [
    'id' => 0,
    'project_name' => '',
    'description' => '',
    'technologies' => '',
    'image_path' => '',
    'github_url' => '',
    'live_url' => '',
    'is_featured' => 0,
    'status' => 'published',
    'sort_order' => 0
];

// Load selected project for editing

if (isset($_GET['edit'])) {

    $editId = filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM projects WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $existingProject = $stmt->fetch();

        if ($existingProject) {
            $formData = $existingProject;
        } else {
            $error = 'The selected project was not found.';
        }
    }
}

// Handle form submissions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['projects_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['projects_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        // Delete project

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id < 1) {

                $error = 'Invalid project record.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "DELETE FROM projects WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    if ($stmt->rowCount() === 0) {
                        $error = 'The project was not found.';
                    } else {

                        $_SESSION['projects_flash'] =
                            'Project deleted successfully!';

                        header("Location: projects.php");
                        exit;

                    }

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to delete this project.';

                }
            }
        }

        // Add or update project

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $name = trim($_POST['project_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $technologies = trim($_POST['technologies'] ?? '');
            $githubUrl = trim($_POST['github_url'] ?? '');
            $liveUrl = trim($_POST['live_url'] ?? '');

            $isFeatured = $_POST['is_featured'] ?? '0';
            $status = $_POST['status'] ?? 'published';

            $sortOrder = filter_var(
                $_POST['sort_order'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $existingImage = '';

            // Retrieve the existing image when editing

            if ($id !== false && $id > 0) {

                try {

                    $check = $pdo->prepare(
                        "SELECT image_path
                         FROM projects
                         WHERE id = ?"
                    );

                    $check->execute([$id]);

                    $existingRecord = $check->fetch();

                    if (!$existingRecord) {

                        $error = 'The project you are editing no longer exists.';

                    } else {

                        $existingImage = $existingRecord['image_path'] ?? '';

                    }

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to retrieve the existing project image.';

                }
            }

            // Preserve submitted values and existing image

            $formData = [
                'id' => $id === false ? 0 : $id,
                'project_name' => $name,
                'description' => $description,
                'technologies' => $technologies,
                'image_path' => $existingImage,
                'github_url' => $githubUrl,
                'live_url' => $liveUrl,
                'is_featured' => $isFeatured,
                'status' => $status,
                'sort_order' => $sortOrder === false ? 0 : $sortOrder
            ];

            // Validate information

            if ($error !== '') {

                // Keep the existing error.

            } elseif ($id === false || $id < 0) {

                $error = 'Invalid project record.';

            } elseif ($name === '') {

                $error = 'Project name is required.';

            } elseif (
                strlen($name) > 200 ||
                strlen($githubUrl) > 255 ||
                strlen($liveUrl) > 255
            ) {

                $error = 'One or more fields exceed their allowed length.';

            } elseif (
                !projectValidUrl($githubUrl) ||
                !projectValidUrl($liveUrl)
            ) {

                $error = 'Please enter valid HTTP or HTTPS project links.';

            } elseif ($sortOrder === false || $sortOrder < 0) {

                $error = 'Please enter a valid display order.';

            } elseif (
                !in_array((string) $isFeatured, ['0', '1'], true) ||
                !in_array($status, ['draft', 'published'], true)
            ) {

                $error = 'Please select valid project options.';

            } else {

                try {

                    // Upload a new project image.
                    // If no new image is selected, keep the existing image.

                    $newImage = savePortfolioUpload(
                        $_FILES['project_image'] ?? [],
                        'image'
                    );

                    $imageToSave = $newImage ?? (
                        $existingImage !== '' ? $existingImage : null
                    );

                    $formData['image_path'] = $imageToSave ?? '';

                    // Update existing project

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE projects SET
                                project_name = ?,
                                description = ?,
                                technologies = ?,
                                image_path = ?,
                                github_url = ?,
                                live_url = ?,
                                is_featured = ?,
                                status = ?,
                                sort_order = ?
                            WHERE id = ?"
                        );

                        $stmt->execute([
                            $name,
                            $description,
                            $technologies,
                            $imageToSave,
                            $githubUrl,
                            $liveUrl,
                            (int) $isFeatured,
                            $status,
                            $sortOrder,
                            $id
                        ]);

                        $successMessage =
                            'Project updated successfully!';

                    } else {

                        // Insert new project

                        $stmt = $pdo->prepare(
                            "INSERT INTO projects (
                                project_name,
                                description,
                                technologies,
                                image_path,
                                github_url,
                                live_url,
                                is_featured,
                                status,
                                sort_order
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $name,
                            $description,
                            $technologies,
                            $imageToSave,
                            $githubUrl,
                            $liveUrl,
                            (int) $isFeatured,
                            $status,
                            $sortOrder
                        ]);

                        $successMessage =
                            'New project added successfully!';

                    }

                    $_SESSION['projects_flash'] = $successMessage;

                    header("Location: projects.php");
                    exit;

                } catch (RuntimeException $ex) {

                    $error = $ex->getMessage();

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to save the project. Please try again.';

                }
            }
        }
    }
}

// Load all projects

$stmt = $pdo->query(
    "SELECT * FROM projects
     ORDER BY sort_order ASC, id ASC"
);

$allProjects = $stmt->fetchAll();

$activePage = 'projects';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Projects Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Projects Management</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">MY PORTFOLIO WORK</p>

            <h2>Manage Your Projects</h2>

            <p>
                Add and update project descriptions, technologies,
                images, GitHub links and live website links.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo projectEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo projectEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Add or Edit Project -->

        <section class="editor-section" id="project-form">

            <h2>
                <?php echo !empty($formData['id']) ? 'Edit Project' : 'Add New Project'; ?>
            </h2>

            <p class="editor-note">
                Enter the details of your software or web development project.
            </p>

            <form
                method="POST"
                enctype="multipart/form-data"
                action="projects.php#project-form"
            >

                <input
                    type="hidden"
                    name="projects_token"
                    value="<?php echo projectEscape($_SESSION['projects_token']); ?>"
                >

                <input type="hidden" name="action" value="save">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $formData['id']; ?>"
                >

                <div class="editor-grid">

                    <div class="editor-field full">

                        <label>Project Name *</label>

                        <input
                            type="text"
                            name="project_name"
                            maxlength="200"
                            value="<?php echo projectEscape($formData['project_name']); ?>"
                            placeholder="Example: Employee Management System"
                            required
                        >

                    </div>

                    <div class="editor-field full">

                        <label>Project Description</label>

                        <textarea
                            name="description"
                            rows="5"
                            placeholder="Describe what your project does."
                        ><?php echo projectEscape($formData['description']); ?></textarea>

                    </div>

                    <div class="editor-field full">

                        <label>Technologies Used</label>

                        <input
                            type="text"
                            name="technologies"
                            value="<?php echo projectEscape($formData['technologies']); ?>"
                            placeholder="C#, Windows Forms, ADO.NET, SQL Server"
                        >

                    </div>

                    <!-- Project Image Upload -->

                    <div class="editor-field full">

                        <label>Upload Project Image</label>

                        <input
                            type="file"
                            name="project_image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <?php if (!empty($formData['image_path'])): ?>

                            <small>Current project image:</small>

                            <img
                                src="../<?php echo projectEscape($formData['image_path']); ?>"
                                alt="<?php echo projectEscape($formData['project_name']); ?>"
                                style="width: 180px; height: 120px; object-fit: cover; border-radius: 6px;"
                            >

                        <?php else: ?>

                            <small>No project image uploaded yet.</small>

                        <?php endif; ?>

                        <small>
                            JPG, PNG or WebP. Maximum size: 5 MB.
                            Leave empty to keep the existing image.
                        </small>

                    </div>

                    <div class="editor-field">

                        <label>GitHub Repository URL</label>

                        <input
                            type="url"
                            name="github_url"
                            maxlength="255"
                            value="<?php echo projectEscape($formData['github_url']); ?>"
                            placeholder="https://github.com/username/project"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Live Project URL</label>

                        <input
                            type="url"
                            name="live_url"
                            maxlength="255"
                            value="<?php echo projectEscape($formData['live_url']); ?>"
                            placeholder="https://example.com"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Featured Project?</label>

                        <select name="is_featured">

                            <option value="0"
                                <?php echo (string) $formData['is_featured'] === '0' ? 'selected' : ''; ?>>
                                No
                            </option>

                            <option value="1"
                                <?php echo (string) $formData['is_featured'] === '1' ? 'selected' : ''; ?>>
                                Yes
                            </option>

                        </select>

                    </div>

                    <div class="editor-field">

                        <label>Publication Status</label>

                        <select name="status">

                            <option value="published"
                                <?php echo $formData['status'] === 'published' ? 'selected' : ''; ?>>
                                Published
                            </option>

                            <option value="draft"
                                <?php echo $formData['status'] === 'draft' ? 'selected' : ''; ?>>
                                Draft
                            </option>

                        </select>

                    </div>

                    <div class="editor-field">

                        <label>Display Order</label>

                        <input
                            type="number"
                            name="sort_order"
                            min="0"
                            value="<?php echo (int) $formData['sort_order']; ?>"
                        >

                        <small>
                            Smaller numbers appear first after featured projects.
                        </small>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="save-btn">

                        <?php echo !empty($formData['id']) ? 'Update Project' : 'Add Project'; ?>

                    </button>

                    <?php if (!empty($formData['id'])): ?>

                        <a href="projects.php" class="back-link">
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Projects -->

        <section class="editor-section">

            <h2>Your Projects</h2>

            <p class="editor-note">
                Edit, publish, convert to draft or delete existing projects.
            </p>

            <?php if (empty($allProjects)): ?>

                <p>No projects have been added yet.</p>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table">

                        <thead>

                            <tr>
                                <th>Project</th>
                                <th>Technologies</th>
                                <th>Featured</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allProjects as $project): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo projectEscape($project['project_name']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo projectEscape($project['technologies']); ?>
                                    </td>

                                    <td>
                                        <?php echo !empty($project['is_featured']) ? 'Yes' : 'No'; ?>
                                    </td>

                                    <td>

                                        <?php if ($project['status'] === 'published'): ?>

                                            <span class="status-published">
                                                Published
                                            </span>

                                        <?php else: ?>

                                            <span class="status-hidden">
                                                Draft
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="skill-actions">

                                            <a
                                                href="projects.php?edit=<?php echo (int) $project['id']; ?>#project-form"
                                                class="edit-link"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="projects.php"
                                                onsubmit="return confirm('Are you sure you want to delete this project?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="projects_token"
                                                    value="<?php echo projectEscape($_SESSION['projects_token']); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $project['id']; ?>"
                                                >

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
