<?php
require_once __DIR__ . '/includes/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    // Basic Validation
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } elseif (strlen($title) > 255) {
        $error = 'Title cannot exceed 255 characters.';
    } else {
        try {
            // Insert post into database
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content, $image_url]);

            $post_id = $pdo->lastInsertId();

            // Redirect to the newly created post or homepage
            header("Location: post.php?id=" . $post_id);
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to create post. Please try again later.';
        }
    }
}
?>

<div class="form-container" style="max-width: 700px;">
    <h2 class="form-title"><i class="fa-solid fa-pen-to-square"></i> Create a Post</h2>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">Share something interesting with the Nexus community.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="create_post.php">
        <div class="form-group">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" id="title" name="title" class="form-control" placeholder="Give your post a catchy title (max 255 characters)" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
            <textarea id="content" name="content" class="form-control" placeholder="What are your thoughts?" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="image_url" class="form-label">Image URL (Optional)</label>
            <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg" value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>">
            <small style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 5px; display: block;">Paste a direct link to an image to include it in your post.</small>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
            <a href="index.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Post
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
