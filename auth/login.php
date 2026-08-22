<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('../index.php');
}

$error = null;

$flash_login = $_SESSION['flash_login'] ?? null;
unset($_SESSION['flash_login']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        if (
            !$user ||
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {

            $error = 'Invalid email or password.';

        } else {

            $_SESSION['user_id'] = $user['id'];

            $_SESSION['user'] = [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];


            if ($user['role'] === 'owner') {

                redirect('../owner/dashboard.php');

            } elseif ($user['role'] === 'admin') {

                redirect('../admin/dashboard.php');

            } else {

                redirect('../index.php');

            }

        }

    }

}


$page_title = 'Login';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">

            <h2>
                Welcome Back
            </h2>

            <p class="auth-sub">
                Log in to manage bookings, reviews,
                or your hotel listings.
            </p>


            <?php if ($flash_login): ?>

                <div class="alert alert-success">
                    <?= h($flash_login) ?>
                </div>

            <?php endif; ?>


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
                        required
                        autofocus
                        autocomplete="email"
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >

                </div>


                <div
                    style="
                        text-align:right;
                        margin-bottom:18px;
                    "
                >

                    <a
                        href="forgot-password.php"
                        style="
                            color:var(--terracotta);
                            font-weight:600;
                        "
                    >
                        Forgot Password?
                    </a>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Log In
                </button>

            </form>


            <div class="auth-foot">

                New to AllHotels.lk?

                <a
                    href="../auth/register.php?type=customer"
                >
                    Create an account
                </a>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>