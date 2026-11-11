
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';
require_once '../includes/upload_helper.php';

// Security token

if (empty($_SESSION['profile_token'])) {
    $_SESSION['profile_token'] = bin2hex(random_bytes(32));
}

// Escape displayed information

function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Get existing profile

$stmt = $pdo->query(
    "SELECT * FROM profile ORDER BY id ASC LIMIT 1"
);

$existingProfile = $stmt->fetch();

// Default information

$profile = array_merge([
    'full_name' => 'Muhammad Shameer',
    'headline' => 'Computer Science Student | Junior Software Developer',
    'short_bio' => '',
    'about' => '',
    'contact_intro' => '',
    'location' => 'Pakistan',
    'phone' => '03349501062',
    'email' => 'mohammad.shameer762@outlook.com',
    'profile_image' => 'assets/images/profile.jpg',
    'resume_file' => '',
    'github_url' => 'https://github.com/M-Shameer',
    'linkedin_url' => ''
], $existingProfile ?: []);

// Notifications

$message = $_SESSION['profile_flash'] ?? '';
unset($_SESSION['profile_flash']);

$error = '';

// Save profile

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify security token

    if (
        !isset($_POST['profile_token']) ||
        !is_string($_POST['profile_token']) ||
        !hash_equals(
            $_SESSION['profile_token'],
            $_POST['profile_token']
        )
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        // Fields received from the form

        $fields = [
            'full_name',
            'headline',
            'short_bio',
            'about',
            'contact_intro',
            'location',
            'phone',
            'email',
            'github_url',
            'linkedin_url'
        ];

        $data = [];

        foreach ($fields as $field) {

            $value = $_POST[$field] ?? '';

            $data[$field] = is_string($value)
                ? trim($value)
                : '';

        }

        // Preserve existing files unless new files are uploaded

        $data['profile_image'] =
            $profile['profile_image'] ?? 'assets/images/profile.jpg';

        $data['resume_file'] =
            $profile['resume_file'] ?? '';

        // Update displayed values while processing

        $profile = array_merge($profile, $data);

        // Validate information

        if ($data['full_name'] === '') {

            $error = 'Full name is required.';

        } elseif (
            $data['email'] !== '' &&
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
        ) {

            $error = 'Please enter a valid email address.';

        }

        // Validate website links

        foreach (['github_url', 'linkedin_url'] as $field) {

            if ($data[$field] !== '') {

                $scheme = strtolower(
                    parse_url($data[$field], PHP_URL_SCHEME) ?? ''
                );

                if (
                    !filter_var($data[$field], FILTER_VALIDATE_URL) ||
                    !in_array($scheme, ['http', 'https'], true)
                ) {

                    $error = 'Please enter valid HTTP or HTTPS profile links.';
                    break;

                }
            }
        }

        // Save information if validation succeeds

        if ($error === '') {

            try {

                // Upload a new profile photograph

                $newImage = savePortfolioUpload(
                    $_FILES['profile_image'] ?? [],
                    'image'
                );

                if ($newImage !== null) {
                    $data['profile_image'] = $newImage;
                }

                // Upload a new resume PDF

                $newResume = savePortfolioUpload(
                    $_FILES['resume_file'] ?? [],
                    'pdf'
                );

                if ($newResume !== null) {
                    $data['resume_file'] = $newResume;
                }

                // Update existing profile

                if (!empty($existingProfile['id'])) {

                    $data['id'] = $existingProfile['id'];

                    $sql = "UPDATE profile SET

                        full_name = :full_name,
                        headline = :headline,
                        short_bio = :short_bio,
                        about = :about,
                        contact_intro = :contact_intro,
                        location = :location,
                        phone = :phone,
                        email = :email,
                        profile_image = :profile_image,
                        resume_file = :resume_file,
                        github_url = :github_url,
                        linkedin_url = :linkedin_url

                        WHERE id = :id";

                } else {

                    // Create the first profile record

                    $sql = "INSERT INTO profile (

                        full_name,
                        headline,
                        short_bio,
                        about,
                        contact_intro,
                        location,
                        phone,
                        email,
                        profile_image,
                        resume_file,
                        github_url,
                        linkedin_url

                    ) VALUES (

                        :full_name,
                        :headline,
                        :short_bio,
                        :about,
                        :contact_intro,
                        :location,
                        :phone,
                        :email,
                        :profile_image,
                        :resume_file,
                        :github_url,
                        :linkedin_url

                    )";

                }

                // Execute database query

                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

                // Redirect after successful save

                $_SESSION['profile_flash'] =
                    'Profile information saved successfully!';

                header("Location: profile.php");
                exit;

            } catch (RuntimeException $ex) {

                // Display upload errors

                $error = $ex->getMessage();

            } catch (PDOException $ex) {

                // Log database errors

                error_log($ex->getMessage());

                $error = 'Unable to save your profile. Please try again.';

            }

        }
    }
}

// Active sidebar section

$activePage = 'profile';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profile Management | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>

                <p class="eyebrow">CONTENT MANAGEMENT</p>

                <h1>Profile Management</h1>

            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">PERSONAL INFORMATION</p>

            <h2>Manage Your Profile</h2>

            <p>
                Edit the personal and professional information displayed
                on your public portfolio.
            </p>

        </section>

        <!-- Success Message -->

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo e($message); ?>
            </div>

        <?php endif; ?>

        <!-- Error Message -->

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo e($error); ?>
            </div>

        <?php endif; ?>

        <!-- Profile Form -->

        <form method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="profile_token"
                value="<?php echo e($_SESSION['profile_token']); ?>"
            >

            <!-- Personal Details -->

            <section class="editor-section">

                <h2>Personal Details</h2>

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Full Name *</label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?php echo e($profile['full_name']); ?>"
                            required
                        >

                    </div>

                    <div class="editor-field">

                        <label>Professional Headline</label>

                        <input
                            type="text"
                            name="headline"
                            value="<?php echo e($profile['headline']); ?>"
                        >

                    </div>

                    <div class="editor-field full">

                        <label>Short Biography</label>

                        <textarea
                            name="short_bio"
                            rows="3"
                        ><?php echo e($profile['short_bio']); ?></textarea>

                    </div>

                    <div class="editor-field full">

                        <label>About Me</label>

                        <textarea
                            name="about"
                            rows="6"
                        ><?php echo e($profile['about']); ?></textarea>

                    </div>

                    <div class="editor-field full">

                        <label>Contact Section Introduction</label>

                        <textarea
                            name="contact_intro"
                            rows="3"
                        ><?php echo e($profile['contact_intro']); ?></textarea>

                    </div>

                </div>

            </section>

            <!-- Contact Information -->

            <section class="editor-section">

                <h2>Contact Information</h2>

                <div class="editor-grid">

                    <div class="editor-field">

                        <label>Email Address</label>

                        <input
                            type="email"
                            name="email"
                            value="<?php echo e($profile['email']); ?>"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Phone Number</label>

                        <input
                            type="text"
                            name="phone"
                            value="<?php echo e($profile['phone']); ?>"
                        >

                    </div>

                    <div class="editor-field">

                        <label>Location</label>

                        <input
                            type="text"
                            name="location"
                            value="<?php echo e($profile['location']); ?>"
                        >

                    </div>

                    <div class="editor-field">

                        <label>GitHub URL</label>

                        <input
                            type="url"
                            name="github_url"
                            value="<?php echo e($profile['github_url']); ?>"
                        >

                    </div>

                    <div class="editor-field">

                        <label>LinkedIn URL</label>

                        <input
                            type="url"
                            name="linkedin_url"
                            value="<?php echo e($profile['linkedin_url']); ?>"
                        >

                    </div>

                </div>

            </section>

            <!-- Profile Media -->

            <section class="editor-section">

                <h2>Profile Media</h2>

                <div class="editor-grid">

                    <!-- Profile Photograph -->

                    <div class="editor-field">

                        <label>Upload Profile Photograph</label>

                        <input
                            type="file"
                            name="profile_image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <?php if (!empty($profile['profile_image'])): ?>

                            <small>Current profile photograph:</small>

                            <img
                                src="../<?php echo e($profile['profile_image']); ?>"
                                alt="Current profile photograph"
                                style="width: 110px; height: 120px; object-fit: cover; border-radius: 8px;"
                            >

                        <?php endif; ?>

                        <small>
                            JPG, PNG or WebP. Maximum size: 5 MB.
                        </small>

                    </div>

                    <!-- Resume PDF -->

                    <div class="editor-field">

                        <label>Upload Resume PDF</label>

                        <input
                            type="file"
                            name="resume_file"
                            accept=".pdf,application/pdf"
                        >

                        <?php if (!empty($profile['resume_file'])): ?>

                            <small>

                                <a
                                    href="../<?php echo e($profile['resume_file']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    View Current Resume
                                </a>

                            </small>

                        <?php endif; ?>

                        <small>
                            PDF only. Maximum size: 10 MB.
                        </small>

                    </div>

                </div>

            </section>

            <!-- Save Button -->

            <div class="profile-save-area">

                <button type="submit" class="save-btn">
                    Save Profile Changes
                </button>

                <p>
                    Your personal information and uploaded files
                    will be saved to your portfolio database.
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
