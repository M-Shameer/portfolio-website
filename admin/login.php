
<?php

// Configure session security before starting the session.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    ),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once '../config/database.php';

// Redirect if already logged in.
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Generate CSRF token.
if (empty($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validate CSRF token.
    $submittedToken = $_POST['login_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !isset($_SESSION['login_token']) ||
        !is_string($_SESSION['login_token']) ||
        !hash_equals($_SESSION['login_token'], $submittedToken)
    ) {
        $message = "Invalid request. Please refresh the page.";

    } else {

        // Get form values.
        $identifierInput = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validate input types and lengths.
        if (
            !is_string($identifierInput) ||
            !is_string($password)
        ) {
            $message = "Please enter a valid username/email and password.";

        } else {

            $identifier = trim($identifierInput);

            if (
                $identifier === '' ||
                $password === '' ||
                strlen($identifier) > 254 ||
                strlen($password) > 4096
            ) {
                $message = "Please enter a valid username/email and password.";

            } else {

                // Find admin by username or email.
                $stmt = $pdo->prepare(
                    "SELECT id, username, password_hash
                     FROM admin_users
                     WHERE username = :username OR email = :email
                     LIMIT 1"
                );

                $stmt->execute([
                    ':username' => $identifier,
                    ':email' => $identifier
                ]);

                $admin = $stmt->fetch();

                // Verify password.
                if (
                    $admin &&
                    password_verify($password, $admin['password_hash'])
                ) {

                    // Regenerate session ID after successful login.
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];

                    // Remove login CSRF token.
                    unset($_SESSION['login_token']);

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $message = "Incorrect username/email or password.";

                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Shameer Portfolio</title>

    <style>

        /* General Reset */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #201F1D;
            color: #FAF9F6;
        }

        /* Main Layout */

        .login-container {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Left Branding Section */

        .branding-section {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 55px 65px;
            background:
                radial-gradient(circle at 15% 85%, rgba(198, 155, 114, 0.16), transparent 35%),
                linear-gradient(145deg, #292724, #171614);
        }

        .branding-section::before {
            content: "";
            position: absolute;
            width: 380px;
            height: 380px;
            border: 1px solid rgba(198, 155, 114, 0.16);
            border-radius: 50%;
            top: 20%;
            right: -170px;
        }

        .branding-section::after {
            content: "";
            position: absolute;
            width: 270px;
            height: 270px;
            border: 1px solid rgba(198, 155, 114, 0.12);
            border-radius: 50%;
            top: 28%;
            right: -115px;
        }

        .brand-logo {
            position: relative;
            z-index: 1;
            font-size: 21px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .brand-logo span {
            color: #C69B72;
        }

        .branding-content {
            position: relative;
            z-index: 1;
            max-width: 480px;
        }

        .small-heading {
            color: #C69B72;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .branding-content h1 {
            font-size: clamp(38px, 4.5vw, 58px);
            line-height: 1.15;
            margin-bottom: 22px;
            font-weight: 700;
        }

        .branding-content h1 span {
            color: #C69B72;
        }

        .branding-content p {
            color: #BDB9B2;
            font-size: 16px;
            line-height: 1.8;
            max-width: 420px;
        }

        .branding-footer {
            position: relative;
            z-index: 1;
            color: #88837B;
            font-size: 12px;
            letter-spacing: 1px;
        }

        /* Right Login Section */

        .form-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 35px;
            background: #201F1D;
        }

        .login-card {
            width: 100%;
            max-width: 410px;
        }

        .form-heading {
            margin-bottom: 35px;
        }

        .form-heading .small-heading {
            margin-bottom: 14px;
        }

        .form-heading h2 {
            font-size: 34px;
            margin-bottom: 12px;
        }

        .form-heading p {
            color: #A9A49C;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Form Fields */

        .form-group {
            margin-bottom: 23px;
        }

        .form-group label {
            display: block;
            color: #E7E2DA;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 16px;
            border: 1px solid #49443D;
            border-radius: 7px;
            background: #292724;
            color: #FAF9F6;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input::placeholder {
            color: #817B73;
        }

        .form-group input:focus {
            border-color: #C69B72;
            box-shadow: 0 0 0 3px rgba(198, 155, 114, 0.12);
        }

        /* Error Message */

        .error-message {
            padding: 13px 15px;
            margin-bottom: 22px;
            border: 1px solid rgba(220, 90, 90, 0.4);
            border-radius: 7px;
            background: rgba(220, 90, 90, 0.09);
            color: #F2A3A3;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Login Button */

        .login-button {
            width: 100%;
            padding: 16px;
            margin-top: 5px;
            border: none;
            border-radius: 7px;
            background: #C69B72;
            color: #201F1D;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }

        .login-button:hover {
            background: #D8B18C;
            transform: translateY(-2px);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .security-note {
            margin-top: 27px;
            text-align: center;
            color: #817B73;
            font-size: 12px;
            line-height: 1.7;
        }

        .security-note span {
            color: #C69B72;
        }

        /* Responsive Design */

        @media (max-width: 850px) {

            .login-container {
                grid-template-columns: 1fr;
            }

            .branding-section {
                min-height: 300px;
                padding: 35px;
            }

            .branding-content {
                margin-top: 65px;
                margin-bottom: 45px;
            }

            .branding-content h1 {
                font-size: 38px;
            }

            .form-section {
                padding: 55px 25px;
            }
        }

        @media (max-width: 480px) {

            .branding-section {
                min-height: 260px;
                padding: 27px 23px;
            }

            .branding-content {
                margin-top: 50px;
                margin-bottom: 35px;
            }

            .branding-content h1 {
                font-size: 32px;
            }

            .branding-content p {
                font-size: 14px;
            }

            .form-section {
                padding: 45px 23px;
            }

            .form-heading h2 {
                font-size: 29px;
            }
        }

    </style>

</head>

<body>

    <div class="login-container">

        <!-- Left Branding Section -->

        <section class="branding-section">

            <div class="brand-logo">
                SHAMEER<span>.</span>
            </div>

            <div class="branding-content">

                <div class="small-heading">
                    Portfolio Administration
                </div>

                <h1>
                    Welcome to your
                    <span>Workspace.</span>
                </h1>

                <p>
                    Manage your portfolio, update your professional
                    information, showcase your projects, and keep
                    everything organized in one place.
                </p>

            </div>

            <div class="branding-footer">
                © <?php echo date('Y'); ?> SHAMEER. ALL RIGHTS RESERVED.
            </div>

        </section>


        <!-- Right Login Section -->

        <section class="form-section">

            <div class="login-card">

                <div class="form-heading">

                    <div class="small-heading">
                        Admin Portal
                    </div>

                    <h2>Admin Login</h2>

                    <p>
                        Please enter your credentials to access
                        your portfolio dashboard.
                    </p>

                </div>


                <?php if ($message !== ""): ?>

                    <div class="error-message" role="alert">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                <?php endif; ?>


                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="login_token"
                        value="<?php echo htmlspecialchars($_SESSION['login_token'], ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <div class="form-group">

                        <label for="identifier">
                            Username or Email
                        </label>

                        <input
                            type="text"
                            id="identifier"
                            name="identifier"
                            placeholder="Enter your username or email"
                            maxlength="254"
                            autocomplete="username"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            maxlength="4096"
                            autocomplete="current-password"
                            required
                        >

                    </div>


                    <button type="submit" class="login-button">
                        Sign In to Dashboard
                    </button>

                </form>


                <div class="security-note">
                    <span>●</span>
                    Secure administrator access
                    <br>
                    Authorized users only.
                </div>

            </div>

        </section>

    </div>

</body>

</html>
