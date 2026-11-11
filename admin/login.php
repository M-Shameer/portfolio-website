
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

// If already logged in, redirect to dashboard.
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
        !hash_equals($_SESSION['login_token'], $submittedToken)
    ) {
        $message = "Invalid request. Please refresh the page.";

    } else {

        // Get form values.
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input on the server.
        if (
            !is_string($identifier) ||
            !is_string($password) ||
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
</head>

<body>

    <h1>Admin Login</h1>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="">

        <input
            type="hidden"
            name="login_token"
            value="<?php echo htmlspecialchars($_SESSION['login_token'], ENT_QUOTES, 'UTF-8'); ?>"
        >

        <label>Username or Email</label>
        <br>

        <input
            type="text"
            name="identifier"
            maxlength="254"
            autocomplete="username"
            required
        >

        <br><br>

        <label>Password</label>
        <br>

        <input
            type="password"
            name="password"
            maxlength="4096"
            autocomplete="current-password"
            required
        >

        <br><br>

        <button type="submit">Login</button>

    </form>

</body>
</html>
