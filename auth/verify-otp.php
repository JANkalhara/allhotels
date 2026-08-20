<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = null;

// Email stored during registration
$email = $_SESSION['verify_email'] ?? '';


// If email is not available
if ($email === '') {
    redirect('../register/register.php');
}


// ========================================
// HANDLE OTP SUBMISSION
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = trim($_POST['otp'] ?? '');


    // Validate OTP
    if (!preg_match('/^[0-9]{6}$/', $otp)) {

        $error = 'Please enter a valid 6-digit OTP.';

    } else {

        // ========================================
        // GET PENDING REGISTRATION
        // ========================================

        $stmt = $pdo->prepare(
            "SELECT
                id,
                full_name,
                email,
                password_hash,
                phone,
                whatsapp,
                business_address,
                role,
                otp_code,
                otp_expires_at
             FROM pending_registrations
             WHERE email = ?"
        );

        $stmt->execute([$email]);

        $pending = $stmt->fetch();


        // ========================================
        // CHECK ACCOUNT
        // ========================================

        if (!$pending) {

            $error =
                'Registration session not found. Please register again.';


        // ========================================
        // CHECK OTP
        // ========================================

        } elseif ($pending['otp_code'] !== $otp) {

            $error =
                'Invalid OTP. Please check your email and try again.';


        // ========================================
        // CHECK OTP EXPIRATION
        // ========================================

        } elseif (
            empty($pending['otp_expires_at']) ||
            strtotime($pending['otp_expires_at']) < time()
        ) {

            $error =
                'Your OTP has expired. Please request a new OTP.';


        } else {

            // ========================================
            // OTP IS CORRECT
            // ========================================

            try {

                /*
                 * Start database transaction
                 *
                 * This ensures the user is created
                 * safely and pending record is removed.
                 */

                $pdo->beginTransaction();


                // ========================================
                // CHECK USER EMAIL AGAIN
                // ========================================

                $check = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = ?"
                );

                $check->execute([
                    $pending['email']
                ]);

                $existingUser = $check->fetch();


                if ($existingUser) {

                    // Stop transaction
                    $pdo->rollBack();

                    $error =
                        'An account with this email already exists. Please log in.';

                } else {

                    // ========================================
                    // CREATE REAL USER ACCOUNT
                    // ========================================

                    $insert = $pdo->prepare(
                        "INSERT INTO users
                        (
                            full_name,
                            email,
                            password_hash,
                            phone,
                            whatsapp,
                            business_address,
                            role,
                            verify_token,
                            is_verified,
                            otp_code,
                            otp_expires_at
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 1, NULL, NULL)"
                    );


                    $insert->execute([
                        $pending['full_name'],
                        $pending['email'],
                        $pending['password_hash'],
                        $pending['phone'],
                        $pending['whatsapp'],
                        $pending['business_address'],
                        $pending['role']
                    ]);


                    $userId = $pdo->lastInsertId();


                    // ========================================
                    // DELETE PENDING REGISTRATION
                    // ========================================

                    $delete = $pdo->prepare(
                        "DELETE FROM pending_registrations
                         WHERE id = ?"
                    );

                    $delete->execute([
                        $pending['id']
                    ]);


                    // ========================================
                    // COMMIT
                    // ========================================

                    $pdo->commit();


                    // ========================================
                    // WELCOME NOTIFICATION
                    // ========================================

                    notify(
                        $pdo,
                        $userId,
                        'welcome',
                        'Welcome to AllHotels.lk! Your Hotel Owner account has been verified successfully.',
                        'both'
                    );


                    // ========================================
                    // CLEAR SESSION
                    // ========================================

                    unset($_SESSION['verify_email']);


                    // ========================================
                    // LOGIN MESSAGE
                    // ========================================

                    $_SESSION['flash_login'] =
                        'Email verified successfully. Your Hotel Owner account is now active. Please log in.';


                    // ========================================
                    // REDIRECT LOGIN
                    // ========================================

                    redirect('../auth/login.php');
                }


            } catch (PDOException $e) {

                // Rollback if something goes wrong
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}


$page_title = 'Verify Email';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">

            <h2>Verify Your Email</h2>


            <p class="auth-sub">
                We have sent a 6-digit verification code to your email address.
            </p>


            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>


            <div style="
                text-align: center;
                margin-bottom: 25px;
            ">

                <strong>
                    <?= h($email) ?>
                </strong>

            </div>


            <form method="POST">

                <div class="form-group">

                    <label for="otp">
                        Enter Verification Code
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
                    Verify Email
                </button>

            </form>


            <div class="auth-foot">

                Didn't receive the code?

                <a href="resend-otp.php">
                    Resend OTP
                </a>

            </div>


            <div class="auth-foot">

                <a href="../register/register.php">
                    Back to Registration
                </a>

            </div>

        </div>

    </div>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>