<?php
require_once 'config/constants.php';
require_once 'Models/UserHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$userHelper = new UserHelper();
$currentUser = $_SESSION['user'] ?? null;
$userName = $userHelper->getDisplayName($currentUser);
$isLoggedIn = !empty($currentUser);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title'] ?? 'KIV/WEB'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="CSSs/style.css">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid d-flex justify-content-between align-items-center">

            <div class="flex-grow-1"></div>

            <div class="navbar-brand">
                <a href="index.php" class="text-white text-decoration-none">
                    <h1 class="mb-0"><?php echo defined('SITE_NAME') ? SITE_NAME : 'WEB CONFERENCE'; ?></h1>
                </a>
            </div>

            <div class="d-flex align-items-center justify-content-end">
                <span class="text-white me-3 d-none d-md-inline" id="user-status">
                    <?php if ($isLoggedIn): ?>
                        <?php echo htmlspecialchars($userName); ?>
                    <?php else: ?>
                        <a href="index.php?path=login" class="text-white text-decoration-none">Nepřihlášený uživatel</a>
                    <?php endif; ?>
                </span>

                <div class="dropdown">
                    <button class="btn btn-link text-white p-2 border-0 d-flex align-items-center justify-content-center"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Navigační menu">
                        <span class="hamburger-icon"></span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header d-md-none" id="user-status-mobile">
                            <?php if ($isLoggedIn): ?>
                                <?php echo htmlspecialchars($userName); ?>
                            <?php else: ?>
                                <a href="index.php?path=login" class="text-decoration-none">Nepřihlášený uživatel</a>
                            <?php endif; ?>
                        </li>
                        <li class="d-md-none"><hr class="dropdown-divider"></li>

                        <li><a class="dropdown-item <?php echo ($data['activePage'] ?? '') === 'home' ? 'active' : ''; ?>" href="index.php">Úvodní stránka</a></li>

                        <li><hr class="dropdown-divider"></li>

                        <?php if ($isLoggedIn): ?>


                            <?php if ($userHelper->hasRole($currentUser, 'autor')): ?>
                                <li><a class="dropdown-item <?php echo ($data['activePage'] ?? '') === 'article-maker' ? 'active' : ''; ?>" href="index.php?path=article-maker">Vytvořit článek</a></li>
                            <?php endif; ?>

                            <?php if ($userHelper->hasRole($currentUser, 'recenzent')): ?>
                                <li><a class="dropdown-item <?php echo ($data['activePage'] ?? '') === 'recenzents' ? 'active' : ''; ?>" href="index.php?path=recenzents">Správa recenzí</a></li>
                            <?php endif; ?>

                            <?php if ($userHelper->isAdmin($currentUser)): ?>
                                <li><a class="dropdown-item <?php echo ($data['activePage'] ?? '') === 'manage-users' ? 'active' : ''; ?>" href="index.php?path=manage-users">Správa uživatelů</a></li>
                                <li><a class="dropdown-item <?php echo ($data['activePage'] ?? '') === 'manage-recenzents' ? 'active' : ''; ?>" href="index.php?path=manage-recenzents">Přiřazení recenzentů</a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?path=logout">Odhlásit se</a></li>

                        <?php else: ?>
                            <li><a class="dropdown-item" href="index.php?path=login">Přihlášení / Registrace</a></li>
                        <?php endif; ?>



                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>