<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'About Us';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/slider.php';
?>
<div class="container section static-page">
    <h1>About AllHotels.lk</h1>
    <p class="lead">AllHotels.lk is Sri Lanka's comprehensive hotel discovery and management platform, built to connect visitors with hotels, function halls, and event venues across the island.</p>

    <div class="panel" style="margin-top:30px;">
        <h3>What We Do</h3>
        <p>Whether you're planning a beachfront wedding, a corporate meeting, a family picnic, or a birthday celebration, AllHotels.lk lets you search, filter, and compare venues by location, guest capacity, budget, and function type — all without needing an account.</p>
    </div>

    <div class="panel">
        <h3>For Hotel Owners</h3>
        <p>Register your property, manage your listing, and — on our Premium plan — unlock a full photo gallery and an interactive online booking engine that sends you instant Email and WhatsApp alerts for every new review or reservation.</p>
        <a href="/allhotels/auth/register.php?type=owner" class="btn btn-terracotta">List Your Hotel</a>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
