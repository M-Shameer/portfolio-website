
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// Escape displayed information

function educationEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate date format

function educationValidDate($date) {

    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

// Security token

if (empty($_SESSION['education_token'])) {
    $_SESSION['education_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['education_flash'] ?? '';
unset($_SESSION['education_flash']);

$error = '';

// Empty form

$formData = [
    'id' => 0,
    'degree' => '',
    'institution' => '',
    'location' => '',
    'start_date' => '',
    'end_date' => '',
    'description' => '',
    'sort_order' => 0,
    'is_published' => 1
];

// Load selected education for editing

if (isset($_GET['edit'])) {

    $editId = filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM education WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $existingEducation = $stmt->fetch();

        if ($existingEducation) {
            $formData = $existingEducation;
        } else {
            $error = 'The selected education record was not found.';
        }
    }
}

// Handle form submissions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['education_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['education_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        // Delete education

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id < 1) {

                $error = 'Invalid education record.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "DELETE FROM education WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    $_SESSION['education_flash'] =
                        'Education record deleted successfully!';

                    header("Location: education.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to delete this education record.';

                }
            }

        }

        // Add or update education

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $degree = trim($_POST['degree'] ?? '');
            $institution = trim($_POST['institution'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $sortOrder = filter_var(
                $_POST['sort_order'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $isPublished = $_POST['is_published'] ?? '1';

            // Preserve submitted form values

            $formData = [
                'id' => $id === false ? 0 : $id,
                'degree' => $degree,
                'institution' => $institution,
                'location' => $location,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $description,
                'sort_order' => $sortOrder === false ? 0 : $sortOrder,
                'is_published' => $isPublished
            ];

            if ($id === false || $id < 0) {

                $error = 'Invalid education record.';

            } elseif ($degree === '' || $institution === '') {

                $error = 'Degree and institution are required.';

            } elseif (
                strlen($degree) > 150 ||
                strlen($institution) > 200 ||
                strlen($location) > 150
            ) {

                $error = 'One or more text fields exceed their allowed length.';

            } elseif (
                !educationValidDate($startDate) ||
                !educationValidDate($endDate)
            ) {

                $error = 'Please enter valid education dates.';

            } elseif (
                $startDate !== '' &&
                $endDate !== '' &&
                $endDate < $startDate
            ) {

                $error = 'End date cannot be earlier than start date.';

            } elseif ($sortOrder === false || $sortOrder < 0) {

                $error = 'Please enter a valid display order.';

            } elseif (
                !in_array((string) $isPublished, ['0', '1'], true)
            ) {

                $error = 'Please select a valid visibility option.';

            } else {

                try {

                    $startDate = $startDate !== ''
                        ? $startDate
                        : null;

                    $endDate = $endDate !== ''
                        ? $endDate
                        : null;

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE education SET
                                degree = ?,
                                institution = ?,
                                location = ?,
                                start_date = ?,
                                end_date = ?,
                                description = ?,
                                sort_order = ?,
                                is_published = ?
                            WHERE id = ?"
                        );

                        $stmt->execute([
                            $degree,
                            $institution,
                            $location,
                            $startDate,
                            $endDate,
                            $description,
                            $sortOrder,
                            (int) $isPublished,
                            $id
                        ]);

                        $successMessage =
                            'Education updated successfully!';

                    } else {

                        $stmt = $pdo->prepare(
                            "INSERT INTO education (
                                degree,
                                institution,
                                location,
                                start_date,
                                end_date,
                                description,
                                sort_order,
                                is_published
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $degree,
                            $institution,
                            $location,
                            $startDate,
                            $endDate,
                            $description,
                            $sortOrder,
                            (int) $isPublished
                        ]);

                        $successMessage =
                            'New education record added successfully!';

                    }

                    $_SESSION['education_flash'] = $successMessage;

                    header("Location: education.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to save education. Please try again.';

                }
            }
        }
    }
}

// Load all education records

$stmt = $pdo->query(
    "SELECT * FROM education
     ORDER BY sort_order ASC, id ASC"
);

$allEducation = $stmt->fetchAll();

// Active sidebar item

$activePage = 'education';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Education Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Education Management</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">ACADEMIC BACKGROUND</p>

            <h2>Manage Your Education</h2>

            <p>
                Add and update your degrees, universities,
                academic details and qualifications.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo educationEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo educationEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Add or Edit Education -->

        <section class="editor-section" id="education-form">

            <h2>
                <?php echo !empty($formData['id']) ? 'Edit Education' : 'Add New Education'; ?>
            </h2>

            <p class="editor-note">
                Enter your degree, university and academic information.
            </p>

            <form method="POST" action="education.php#education-form">

                <input
                    type="hidden"
                    name="education_token"
                    value="<?php echo educationEscape($_SESSION['education_token']); ?>"
                >

                <input type="hidden" name="action" value="save">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $formData['id']; ?>"
                >

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Degree / Qualification *</label>

                        <input
                            type="text"
                            name="degree"
                            maxlength="150"
                            value="<?php echo educationEscape($formData['degree']); ?>"
                            placeholder="Example: BS Computer Science"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Institution / University *</label>

                        <input
                            type="text"
                            name="institution"
                            maxlength="200"
                            value="<?php echo educationEscape($formData['institution']); ?>"
                            placeholder="University name"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Location</label>

                        <input
                            type="text"
                            name="location"
                            maxlength="150"
                            value="<?php echo educationEscape($formData['location']); ?>"
                            placeholder="Islamabad, Pakistan"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Start Date</label>

                        <input
                            type="date"
                            name="start_date"
                            value="<?php echo educationEscape($formData['start_date'] ?? ''); ?>"
                        >

                    </div>

                    <div class="editor-field">

                        <label>End Date</label>

                        <input
                            type="date"
                            name="end_date"
                            value="<?php echo educationEscape($formData['end_date'] ?? ''); ?>"
                        >

                        <small>
                            Leave blank if you are still studying.
                        </small>

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

                        <label>Additional Academic Details</label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Example: Currently enrolled — 4th Semester"
                        ><?php echo educationEscape($formData['description']); ?></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="save-btn">

                        <?php echo !empty($formData['id']) ? 'Update Education' : 'Add Education'; ?>

                    </button>

                    <?php if (!empty($formData['id'])): ?>

                        <a href="education.php" class="back-link">
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Education Records -->

        <section class="editor-section">

            <h2>Your Education Records</h2>

            <p class="editor-note">
                Manage your existing academic qualifications below.
            </p>

            <?php if (empty($allEducation)): ?>

                <p>No education records have been added yet.</p>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table">

                        <thead>

                            <tr>
                                <th>Degree</th>
                                <th>Institution</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allEducation as $item): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo educationEscape($item['degree']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo educationEscape($item['institution']); ?>
                                    </td>

                                    <td>
                                        <?php echo educationEscape($item['location']); ?>
                                    </td>

                                    <td>

                                        <?php if ($item['is_published']): ?>

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
                                                href="education.php?edit=<?php echo (int) $item['id']; ?>#education-form"
                                                class="edit-link"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="education.php"
                                                onsubmit="return confirm('Are you sure you want to delete this education record?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="education_token"
                                                    value="<?php echo educationEscape($_SESSION['education_token']); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $item['id']; ?>"
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
