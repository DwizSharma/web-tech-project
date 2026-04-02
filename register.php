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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = 'Username must be between 3 and 20 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert user into the database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);

            // Automatically log in the user after successful registration
            $_SESSION['user_id'] = $pdo->lastInsertId();

            // Redirect to homepage
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            // Handle unique constraint violations (username or email already exists)
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = 'Username or email already exists. Please choose another.';
            } else {
                $error = 'Registration failed due to a system error. Please try again later.';
            }
        }
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Join Nexus</h2>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">Create an account to join the community, share projects, and upvote content.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Choose a unique username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="student@college.edu" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
        </div>

        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Type your password again" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 10px;">
            <i class="fa-solid fa-user-plus"></i> Sign Up
        </button>
    </form>

    <div style="margin-top: 25px; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
        Already have an account? <a href="login.php" style="font-weight: 600;">Log In here</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
