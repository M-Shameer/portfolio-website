

<?php

// Start the session for contact form security and notifications
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a security token for the contact form
if (empty($_SESSION['contact_token'])) {
    $_SESSION['contact_token'] = bin2hex(random_bytes(32));
}

// Get contact form notification
$contactFlash = $_SESSION['contact_flash'] ?? null;

unset($_SESSION['contact_flash']);

// Connect to the database
require_once __DIR__ . '/config/database.php';


// Load website settings

$stmt = $pdo->query(
    "SELECT setting_key, setting_value FROM site_settings"
);

$siteSettings = [];

foreach ($stmt->fetchAll() as $setting) {
    $siteSettings[$setting['setting_key']] = $setting['setting_value'];
}

$siteSettings = array_merge([
    'website_title' => 'Muhammad Shameer | Portfolio',
    'meta_description' => 'Portfolio of Muhammad Shameer, Computer Science Student and Junior Software Developer.',
    'footer_text' => '© 2026 Muhammad Shameer. All Rights Reserved.',
    'footer_credit' => 'Designed and Developed by Muhammad Shameer'
], $siteSettings);

// Load personal profile

$stmt = $pdo->query(
    "SELECT * FROM profile ORDER BY id ASC LIMIT 1"
);

$profile = $stmt->fetch();

if (!$profile) {

    $profile = [
        'full_name' => 'Muhammad Shameer',
        'headline' => 'Computer Science Student | Junior Software Developer',
        'short_bio' => '',
        'about' => '',
        'contact_intro' => '',
        'location' => 'Pakistan',
        'phone' => '',
        'email' => '',
        'profile_image' => 'assets/images/profile.jpg',
        'resume_file' => '',
        'linkedin_url' => '',
        'github_url' => ''
    ];

}

// Load published skills

$skills = $pdo->query(
    "SELECT * FROM skills
     WHERE is_published = 1
     ORDER BY sort_order, id"
)->fetchAll();

// Load published experience

$experience = $pdo->query(
    "SELECT * FROM experience
     WHERE is_published = 1
     ORDER BY sort_order, id"
)->fetchAll();

// Load published education

$education = $pdo->query(
    "SELECT * FROM education
     WHERE is_published = 1
     ORDER BY sort_order, id"
)->fetchAll();

// Load published certifications

$certifications = $pdo->query(
    "SELECT * FROM certifications
     WHERE is_published = 1
     ORDER BY sort_order, id"
)->fetchAll();

// Load published projects

$projects = $pdo->query(
    "SELECT * FROM projects
     WHERE status = 'published'
     ORDER BY is_featured DESC, sort_order, id"
)->fetchAll();

// Load published social links

$socialLinks = $pdo->query(
    "SELECT * FROM social_links
     WHERE is_published = 1
     ORDER BY sort_order, id"
)->fetchAll();

// Escape displayed text

function portfolioEscape($value) {

    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );

}

// Format dates

function portfolioDate($date) {

    if (empty($date)) {
        return '';
    }

    return date('M Y', strtotime($date));

}

// Format experience and education date ranges

function portfolioDateRange($start, $end, $isCurrent = false, $fallback = '') {

    if ($isCurrent) {
        return 'PRESENT';
    }

    $startDate = portfolioDate($start);
    $endDate = portfolioDate($end);

    if ($startDate !== '' && $endDate !== '') {
        return $startDate . ' — ' . $endDate;
    }

    if ($startDate !== '') {
        return $startDate;
    }

    if ($endDate !== '') {
        return $endDate;
    }

    return $fallback;

}

// Prepare the hero heading

$fullName = trim($profile['full_name'] ?? 'Muhammad Shameer');

$nameParts = preg_split('/\s+/', $fullName, 2);

$firstName = $nameParts[0] ?? '';

$lastName = $nameParts[1] ?? '';

$profileImage = !empty($profile['profile_image'])
    ? $profile['profile_image']
    : 'assets/images/profile.jpg';

// Load the website header

include __DIR__ . '/includes/header.php';

?>

<main>

    <!-- About Section -->

    <section class="section about-section" id="about">

        <p class="section-label">GET TO KNOW ME</p>

        <h2>About Me</h2>

        <p>
            <?php echo nl2br(portfolioEscape($profile['about'])); ?>
        </p>

    </section>


    <!-- Skills Section -->

    <section class="section skills-section" id="skills">

        <p class="section-label">WHAT I WORK WITH</p>

        <h2>Technical Skills</h2>

        <div class="skills-grid">

            <?php foreach ($skills as $skill): ?>

                <div class="skill-card">

                    <h3>
                        <?php echo portfolioEscape($skill['category']); ?>
                    </h3>

                    <p>
                        <?php echo portfolioEscape($skill['skill_name']); ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- Experience Section -->

    <section class="section experience-section" id="experience">

        <p class="section-label">MY PROFESSIONAL JOURNEY</p>

        <h2>Experience</h2>

        <?php foreach ($experience as $job): ?>

            <div class="experience-card">

                <p class="experience-date">

                    <?php

                    echo portfolioEscape(
                        portfolioDateRange(
                            $job['start_date'],
                            $job['end_date'],
                            $job['is_current'],
                            $job['employment_type']
                        )
                    );

                    ?>

                </p>

                <h3>
                    <?php echo portfolioEscape($job['job_title']); ?>
                </h3>

                <h4>
                    <?php echo portfolioEscape($job['company']); ?>
                </h4>

                <?php if (!empty($job['location'])): ?>

                    <p>
                        <?php echo portfolioEscape($job['location']); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($job['description'])): ?>

                    <p>
                        <?php echo nl2br(portfolioEscape($job['description'])); ?>
                    </p>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </section>


    <!-- Education Section -->

    <section class="section education-section" id="education">

        <p class="section-label">MY ACADEMIC BACKGROUND</p>

        <h2>Education</h2>

        <?php foreach ($education as $item): ?>

            <div class="education-card">

                <h3>
                    <?php echo portfolioEscape($item['degree']); ?>
                </h3>

                <h4>
                    <?php echo portfolioEscape($item['institution']); ?>
                </h4>

                <?php if (!empty($item['location'])): ?>

                    <p>
                        <?php echo portfolioEscape($item['location']); ?>
                    </p>

                <?php endif; ?>

                <?php

                $educationPeriod = portfolioDateRange(
                    $item['start_date'],
                    $item['end_date'],
                    false
                );

                ?>

                <?php if ($educationPeriod !== ''): ?>

                    <p>
                        <?php echo portfolioEscape($educationPeriod); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($item['description'])): ?>

                    <p>
                        <?php echo nl2br(portfolioEscape($item['description'])); ?>
                    </p>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </section>


    <!-- Certifications Section -->

    <section class="section certification-section" id="certifications">

        <p class="section-label">PROFESSIONAL DEVELOPMENT</p>

        <h2>Certifications</h2>

        <?php foreach ($certifications as $certificate): ?>

            <div class="certification-card">

                <h3>
                    <?php echo portfolioEscape($certificate['certificate_name']); ?>
                </h3>

                <p>
                    <?php echo portfolioEscape($certificate['issuing_organization']); ?>
                </p>

                <?php if (!empty($certificate['issue_date'])): ?>

                    <p>
                        Issued:
                        <?php echo portfolioEscape(portfolioDate($certificate['issue_date'])); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($certificate['description'])): ?>

                    <p class="certificate-note">
                        <?php echo nl2br(portfolioEscape($certificate['description'])); ?>
                    </p>

                <?php endif; ?>

                <?php if (!empty($certificate['certificate_file'])): ?>

                    <a
                        href="<?php echo portfolioEscape($certificate['certificate_file']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        View Certificate →
                    </a>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </section>


    <!-- Projects Section -->

    <section class="section projects-section" id="projects">

        <p class="section-label">MY RECENT WORK</p>

        <h2>Featured Projects</h2>

        <div class="project-grid">

            <?php foreach ($projects as $project): ?>

                <article class="project-card">

                    <?php if (!empty($project['image_path'])): ?>

                        <img
                            src="<?php echo portfolioEscape($project['image_path']); ?>"
                            alt="<?php echo portfolioEscape($project['project_name']); ?>"
                        >

                    <?php endif; ?>

                    <h3>
                        <?php echo portfolioEscape($project['project_name']); ?>
                    </h3>

                    <p>
                        <?php echo nl2br(portfolioEscape($project['description'])); ?>
                    </p>

                    <?php if (!empty($project['technologies'])): ?>

                        <p class="project-tech">
                            <?php echo portfolioEscape($project['technologies']); ?>
                        </p>

                    <?php endif; ?>

                    <?php if (!empty($project['github_url'])): ?>

                        <a
                            href="<?php echo portfolioEscape($project['github_url']); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            View GitHub Project →
                        </a>

                    <?php endif; ?>

                    <?php if (!empty($project['live_url'])): ?>

                        <a
                            href="<?php echo portfolioEscape($project['live_url']); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            View Live Project →
                        </a>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- Contact Section -->

    
<!-- Contact Section -->

<section class="section contact-section" id="contact">

    <p class="section-label">LET'S CONNECT</p>

    <h2>Get In Touch</h2>

    <p>
        <?php echo nl2br(portfolioEscape($profile['contact_intro'] ?? '')); ?>
    </p>

    <div class="contact-details">

        <?php if (!empty($profile['email'])): ?>

            <p>
                Email:

                <a href="mailto:<?php echo portfolioEscape($profile['email']); ?>">
                    <?php echo portfolioEscape($profile['email']); ?>
                </a>
            </p>

        <?php endif; ?>

        <?php if (!empty($profile['phone'])): ?>

            <p>
                Phone:

                <a href="tel:<?php echo portfolioEscape($profile['phone']); ?>">
                    <?php echo portfolioEscape($profile['phone']); ?>
                </a>
            </p>

        <?php endif; ?>

        <?php if (!empty($profile['location'])): ?>

            <p>
                Location:
                <?php echo portfolioEscape($profile['location']); ?>
            </p>

        <?php endif; ?>

    </div>

    <!-- Contact Form Notification -->

    <?php if (
        is_array($contactFlash) &&
        !empty($contactFlash['message'])
    ): ?>

        <div class="contact-form-status <?php
            echo ($contactFlash['type'] ?? '') === 'success'
                ? 'success'
                : 'error';
        ?>">

            <?php echo portfolioEscape($contactFlash['message']); ?>

        </div>

    <?php endif; ?>

    <!-- Visitor Contact Form -->

    <form
        action="contact_submit.php"
        method="POST"
        class="contact-form"
    >

        <input
            type="hidden"
            name="contact_token"
            value="<?php echo portfolioEscape($_SESSION['contact_token']); ?>"
        >

        <!-- Hidden spam protection field -->

        <div class="contact-honeypot" aria-hidden="true">

            <label for="website">Leave this field empty</label>

            <input
                type="text"
                name="website"
                id="website"
                tabindex="-1"
                autocomplete="off"
            >

        </div>

        <div class="contact-form-group">

            <label for="sender_name">Your Name *</label>

            <input
                type="text"
                id="sender_name"
                name="sender_name"
                placeholder="Enter your full name"
                maxlength="150"
                autocomplete="name"
                required
            >

        </div>

        <div class="contact-form-group">

            <label for="sender_email">Email Address *</label>

            <input
                type="email"
                id="sender_email"
                name="sender_email"
                placeholder="Enter your email address"
                maxlength="150"
                autocomplete="email"
                required
            >

        </div>

        <div class="contact-form-group">

            <label for="subject">Subject</label>

            <input
                type="text"
                id="subject"
                name="subject"
                placeholder="What would you like to discuss?"
                maxlength="200"
            >

        </div>

        <div class="contact-form-group">

            <label for="message">Your Message *</label>

            <textarea
                id="message"
                name="message"
                rows="6"
                maxlength="5000"
                placeholder="Write your message here..."
                required
            ></textarea>

        </div>

        <button type="submit" class="btn btn-primary">
            Send Message
        </button>

    </form>

    <p class="contact-email-note">
        Prefer email?
        <a href="mailto:<?php echo portfolioEscape($profile['email']); ?>">
            Send me an email directly.
        </a>
    </p>

</section>


</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
