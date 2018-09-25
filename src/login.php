<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Auth.class.php";
require_once __DIR__ . "/Icons.class.php";

// login handling
if (isset($_POST["login"]) && !empty($_POST["pass"])) {
    if (Auth::passwordValid($_POST["pass"])) {
        Auth::startSession();

        // for good measure
        Auth::vacuumExpiredSessions();
    } else {

        // a sleep a day keeps the bad guys at bay
        sleep(2);
    }
    header("Location: index.php");
    exit;
}

// if no session or session invalid, display login mask and peace out
if (!Auth::sessionValid()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Login - ReAD</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php if (Config::THEME_MOBILE == "dark") { ?>
            <meta name="theme-color" content="#000">
        <?php } ?>
        <link rel="shortcut icon" href="imgs/favicon.png">
        <link rel="apple-touch-icon" href="imgs/favicon.png">
        <link rel="stylesheet" href="deps/octicons-4.3.0/build/font/octicons.css">
        <link rel="stylesheet" href="style.css?<?= $styleQueryString ?>">
    </head>
    <body>
        <section class="login">
            <h1><span class="icon"><?= Icons::LOGIN ?></span> ReAD</h1>
            <form action="index.php" method="post">
                <input type="password" name="pass" placeholder="Password" autofocus>
                <input type="submit" class="submit" name="login">
            </form>
        </section>
    </body>
    <?php

    exit;
}

// logout
if (isset($_GET["logout"])) {
    Auth::killSession();

    header("Location: index.php");
    exit;
}

// else everything's fine
