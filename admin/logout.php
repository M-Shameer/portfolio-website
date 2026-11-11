
<?php

session_start();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['logout_token'], $_SESSION['logout_token']) ||
    !is_string($_POST['logout_token']) ||
    !is_string($_SESSION['logout_token']) ||
    !hash_equals($_SESSION['logout_token'], $_POST['logout_token'])
) {
    http_response_code(403);
    exit("Invalid logout request.");
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);

}

session_destroy();

header("Location: login.php");
exit;

?>
