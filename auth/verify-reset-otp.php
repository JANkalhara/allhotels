<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = null;

$email = $_SESSION['reset_email'] ?? '';

if ($email === '') {
    redirect('../auth/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp)) {

        $error = 'Please enter a valid 6-digit OTP.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                email,
                reset_otp,
                reset_otp_expires_at
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $error = 'Account not found. Please try again.';

        } elseif ($user['reset_otp'] !== $otp) {

            $error = 'Invalid OTP. Please check your email and try again.';

        } elseif (
            empty($user['reset_otp_expires_at']) ||
            strtotime($user['reset_otp_expires_at']) < time()
        ) {

            $error = 'Your OTP has expired. Please request a new one.';

        } else {

            // OTP correct
            $_SESSION['reset_verified'] = true;

            redirect('../auth/reset-password.php');
        }
    }
}

$page_title = 'Verify Reset OTP';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">

            <h2>Verify OTP</h2>

            <p class="auth-sub">
                Enter the 6-digit OTP sent to your email.
            </p>

            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <div style="
                text-align:center;
                margin-bottom:25px;
            ">

                <strong>
                    <?= h($email) ?>
                </strong>

            </div>

            <form method="POST">

                <div class="form-group">

                    <label for="otp">
                        Enter OTP
                    </label>

                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        maxlength="6"
                        minlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        placeholder="Enter 6-digit OTP"
                        autocomplete="one-time-code"
                        required
                        autofocus
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Verify OTP
                </button>

            </form>

            <div class="auth-foot">

                Didn't receive the code?

                <a href="../auth/forgot-password.php">
                    Send Again
                </a>

            </div>

            <div class="auth-foot">

                <a href="../auth/login.php">
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>