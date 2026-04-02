<?php
require_once __DIR__ . '/includes/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $avatar_url = trim($_POST['avatar_url'] ?? '');

    // Basic Validation
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            // Update user in the database
            $stmt = $pdo->prepare("UPDATE users SET email = ?, bio = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$email, $bio, $avatar_url, $user_id]);

            $success = 'Your profile settings have been updated successfully.';

            // Re-fetch current user data to update the header if avatar changed
            // Normally this is handled by the header re-running its query on reload.
        } catch (PDOException $e) {
            // Handle unique constraint violations for email
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = 'That email is already in use by another account.';
            } else {
                $error = 'Failed to update settings due to a system error. Please try again later.';
            }
        }
    }
}

// Fetch current user details to pre-fill the form
try {
    $stmt = $pdo->prepare("SELECT username, email, bio, avatar_url FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // If user not found, they might have been deleted, so force logout
        header("Location: logout.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Failed to load user settings.";
}
?>

<div class="form-container" style="max-width: 600px;">
    <h2 class="form-title"><i class="fa-solid fa-gear"></i> Account Settings</h2>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">Update your profile information and preferences.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid #28a745; padding: 10px 15px; border-radius: var(--radius-sm); margin-bottom: 15px;">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="settings.php">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background-color: var(--border-color); cursor: not-allowed; opacity: 0.7;">
            <small style="color: var(--text-secondary); font-size: 0.8rem; display: block; margin-top: 5px;">Your username cannot be changed.</small>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="avatar_url" class="form-label">Profile Avatar URL</label>
            <input type="url" id="avatar_url" name="avatar_url" class="form-control" placeholder="https://example.com/avatar.jpg" value="<?php echo htmlspecialchars($user['avatar_url']); ?>">
            <small style="color: var(--text-secondary); font-size: 0.8rem; display: block; margin-top: 5px;">Provide a direct link to an image to use as your profile picture.</small>
        </div>

        <div class="form-group">
            <label for="bio" class="form-label">About Me (Bio)</label>
            <textarea id="bio" name="bio" class="form-control" placeholder="Tell the community a little bit about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
            <a href="profile.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
