<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('/allhotels/index.php');
}

$error = null;
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Only Hotel Owner registration
    $type         = 'owner';
    $fullName     = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $phone        = trim($_POST['phone'] ?? '');
    $whatsapp     = trim($_POST['whatsapp'] ?? '');
    $businessAddr = trim($_POST['business_address'] ?? '');

    $old = $_POST;

    // Validation
    if (
        $fullName === '' ||
        $email === '' ||
        strlen($password) < 6 ||
        $phone === '' ||
        $businessAddr === ''
    ) {
        $error = 'Please fill all required fields. Password must be at least 6 characters.';
    } else {

        // Check existing email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {

            $error = 'An account with this email already exists.';

        } else {

            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Verification token
            $token = bin2hex(random_bytes(16));

            // Create owner account
            $stmt = $pdo->prepare(
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
                    is_verified
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
                $token,
                1
            ]);

            $userId = $pdo->lastInsertId();

            // Welcome notification
            notify(
                $pdo,
                $userId,
                'welcome',
                'Welcome to AllHotels.lk! Your Hotel Owner account has been activated.',
                'both'
            );

            $_SESSION['flash_login'] =
                'Hotel Owner account created successfully. Please log in.';

            redirect('../auth/login.php');
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