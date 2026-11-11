
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars(
            $siteSettings['website_title'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>
    </title>

    <meta
        name="description"
        content="<?php echo htmlspecialchars(
            $siteSettings['meta_description'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>"
    >

    <link rel="stylesheet" href="assets/css/style.css?v=3">

</head>

<body>

<header class="hero" id="home">

    <!-- Navigation -->

    <nav class="navbar">

        <a href="index.php" class="logo">
            MS<span>.</span>
        </a>

        <button
            class="menu-toggle"
            id="menu-toggle"
            aria-label="Open navigation menu"
            aria-expanded="false"
        >
            ☰
        </button>

        <ul class="nav-links" id="nav-links">

            <li><a href="#home">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#skills">Skills</a></li>
            <li><a href="#experience">Experience</a></li>
            <li><a href="#education">Education</a></li>
            <li><a href="#certifications">Certifications</a></li>
            <li><a href="#projects">Projects</a></li>
            <li><a href="#contact">Contact</a></li>

        </ul>

    </nav>

    <!-- Hero Layout -->

    <div class="hero-layout">

        <!-- Hero Content -->

        <div class="hero-content">

            <p class="intro">
                Assalam Alaikum, I'M
            </p>

            <h1>
                <?php echo htmlspecialchars(
                    $firstName,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

                <span>
                    <?php echo htmlspecialchars(
                        $lastName,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>
            </h1>

            <h2>
                <?php echo htmlspecialchars(
                    $profile['headline'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </h2>

            <p class="hero-description">
                <?php echo nl2br(
                    htmlspecialchars(
                        $profile['short_bio'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ); ?>
            </p>

            <!-- Hero Buttons -->

            <div class="hero-buttons">

                <a href="#projects" class="btn btn-primary">
                    View My Projects
                </a>

                <a href="#contact" class="btn btn-secondary">
                    Contact Me
                </a>

                <?php if (!empty($profile['resume_file'])): ?>

                    <a
                        href="<?php echo htmlspecialchars(
                            $profile['resume_file'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        class="btn btn-secondary"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        View Resume
                    </a>

                <?php endif; ?>

            </div>

        </div>

        <!-- Profile Photograph -->

        <div class="profile-image">

            <img
                src="<?php echo htmlspecialchars(
                    $profileImage,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                alt="<?php echo htmlspecialchars(
                    $profile['full_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?> - Professional Profile Photo"
            >

        </div>

    </div>

</header>
