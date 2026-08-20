<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// PHPMailer
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// Mail configuration
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (is_logged_in()) {
    redirect('/allhotels/index.php');
}

$error = null;
$old = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Only Hotel Owner registration
    $type = 'owner';

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $businessAddr = trim($_POST['business_address'] ?? '');

    $old = $_POST;


    // =========================
    // VALIDATION
    // =========================

    if (
        $fullName === '' ||
        $email === '' ||
        strlen($password) < 6 ||
        $phone === '' ||
        $businessAddr === ''
    ) {

        $error =
            'Please fill all required fields. Password must be at least 6 characters.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        // =========================
        // CHECK USERS TABLE
        // =========================

        $check = $pdo->prepare(
            "SELECT id, is_verified
             FROM users
             WHERE email = ?"
        );

        $check->execute([$email]);

        $existingUser = $check->fetch();


        if ($existingUser) {

            if ((int)$existingUser['is_verified'] === 1) {

                $error =
                    'An account with this email already exists. Please log in.';

            } else {

                $error =
                    'This email has an existing unverified account. Please complete email verification.';
            }

        } else {

            // =========================
            // CHECK PENDING REGISTRATION
            // =========================

            $pendingCheck = $pdo->prepare(
                "SELECT id
                 FROM pending_registrations
                 WHERE email = ?"
            );

            $pendingCheck->execute([$email]);

            $pending = $pendingCheck->fetch();


            // =========================
            // PASSWORD HASH
            // =========================

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // =========================
            // GENERATE OTP
            // =========================

            $otp = str_pad(
                (string) random_int(0, 999999),
                6,
                '0',
                STR_PAD_LEFT
            );


            // OTP expires in 5 minutes
            $otpExpires = date(
                'Y-m-d H:i:s',
                time() + (5 * 60)
            );


            try {

                // =========================
                // SAVE / UPDATE PENDING USER
                // =========================

                if ($pending) {

                    // Existing pending registration
                    // Update registration details and OTP

                    $stmt = $pdo->prepare(
                        "UPDATE pending_registrations
                         SET
                            full_name = ?,
                            password_hash = ?,
                            phone = ?,
                            whatsapp = ?,
                            business_address = ?,
                            role = ?,
                            otp_code = ?,
                            otp_expires_at = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $fullName,
                        $hash,
                        $phone,
                        $whatsapp,
                        $businessAddr,
                        $type,
                        $otp,
                        $otpExpires,
                        $pending['id']
                    ]);

                } else {

                    // New pending registration

                    $stmt = $pdo->prepare(
                        "INSERT INTO pending_registrations
                        (
                            full_name,
                            email,
                            password_hash,
                            phone,
                            whatsapp,
                            business_address,
                            role,
                            otp_code,
                            otp_expires_at
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $fullName,
                        $email,
                        $hash,
                        $phone,
                        $whatsapp,
                        $businessAddr,
                        $type,
                        $otp,
                        $otpExpires
                    ]);
                }


                // =========================
                // SEND OTP EMAIL
                // =========================

                $mail = new PHPMailer(true);

                $mail->isSMTP();

                $mail->Host = MAIL_HOST;

                $mail->SMTPAuth = true;

                $mail->Username = MAIL_USERNAME;

                $mail->Password = MAIL_PASSWORD;

                $mail->SMTPSecure =
                    PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port = MAIL_PORT;


                $mail->setFrom(
                    MAIL_FROM_EMAIL,
                    MAIL_FROM_NAME
                );


                $mail->addAddress(
                    $email,
                    $fullName
                );


                $mail->isHTML(true);

                $mail->Subject =
                    'AllHotels.lk - Email Verification OTP';


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
                            margin-bottom: 20px;
                        ">
                            Welcome to AllHotels.lk
                        </h2>


                        <p>
                            Hello
                            <strong>
                                ' . htmlspecialchars($fullName) . '
                            </strong>,
                        </p>


                        <p>
                            Thank you for registering as a
                            Hotel Owner on AllHotels.lk.
                        </p>


                        <p>
                            Please use the following OTP to
                            verify your email address:
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
                            If you did not create this account,
                            you can safely ignore this email.
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
                    "Your AllHotels.lk verification OTP is: "
                    . $otp
                    . ". This OTP expires in 5 minutes.";


                // Send
                $mail->send();


                // =========================
                // SAVE EMAIL IN SESSION
                // =========================

                $_SESSION['verify_email'] = $email;


                // =========================
                // REDIRECT OTP PAGE
                // =========================

                redirect('../auth/verify-otp.php');


            } catch (Exception $e) {

                /*
                 * If email sending fails,
                 * remove pending registration.
                 */

                $delete = $pdo->prepare(
                    "DELETE FROM pending_registrations
                     WHERE email = ?"
                );

                $delete->execute([$email]);


                $error =
                    'We could not send the verification email. '
                    . 'Please check your email settings and try again.';
            }
        }
    }
}


$page_title = 'Hotel Owner Registration';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">

            <h2>Register as a Hotel Owner</h2>

            <p class="auth-sub">
                Create your Hotel Owner account and list your property on AllHotels.lk.
            </p>


            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST">

                <!-- Full Name -->
                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= h($old['full_name'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- Email -->
                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= h($old['email'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- Password -->
                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="6"
                        required
                    >

                </div>


                <!-- Phone -->
                <div class="form-group">

                    <label for="phone">
                        Contact Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= h($old['phone'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- WhatsApp -->
                <div class="form-group">

                    <label for="whatsapp">
                        WhatsApp Number
                    </label>

                    <input
                        type="text"
                        id="whatsapp"
                        name="whatsapp"
                        value="<?= h($old['whatsapp'] ?? '') ?>"
                    >

                </div>


                <!-- Business Address -->
                <div class="form-group">

                    <label for="business_address">
                        Business Address
                    </label>

                    <input
                        type="text"
                        id="business_address"
                        name="business_address"
                        value="<?= h($old['business_address'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- Submit -->
                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Register as Hotel Owner
                </button>

            </form>


            <div class="auth-foot">

                Already have an account?

                <a href="../auth/login.php">
                    Log in
                </a>

            </div>

        </div>

    </div>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>