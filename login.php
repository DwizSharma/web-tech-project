<?php
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];

                // Redirect to homepage or intended page
                header("Location: index.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Login failed due to a system error. Please try again later.';
        }
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Welcome Back</h2>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">Log in to access your feed, upvote posts, and join discussions.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 10px;">
            <i class="fa-solid fa-right-to-bracket"></i> Log In
        </button>
    </form>

    <div style="margin-top: 25px; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
        New to Nexus? <a href="register.php" style="font-weight: 600;">Sign Up here</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
