<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = null;

$email = $_SESSION['reset_email'] ?? '';
$verified = $_SESSION['reset_verified'] ?? false;

if ($email === '' || !$verified) {
    redirect('../auth/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters long.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match.';

    } else {

        try {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                UPDATE users
                SET password_hash = ?,
                    reset_otp = NULL,
                    reset_otp_expires_at = NULL
                WHERE email = ?
            ");

            $stmt->execute([
                $passwordHash,
                $email
            ]);

            // Clear reset session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);

            $_SESSION['flash_login'] =
                'Your password has been reset successfully. Please log in.';

            redirect('../auth/login.php');

        } catch (PDOException $e) {

            error_log(
                'Password Reset Error: '
                . $e->getMessage()
            );

            $error =
                'Unable to reset your password. Please try again.';
        }
    }
}

$page_title = 'Reset Password';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">

            <h2>Reset Password</h2>

            <p class="auth-sub">
                Create a new password for your AllHotels.lk account.
            </p>

            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="6"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="6"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Reset Password
                </button>

            </form>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>