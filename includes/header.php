<?php
require_once __DIR__ . '/functions.php';

$user = current_user();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= isset($page_title)
            ? h($page_title) . ' | AllHotels.lk'
            : 'AllHotels.lk — Find Your Perfect Venue'
        ?>
    </title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="/allhotels/css/style.css"
    >

</head>

<body>

<!-- ==========================================================
     HEADER
========================================================== -->

<header class="site-header">

    <div class="header-inner">

        <!-- LOGO -->
        <a href="/allhotels/index.php" class="logo">
            <img src="/allhotels/api/images/logo-white.png" alt="AllHotels.lk Logo">
        </a>

        <!-- NAVIGATION -->
        <nav class="main-nav" id="mainNav">

            <a href="/allhotels/index.php">
                Home
            </a>

            <a href="/allhotels/about.php">
                About Us
            </a>

            <a href="/allhotels/contact.php">
                Contact Us
            </a>

            <?php if ($user): ?>

                <a
                    href="/allhotels/<?= $user['role'] === 'owner'
                        ? 'owner/dashboard.php'
                        : ($user['role'] === 'admin'
                            ? 'admin/dashboard.php'
                            : 'index.php')
                    ?>"
                >
                    My Account
                </a>

                <a
                    href="/allhotels/auth/logout.php"
                    class="nav-cta"
                >
                    Logout
                </a>

            <?php else: ?>

                <a href="/allhotels/auth/login.php">
                    My Account
                </a>

                <a
                    href="/allhotels/auth/register.php?type=owner"
                    class="nav-cta"
                >
                    Add Your Hotel
                </a>

            <?php endif; ?>

        </nav>

        <!-- MOBILE MENU -->
        <button
            class="nav-toggle"
            id="navToggle"
            type="button"
            aria-label="Toggle navigation"
        >
            ☰
        </button>

    </div>

</header>

 
    <!-- SLIDER DOTS -->

    <section class="hotel-photo-slider">

    <div class="slider-images">

        <img
            src="/allhotels/api/images/hotel-1.jpg"
            class="slider-image active"
            alt="Sri Lankan Hotel"
        >

        <img
            src="/allhotels/api/images/hotel-2.jpg"
            class="slider-image"
            alt="Luxury Hotel"
        >

        <img
            src="/allhotels/api/images/hotel-3.jpg"
            class="slider-image"
            alt="Beach Hotel"
        >

        <img
            src="/allhotels/api/images/hotel-4.jpg"
            class="slider-image"
            alt="Hotel Event"
        >

        <img
            src="/allhotels/api/images/hotel-5.jpg"
            class="slider-image"
            alt="Wedding Venue"
        >

    </div>

    <div class="slider-overlay"></div>

    <div class="slider-content">

        <span class="hero-tag">
            Sri Lanka's Hotel &amp; Venue Directory
        </span>

        <h1>
            Find the <em>perfect venue</em><br>
            for every occasion.
        </h1>

    </div>

    <div class="slider-dots">

        <button class="slider-dot active" data-slide="0"></button>
        <button class="slider-dot" data-slide="1"></button>
        <button class="slider-dot" data-slide="2"></button>
        <button class="slider-dot" data-slide="3"></button>
        <button class="slider-dot" data-slide="4"></button>

    </div>

</section>
    
</section>


<main>

 