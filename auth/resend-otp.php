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


// ========================================
// GET EMAIL FROM SESSION
// ========================================

$email = $_SESSION['verify_email'] ?? '';


// If email is not available
if ($email === '') {
    redirect('../register/register.php');
}


// ========================================
// FIND PENDING REGISTRATION
// ========================================

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        email
     FROM pending_registrations
     WHERE email = ?"
);

$stmt->execute([$email]);

$pending = $stmt->fetch();


if (!$pending) {

    $_SESSION['flash_error'] =
        'Registration not found. Please register again.';

    unset($_SESSION['verify_email']);

    redirect('../register/register.php');
}


// ========================================
// GENERATE NEW OTP
// ========================================

try {

    $otp = str_pad(
        (string) random_int(0, 999999),
        6,
        '0',
        STR_PAD_LEFT
    );


    // OTP expires after 5 minutes
    $otpExpires = date(
        'Y-m-d H:i:s',
        time() + (5 * 60)
    );


    // ========================================
    // UPDATE PENDING REGISTRATION
    // ========================================

    $update = $pdo->prepare(
        "UPDATE pending_registrations
         SET
            otp_code = ?,
            otp_expires_at = ?
         WHERE id = ?"
    );

    $update->execute([
        $otp,
        $otpExpires,
        $pending['id']
    ]);


    // ========================================
    // CREATE PHPMailer
    // ========================================

    $mail = new PHPMailer(true);

    $mail->isSMTP();

    $mail->Host = MAIL_HOST;

    $mail->SMTPAuth = true;

    $mail->Username = MAIL_USERNAME;

    $mail->Password = MAIL_PASSWORD;

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = MAIL_PORT;


    // ========================================
    // SENDER
    // ========================================

    $mail->setFrom(
        MAIL_FROM_EMAIL,
        MAIL_FROM_NAME
    );


    // ========================================
    // RECEIVER
    // ========================================

    $mail->addAddress(
        $pending['email'],
        $pending['full_name']
    );


    // ========================================
    // EMAIL
    // ========================================

    $mail->isHTML(true);

    $mail->Subject =
        'AllHotels.lk - New Verification OTP';


    // ========================================
    // EMAIL BODY
    // ========================================

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
                AllHotels.lk
            </h2>


            <p>
                Hello
                <strong>
                    ' . htmlspecialchars($pending['full_name']) . '
                </strong>,
            </p>


            <p>
                You requested a new verification code
                for your AllHotels.lk Hotel Owner account.
            </p>


            <p>
                Your new OTP is:
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
                If you did not request this code,
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


    // Plain text version
    $mail->AltBody =
        "Your new AllHotels.lk verification OTP is: "
        . $otp
        . ". This OTP expires in 5 minutes.";


    // ========================================
    // SEND EMAIL
    // ========================================

    $mail->send();


    // ========================================
    // SUCCESS
    // ========================================

    $_SESSION['flash_otp'] =
        'A new OTP has been sent to your email address.';


} catch (Exception $e) {

    $_SESSION['flash_otp_error'] =
        'Unable to send OTP. Please try again later.';
}


// ========================================
// RETURN TO OTP PAGE
// ========================================

redirect('../auth/verify-otp.php');