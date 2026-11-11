
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';
require_once '../includes/upload_helper.php';

// Escape displayed information

function certificateEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate date format

function certificateValidDate($date) {

    if ($date === '') {
        return true;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
}

// Security token

if (empty($_SESSION['certifications_token'])) {
    $_SESSION['certifications_token'] = bin2hex(random_bytes(32));
}

// Notifications

$message = $_SESSION['certifications_flash'] ?? '';
unset($_SESSION['certifications_flash']);

$error = '';

// Empty form

$formData = [
    'id' => 0,
    'certificate_name' => '',
    'issuing_organization' => '',
    'issue_date' => '',
    'certificate_file' => '',
    'description' => '',
    'sort_order' => 0,
    'is_published' => 1
];

// Load certificate for editing

if (isset($_GET['edit'])) {

    $editId = filter_input(
        INPUT_GET,
        'edit',
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM certifications WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $existingCertificate = $stmt->fetch();

        if ($existingCertificate) {
            $formData = $existingCertificate;
        } else {
            $error = 'The selected certificate was not found.';
        }
    }
}

// Handle submitted forms

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['certifications_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['certifications_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        // Delete certificate

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id < 1) {

                $error = 'Invalid certificate record.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "DELETE FROM certifications WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    if ($stmt->rowCount() === 0) {
                        $error = 'The certificate was not found.';
                    } else {

                        $_SESSION['certifications_flash'] =
                            'Certificate deleted successfully!';

                        header("Location: certifications.php");
                        exit;

                    }

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to delete this certificate.';

                }
            }
        }

        // Add or update certificate

        elseif ($action === 'save') {

            $id = filter_var(
                $_POST['id'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $name = trim($_POST['certificate_name'] ?? '');
            $organization = trim($_POST['issuing_organization'] ?? '');
            $issueDate = trim($_POST['issue_date'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $sortOrder = filter_var(
                $_POST['sort_order'] ?? '0',
                FILTER_VALIDATE_INT
            );

            $isPublished = $_POST['is_published'] ?? '1';

            $existingFile = '';

            // Find the existing certificate file when editing

            if ($id !== false && $id > 0) {

                try {

                    $check = $pdo->prepare(
                        "SELECT certificate_file
                         FROM certifications
                         WHERE id = ?"
                    );

                    $check->execute([$id]);

                    $existingRecord = $check->fetch();

                    if (!$existingRecord) {

                        $error = 'The certificate you are editing no longer exists.';

                    } else {

                        $existingFile = $existingRecord['certificate_file'] ?? '';

                    }

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to retrieve the existing certificate.';

                }
            }

            // Preserve submitted values and the existing file path

            $formData = [
                'id' => $id === false ? 0 : $id,
                'certificate_name' => $name,
                'issuing_organization' => $organization,
                'issue_date' => $issueDate,
                'certificate_file' => $existingFile,
                'description' => $description,
                'sort_order' => $sortOrder === false ? 0 : $sortOrder,
                'is_published' => $isPublished
            ];

            // Validate certificate information

            if ($error !== '') {

                // Keep the existing error.

            } elseif ($id === false || $id < 0) {

                $error = 'Invalid certificate record.';

            } elseif ($name === '') {

                $error = 'Certificate name is required.';

            } elseif (
                strlen($name) > 200 ||
                strlen($organization) > 200
            ) {

                $error = 'One or more fields exceed their allowed length.';

            } elseif (!certificateValidDate($issueDate)) {

                $error = 'Please enter a valid issue date.';

            } elseif ($sortOrder === false || $sortOrder < 0) {

                $error = 'Please enter a valid display order.';

            } elseif (
                !in_array((string) $isPublished, ['0', '1'], true)
            ) {

                $error = 'Please select a valid visibility option.';

            } else {

                try {

                    // Upload a new PDF.
                    // If no PDF is selected, keep the existing file.

                    $newFile = savePortfolioUpload(
                        $_FILES['certificate_file'] ?? [],
                        'pdf'
                    );

                    $fileToSave = $newFile ?? (
                        $existingFile !== '' ? $existingFile : null
                    );

                    $issueDate = $issueDate !== ''
                        ? $issueDate
                        : null;

                    // Update existing certificate

                    if ($id > 0) {

                        $stmt = $pdo->prepare(
                            "UPDATE certifications SET
                                certificate_name = ?,
                                issuing_organization = ?,
                                issue_date = ?,
                                certificate_file = ?,
                                description = ?,
                                sort_order = ?,
                                is_published = ?
                            WHERE id = ?"
                        );

                        $stmt->execute([
                            $name,
                            $organization,
                            $issueDate,
                            $fileToSave,
                            $description,
                            $sortOrder,
                            (int) $isPublished,
                            $id
                        ]);

                        $successMessage =
                            'Certificate updated successfully!';

                    } else {

                        // Insert new certificate

                        $stmt = $pdo->prepare(
                            "INSERT INTO certifications (
                                certificate_name,
                                issuing_organization,
                                issue_date,
                                certificate_file,
                                description,
                                sort_order,
                                is_published
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $name,
                            $organization,
                            $issueDate,
                            $fileToSave,
                            $description,
                            $sortOrder,
                            (int) $isPublished
                        ]);

                        $successMessage =
                            'New certificate added successfully!';

                    }

                    $_SESSION['certifications_flash'] = $successMessage;

                    header("Location: certifications.php");
                    exit;

                } catch (RuntimeException $ex) {

                    $error = $ex->getMessage();

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to save the certificate. Please try again.';

                }
            }
        }
    }
}

// Load all certificates

$stmt = $pdo->query(
    "SELECT * FROM certifications
     ORDER BY sort_order ASC, id ASC"
);

$allCertificates = $stmt->fetchAll();

$activePage = 'certifications';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Certifications Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">CONTENT MANAGEMENT</p>
                <h1>Certifications Management</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">PROFESSIONAL DEVELOPMENT</p>

            <h2>Manage Your Certifications</h2>

            <p>
                Add your certificates, issuing organizations,
                issue dates and certificate documents.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo certificateEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo certificateEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Add or Edit Certificate -->

        <section class="editor-section" id="certificate-form">

            <h2>
                <?php echo !empty($formData['id']) ? 'Edit Certificate' : 'Add New Certificate'; ?>
            </h2>

            <p class="editor-note">
                Enter your certificate information below.
            </p>

            <form
                method="POST"
                enctype="multipart/form-data"
                action="certifications.php#certificate-form"
            >

                <input
                    type="hidden"
                    name="certifications_token"
                    value="<?php echo certificateEscape($_SESSION['certifications_token']); ?>"
                >

                <input type="hidden" name="action" value="save">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $formData['id']; ?>"
                >

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Certificate Name *</label>

                        <input
                            type="text"
                            name="certificate_name"
                            maxlength="200"
                            value="<?php echo certificateEscape($formData['certificate_name']); ?>"
                            placeholder="Web Development Internship Completion Certificate"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Issuing Organization</label>

                        <input
                            type="text"
                            name="issuing_organization"
                            maxlength="200"
                            value="<?php echo certificateEscape($formData['issuing_organization']); ?>"
                            placeholder="National Information Technology Board"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Issue Date</label>

                        <input
                            type="date"
                            name="issue_date"
                            value="<?php echo certificateEscape($formData['issue_date'] ?? ''); ?>"
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

                    <!-- Certificate PDF Upload -->

                    <div class="editor-field full">

                        <label>Upload Certificate PDF</label>

                        <input
                            type="file"
                            name="certificate_file"
                            accept=".pdf,application/pdf"
                        >

                        <?php if (!empty($formData['certificate_file'])): ?>

                            <small>
                                <a
                                    href="../<?php echo certificateEscape($formData['certificate_file']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    View Current Certificate
                                </a>
                            </small>

                        <?php else: ?>

                            <small>
                                No certificate file uploaded yet.
                            </small>

                        <?php endif; ?>

                        <small>
                            PDF only. Maximum size: 10 MB.
                            Leave empty to keep the existing file.
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

                        <label>Description</label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Enter certificate details or duration."
                        ><?php echo certificateEscape($formData['description']); ?></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="save-btn">

                        <?php echo !empty($formData['id']) ? 'Update Certificate' : 'Add Certificate'; ?>

                    </button>

                    <?php if (!empty($formData['id'])): ?>

                        <a href="certifications.php" class="back-link">
                            Cancel Editing
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Certificates -->

        <section class="editor-section">

            <h2>Your Certificates</h2>

            <p class="editor-note">
                Edit, hide or delete your existing certificates.
            </p>

            <?php if (empty($allCertificates)): ?>

                <p>No certificates have been added yet.</p>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table">

                        <thead>

                            <tr>
                                <th>Certificate</th>
                                <th>Organization</th>
                                <th>Issue Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allCertificates as $certificate): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo certificateEscape($certificate['certificate_name']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo certificateEscape($certificate['issuing_organization']); ?>
                                    </td>

                                    <td>
                                        <?php echo certificateEscape($certificate['issue_date'] ?: 'Not specified'); ?>
                                    </td>

                                    <td>

                                        <?php if ($certificate['is_published']): ?>

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
                                                href="certifications.php?edit=<?php echo (int) $certificate['id']; ?>#certificate-form"
                                                class="edit-link"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="certifications.php"
                                                onsubmit="return confirm('Are you sure you want to delete this certificate?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="certifications_token"
                                                    value="<?php echo certificateEscape($_SESSION['certifications_token']); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $certificate['id']; ?>"
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
