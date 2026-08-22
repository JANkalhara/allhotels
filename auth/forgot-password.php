<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {

        $error = 'Please enter your email address.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id, full_name, email
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $error = 'No account found with this email address.';

        } else {

            try {

                // Generate 6 digit OTP
                $otp = str_pad(
                    (string) random_int(0, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                // OTP valid for 5 minutes
                $expires = date(
                    'Y-m-d H:i:s',
                    time() + (5 * 60)
                );

                // Save OTP
                $update = $pdo->prepare("
                    UPDATE users
                    SET reset_otp = ?,
                        reset_otp_expires_at = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $otp,
                    $expires,
                    $user['id']
                ]);

                // PHPMailer
                $mail = new PHPMailer(true);

                $mail->isSMTP();

                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = MAIL_PORT;

                $mail->setFrom(
                    MAIL_FROM_EMAIL,
                    MAIL_FROM_NAME
                );

                $mail->addAddress(
                    $user['email'],
                    $user['full_name']
                );

                $mail->isHTML(true);

                $mail->Subject =
                    'AllHotels.lk - Password Reset OTP';

                $mail->Body = '

                <div style="
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: auto;
                    padding: 30px;
                    background: #f8fafc;
                ">

                    <div style="
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                    ">

                        <h2 style="
                            color: #2563eb;
                        ">
                            AllHotels.lk
                        </h2>

                        <p>
                            Hello
                            <strong>
                                ' . htmlspecialchars($user['full_name']) . '
                            </strong>,
                        </p>

                        <p>
                            We received a request to reset your
                            AllHotels.lk account password.
                        </p>

                        <p>
                            Your password reset OTP is:
                        </p>

                        <div style="
                            background: #f1f5f9;
                            padding: 20px;
                            text-align: center;
                            margin: 25px 0;
                            border-radius: 8px;
                        ">

                            <span style="
                                font-size: 32px;
                                font-weight: bold;
                                letter-spacing: 8px;
                                color: #111827;
                            ">
                                ' . $otp . '
                            </span>

                        </div>

                        <p>
                            This OTP will expire in
                            <strong>5 minutes</strong>.
                        </p>

                        <p style="color:#64748b;">
                            If you did not request a password reset,
                            please ignore this email.
                        </p>

                        <br>

                        <p>
                            Regards,<br>
                            <strong>AllHotels.lk Team</strong>
                        </p>

                    </div>

                </div>

                ';

                $mail->AltBody =
                    "Your AllHotels.lk password reset OTP is: "
                    . $otp
                    . ". This OTP expires in 5 minutes.";

                $mail->send();

                // Save email in session
                $_SESSION['reset_email'] = $user['email'];

                redirect('../auth/verify-reset-otp.php');

            } catch (Exception $e) {

                error_log(
                    'Password Reset Email Error: '
                    . $e->getMessage()
                );

                $error =
                    'Unable to send OTP. Please try again later.';
            }
        }
    }
}

$page_title = 'Forgot Password';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">

            <h2>Forgot Password?</h2>

            <p class="auth-sub">
                Enter your email address and we will send you
                a 6-digit password reset OTP.
            </p>

            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="example@gmail.com"
                        required
                        autofocus
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Send Reset OTP
                </button>

            </form>

            <div class="auth-foot">

                Remember your password?

                <a href="../auth/login.php">
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>