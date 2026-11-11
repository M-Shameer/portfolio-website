
<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

function messageEscape($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Security token

if (empty($_SESSION['messages_token'])) {
    $_SESSION['messages_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['messages_flash'] ?? '';
unset($_SESSION['messages_flash']);

$error = '';

// Handle actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['messages_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['messages_token'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        $id = filter_var(
            $_POST['id'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id < 1) {

            $error = 'Invalid message record.';

        } elseif ($action === 'update_status') {

            $status = $_POST['status'] ?? '';

            if (!in_array($status, ['new', 'read', 'replied'], true)) {

                $error = 'Please select a valid message status.';

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "UPDATE contact_messages
                         SET status = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([$status, $id]);

                    $_SESSION['messages_flash'] =
                        'Message status updated successfully!';

                    header("Location: messages.php");
                    exit;

                } catch (PDOException $ex) {

                    error_log($ex->getMessage());

                    $error = 'Unable to update message status.';

                }
            }

        } elseif ($action === 'delete') {

            try {

                $stmt = $pdo->prepare(
                    "DELETE FROM contact_messages WHERE id = ?"
                );

                $stmt->execute([$id]);

                $_SESSION['messages_flash'] =
                    'Message deleted successfully!';

                header("Location: messages.php");
                exit;

            } catch (PDOException $ex) {

                error_log($ex->getMessage());

                $error = 'Unable to delete this message.';

            }

        } else {

            $error = 'Invalid action.';

        }
    }
}

// Load all contact messages

$stmt = $pdo->query(
    "SELECT * FROM contact_messages
     ORDER BY created_at DESC, id DESC"
);

$allMessages = $stmt->fetchAll();

// Count messages by status

$newCount = 0;
$readCount = 0;
$repliedCount = 0;

foreach ($allMessages as $item) {

    if ($item['status'] === 'new') {
        $newCount++;
    } elseif ($item['status'] === 'read') {
        $readCount++;
    } elseif ($item['status'] === 'replied') {
        $repliedCount++;
    }

}

$activePage = 'messages';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Messages | Portfolio CMS</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>
                <p class="eyebrow">COMMUNICATION</p>
                <h1>Contact Messages</h1>
            </div>

            <a href="dashboard.php" class="back-link">
                ← Dashboard
            </a>

        </header>

        <section class="welcome-panel">

            <p class="welcome-label">YOUR INBOX</p>

            <h2>Manage Visitor Messages</h2>

            <p>
                Read messages received through your portfolio,
                update their status and manage your inbox.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="form-message">
                <?php echo messageEscape($message); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="form-message error-message">
                <?php echo messageEscape($error); ?>
            </div>

        <?php endif; ?>

        <!-- Message Statistics -->

        <section class="stats-grid message-stats">

            <div class="stat-card">

                <div class="stat-icon">✉</div>

                <p>New Messages</p>

                <h3><?php echo $newCount; ?></h3>

            </div>

            <div class="stat-card">

                <div class="stat-icon">◉</div>

                <p>Read Messages</p>

                <h3><?php echo $readCount; ?></h3>

            </div>

            <div class="stat-card">

                <div class="stat-icon">✓</div>

                <p>Replied Messages</p>

                <h3><?php echo $repliedCount; ?></h3>

            </div>

            <div class="stat-card">

                <div class="stat-icon">▤</div>

                <p>Total Messages</p>

                <h3><?php echo count($allMessages); ?></h3>

            </div>

        </section>

        <!-- Messages List -->

        <section class="editor-section">

            <h2>Inbox</h2>

            <p class="editor-note">
                Open a message to read its complete contents.
            </p>

            <?php if (empty($allMessages)): ?>

                <div class="empty-messages">

                    <h3>Your inbox is currently empty.</h3>

                    <p>
                        Messages submitted through your website's
                        contact form will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="skills-table-wrap">

                    <table class="skills-table messages-table">

                        <thead>

                            <tr>
                                <th>Sender</th>
                                <th>Subject / Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($allMessages as $item): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php echo messageEscape($item['sender_name']); ?>
                                        </strong>

                                        <br>

                                        <?php echo messageEscape($item['sender_email']); ?>

                                        <br>

                                        <?php if (filter_var($item['sender_email'], FILTER_VALIDATE_EMAIL)): ?>

                                            <a
                                                class="edit-link"
                                                href="mailto:<?php echo messageEscape($item['sender_email']); ?>"
                                            >
                                                Reply by Email
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                    <td class="message-content">

                                        <strong>
                                            <?php echo messageEscape($item['subject'] ?: 'No subject'); ?>
                                        </strong>

                                        <details>

                                            <summary>Read full message</summary>

                                            <p>
                                                <?php echo nl2br(messageEscape($item['message'])); ?>
                                            </p>

                                        </details>

                                    </td>

                                    <td>

                                        <?php

                                        echo !empty($item['created_at'])
                                            ? messageEscape(
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime($item['created_at'])
                                                )
                                            )
                                            : 'Not available';

                                        ?>

                                    </td>

                                    <td>

                                        <?php if ($item['status'] === 'new'): ?>

                                            <span class="status-new">New</span>

                                        <?php elseif ($item['status'] === 'read'): ?>

                                            <span class="status-published">Read</span>

                                        <?php else: ?>

                                            <span class="status-hidden">Replied</span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="message-actions">

                                            <form
                                                method="POST"
                                                action="messages.php"
                                                class="message-status-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="messages_token"
                                                    value="<?php echo messageEscape($_SESSION['messages_token']); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="update_status"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $item['id']; ?>"
                                                >

                                                <select name="status">

                                                    <option value="new"
                                                        <?php echo $item['status'] === 'new' ? 'selected' : ''; ?>>
                                                        New
                                                    </option>

                                                    <option value="read"
                                                        <?php echo $item['status'] === 'read' ? 'selected' : ''; ?>>
                                                        Read
                                                    </option>

                                                    <option value="replied"
                                                        <?php echo $item['status'] === 'replied' ? 'selected' : ''; ?>>
                                                        Replied
                                                    </option>

                                                </select>

                                                <button type="submit" class="save-btn">
                                                    Update
                                                </button>

                                            </form>

                                            <form
                                                method="POST"
                                                action="messages.php"
                                                onsubmit="return confirm('Are you sure you want to delete this message?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="messages_token"
                                                    value="<?php echo messageEscape($_SESSION['messages_token']); ?>"
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
