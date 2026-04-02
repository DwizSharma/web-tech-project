<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Fetch user info and theme preference if logged in
$theme = 'light';
$currentUser = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, avatar_url, theme_preference FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
    if ($currentUser && !empty($currentUser['theme_preference'])) {
        $theme = $currentUser['theme_preference'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - Connect & Share</title>
    <!-- CSS Reset and Custom Styles -->
    <link rel="stylesheet" href="assets/style.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="theme-<?php echo htmlspecialchars($theme); ?>">

    <header class="navbar">
        <div class="nav-container">
            <!-- Left: Logo -->
            <div class="nav-left">
                <a href="index.php" class="brand-logo">
                    <i class="fa-solid fa-network-wired"></i>
                    <span>Nexus</span>
                </a>
            </div>

            <!-- Center: Search Bar (Innovative feature: live search placeholder) -->
            <div class="nav-center">
                <form action="index.php" method="GET" class="search-form">
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" name="search" placeholder="Search discussions, tags, or users..." class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </form>
            </div>

            <!-- Right: Actions & Profile -->
            <div class="nav-right">
                <!-- Dark Mode Toggle -->
                <button id="themeToggleBtn" class="icon-btn" aria-label="Toggle Theme">
                    <i class="fa-solid <?php echo $theme === 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                </button>

                <?php if ($currentUser): ?>
                    <a href="create_post.php" class="btn btn-primary create-btn">
                        <i class="fa-solid fa-plus"></i> Create
                    </a>

                    <div class="profile-dropdown-wrapper">
                        <button class="profile-dropdown-btn">
                            <?php if (!empty($currentUser['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar_url']); ?>" alt="Avatar" class="avatar-sm">
                            <?php else: ?>
                                <div class="avatar-placeholder-sm">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <span class="username"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                        </button>

                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fa-solid fa-circle-user"></i> My Profile
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fa-solid fa-gear"></i> Settings
                            </a>
                            <hr class="dropdown-divider">
                            <a href="logout.php" class="dropdown-item text-danger">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline">Log In</a>
                        <a href="register.php" class="btn btn-primary">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-content">
