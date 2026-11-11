
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// Escape displayed information

function experienceEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate date format

function experienceValidDate($date) {

    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

// Security token

if (empty($_SESSION['experience_token'])) {
    $_SESSION['experience_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['experience_flash'] ?? '';
unset($_SESSION['experience_flash']);

$error = '';

// Empty form

$formData = [
    'id' => 0,
    'job_title' => '',
    'company' => '',
    'location' => '',
    'employment_type' => '',
    'start_date' => '',
    'end_date' => '',
    'is_current' => 0,
    'description' => '',
    'sort_order' => 0,
    'is_published' => 1
];

// Load selected experience for editing

if (isset($_GET['edit'])) {

    $editId = filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM experience WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $existingExperience = $stmt->fetch();

        if ($existingExperience) {
            $formData = $existingExperience;
        } else {
            $error = 'The selected experience was not found.';
        }
    }
}

// Handle submitted forms

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['experience_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['experience_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        // Delete experience

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {

                $error = 'Invalid experience record.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "DELETE FROM experience WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    $_SESSION['experience_flash'] =
                        'Experience deleted successfully!';

                    header("Location: experience.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to delete this experience.';

                }
            }

        }

        // Add or update experience

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $jobTitle = trim($_POST['job_title'] ?? '');
            $company = trim($_POST['company'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $employmentType = trim($_POST['employment_type'] ?? '');
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');
            $isCurrent = $_POST['is_current'] ?? '0';
            $description = trim($_POST['description'] ?? '');

            $sortOrder = filter_var(
                $_POST['sort_order'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $isPublished = $_POST['is_published'] ?? '1';

            // Preserve submitted values

            $formData = [
                'id' => $id ?: 0,
                'job_title' => $jobTitle,
                'company' => $company,
                'location' => $location,
                'employment_type' => $employmentType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_current' => $isCurrent,
                'description' => $description,
                'sort_order' => $sortOrder === false ? 0 : $sortOrder,
                'is_published' => $isPublished
            ];

            if ($id === false || $id < 0) {

                $error = 'Invalid experience record.';

            } elseif ($jobTitle === '' || $company === '') {

                $error = 'Job title and company are required.';

            } elseif (
                strlen($jobTitle) > 150 ||
                strlen($company) > 150 ||
                strlen($location) > 150 ||
                strlen($employmentType) > 100
            ) {

                $error = 'One or more text fields exceed their allowed length.';

            } elseif (
                !experienceValidDate($startDate) ||
                !experienceValidDate($endDate)
            ) {

                $error = 'Please enter valid employment dates.';

            } elseif (
                $startDate !== '' &&
                $endDate !== '' &&
                $endDate < $startDate &&
                $isCurrent !== '1'
            ) {

                $error = 'End date cannot be earlier than start date.';

            } elseif ($sortOrder === false || $sortOrder < 0) {

                $error = 'Please enter a valid display order.';

            } elseif (
                !in_array((string) $isCurrent, ['0', '1'], true) ||
                !in_array((string) $isPublished, ['0', '1'], true)
            ) {

                $error = 'Please select valid employment and visibility options.';

            } else {

                try {

                    if ($isCurrent === '1') {
                        $endDate = null;
                    } elseif ($endDate === '') {
                        $endDate = null;
                    }

                    if ($startDate === '') {
                        $startDate = null;
                    }

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE experience SET
                                job_title = ?,
                                company = ?,
                                location = ?,
                                employment_type = ?,
                                start_date = ?,
                                end_date = ?,
                                is_current = ?,
                                description = ?,
                                sort_order = ?,
                                is_published = ?
                            WHERE id = ?"
                        );

                        $stmt->execute([
                            $jobTitle,
                            $company,
                            $location,
                            $employmentType,
                            $startDate,
                            $endDate,
                            (int) $isCurrent,
                            $description,
                            $sortOrder,
                            (int) $isPublished,
                            $id
                        ]);

                        $successMessage =
                            'Experience updated successfully!';

                    } else {

                        $stmt = $pdo->prepare(
                            "INSERT INTO experience (
                                job_title,
                                company,
                                location,
                                employment_type,
                                start_date,
                                end_date,
                                is_current,
                                description,
                                sort_order,
                                is_published
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $jobTitle,
                            $company,
                            $location,
                            $employmentType,
                            $startDate,
                            $endDate,
                            (int) $isCurrent,
                            $description,
                            $sortOrder,
                            (int) $isPublished
                        ]);

                        $successMessage =
                            'New experience added successfully!';

                    }

                    $_SESSION['experience_flash'] = $successMessage;

                    header("Location: experience.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to save experience. Please try again.';

                }
            }
        }
    }
}

// Get all experience records

$stmt = $pdo->query(
    "SELECT * FROM experience
     ORDER BY sort_order ASC, id ASC"
);

$allExperience = $stmt->fetchAll();

// Active sidebar item

$activePage = 'experience';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Experience Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Experience Management</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">PROFESSIONAL JOURNEY</p>

            <h2>Manage Your Experience</h2>

            <p>
                Add and update your jobs, internships, responsibilities,
                employment dates and current positions.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo experienceEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo experienceEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Add or Edit Experience Form -->

        <section class="editor-section" id="experience-form">

            <h2>
                <?php echo !empty($formData['id']) ? 'Edit Experience' : 'Add New Experience'; ?>
            </h2>

            <p class="editor-note">
                Enter your job or internship information.
                Add each responsibility on a separate line.
            </p>

            <form method="POST" action="experience.php#experience-form">

                <input
                    type="hidden"
                    name="experience_token"
                    value="<?php echo experienceEscape($_SESSION['experience_token']); ?>"
                >

                <input type="hidden" name="action" value="save">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $formData['id']; ?>"
                >

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Job Title *</label>

                        <input
                            type="text"
                            name="job_title"
                            maxlength="150"
                            value="<?php echo experienceEscape($formData['job_title']); ?>"
                            placeholder="Example: Web Development Intern"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Company *</label>

                        <input
                            type="text"
                            name="company"
                            maxlength="150"
                            value="<?php echo experienceEscape($formData['company']); ?>"
                            placeholder="Company name"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Location</label>

                        <input
                            type="text"
                            name="location"
                            maxlength="150"
                            value="<?php echo experienceEscape($formData['location']); ?>"
                            placeholder="Islamabad, Pakistan"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Employment Type / Duration</label>

                        <input
                            type="text"
                            name="employment_type"
                            maxlength="100"
                            value="<?php echo experienceEscape($formData['employment_type']); ?>"
                            placeholder="Full-time or 3 Months Internship"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Start Date</label>

                        <input
                            type="date"
                            name="start_date"
                            value="<?php echo experienceEscape($formData['start_date'] ?? ''); ?>"
                        >

                        <small>
                            Leave blank when the exact date is unknown.
                        </small>

                    </div>

                    <div class="editor-field">

                        <label>End Date</label>

                        <input
                            type="date"
                            name="end_date"
                            value="<?php echo experienceEscape($formData['end_date'] ?? ''); ?>"
                        >

                        <small>
                            Leave blank for a current position.
                        </small>

                    </div>

                    <div class="editor-field">

                        <label>Currently Working Here?</label>

                        <select name="is_current">

                            <option value="0"
                                <?php echo (string) $formData['is_current'] === '0' ? 'selected' : ''; ?>>
                                No
                            </option>

                            <option value="1"
                                <?php echo (string) $formData['is_current'] === '1' ? 'selected' : ''; ?>>
                                Yes — Present
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
                            Smaller numbers appear first.
                        </small>

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

                    <div class="editor-field full">

                        <label>Responsibilities / Description</label>

                        <textarea
                            name="description"
                            rows="6"
                            placeholder="Enter each responsibility on a separate line."
                        ><?php echo experienceEscape($formData['description']); ?></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="save-btn">

                        <?php echo !empty($formData['id']) ? 'Update Experience' : 'Add Experience'; ?>

                    </button>

                    <?php if (!empty($formData['id'])): ?>

                        <a href="experience.php" class="back-link">
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Experience -->

        <section class="editor-section">

            <h2>Your Work Experience</h2>

            <p class="editor-note">
                Edit, hide or delete your existing jobs and internships.
            </p>

            <?php if (empty($allExperience)): ?>

                <p>No experience records have been added yet.</p>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table">

                        <thead>

                            <tr>
                                <th>Position</th>
                                <th>Company</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allExperience as $job): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo experienceEscape($job['job_title']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo experienceEscape($job['company']); ?>
                                    </td>

                                    <td>

                                        <?php

                                        if (!empty($job['is_current'])) {

                                            echo 'Present';

                                        } elseif (!empty($job['employment_type'])) {

                                            echo experienceEscape($job['employment_type']);

                                        } else {

                                            echo experienceEscape(
                                                $job['start_date'] ?: 'Date not specified'
                                            );

                                        }

                                        ?>

                                    </td>

                                    <td>

                                        <?php if ($job['is_published']): ?>

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
                                                href="experience.php?edit=<?php echo (int) $job['id']; ?>#experience-form"
                                                class="edit-link"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="experience.php"
                                                onsubmit="return confirm('Are you sure you want to delete this experience?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="experience_token"
                                                    value="<?php echo experienceEscape($_SESSION['experience_token']); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $job['id']; ?>"
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
