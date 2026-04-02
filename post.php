<?php
require_once __DIR__ . '/includes/header.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Handle Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = 'You must be logged in to comment.';
    } else {
        $comment_content = trim($_POST['comment_content']);
        if (empty($comment_content)) {
            $error = 'Comment cannot be empty.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$post_id, $_SESSION['user_id'], $comment_content]);

                // Redirect to avoid form resubmission
                header("Location: post.php?id=" . $post_id . "#comments");
                exit;
            } catch (PDOException $e) {
                $error = 'Failed to post comment. Please try again.';
            }
        }
    }
}

// Fetch Post Details
try {
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            u.username,
            u.avatar_url,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as score,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo "<div class='main-content'><div class='alert alert-danger'>Post not found.</div></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    // Fetch Comments
    $comment_stmt = $pdo->prepare("
        SELECT
            c.*,
            u.username,
            u.avatar_url
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $comment_stmt->execute([$post_id]);
    $comments = $comment_stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Failed to load post data.";
}

// Helper function for time elapsed
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $w = floor($diff->d / 7);
        $diff->d -= $w * 7;

        $string = array(
            'y' => 'year', 'm' => 'month', 'w' => 'week',
            'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($k == 'w' && $w) {
                $v = $w . ' week' . ($w > 1 ? 's' : '');
            } else if (isset($diff->$k) && $diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
?>

<div style="max-width: 900px; margin: 20px auto; padding: 0 20px;">

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Main Post -->
    <div class="card post" style="margin-bottom: 20px;">
        <!-- Vote Column -->
        <div class="post-vote-col">
            <button class="vote-btn upvote" data-id="<?php echo $post['id']; ?>" data-type="up" aria-label="Upvote">
                <i class="fa-solid fa-arrow-up"></i>
            </button>
            <span class="vote-count"><?php echo number_format($post['score']); ?></span>
            <button class="vote-btn downvote" data-id="<?php echo $post['id']; ?>" data-type="down" aria-label="Downvote">
                <i class="fa-solid fa-arrow-down"></i>
            </button>
        </div>

        <!-- Content Column -->
        <div class="post-content-col">
            <div class="post-meta">
                <?php if (!empty($post['avatar_url'])): ?>
                    <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" alt="" class="avatar-sm" style="width: 24px; height: 24px;">
                <?php else: ?>
                    <i class="fa-solid fa-circle-user" style="font-size: 1.2rem;"></i>
                <?php endif; ?>
                <span class="post-author" style="font-size: 0.9rem;">n/<?php echo htmlspecialchars($post['username']); ?></span>
                <span class="dot">&bull;</span>
                <span class="post-time"><?php echo time_elapsed_string($post['created_at']); ?></span>
            </div>

            <h1 class="post-title" style="font-size: 1.5rem; margin-top: 10px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>

            <div class="post-body" style="font-size: 1rem; line-height: 1.6; margin-bottom: 20px;">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <?php if (!empty($post['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post Image" class="post-image" style="max-height: 500px; object-fit: contain; width: 100%; background-color: var(--input-bg);">
            <?php endif; ?>

            <div class="post-actions" style="margin-top: 15px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                <button class="action-btn">
                    <i class="fa-regular fa-message"></i>
                    <span><?php echo number_format($post['comment_count']); ?> Comments</span>
                </button>
                <button class="action-btn" onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied!');">
                    <i class="fa-solid fa-share"></i>
                    <span>Share</span>
                </button>
                <button class="action-btn">
                    <i class="fa-regular fa-bookmark"></i>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div id="comments" class="card" style="padding: 20px;">
        <h3 style="margin-bottom: 20px;">Comments (<?php echo number_format($post['comment_count']); ?>)</h3>

        <!-- Add Comment Form -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" action="post.php?id=<?php echo $post['id']; ?>" style="margin-bottom: 30px;">
                <div class="form-group">
                    <textarea name="comment_content" class="form-control" placeholder="What are your thoughts?" required style="min-height: 100px;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Comment</button>
                </div>
            </form>
        <?php else: ?>
            <div style="padding: 15px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; background-color: var(--input-bg);">
                <span style="color: var(--text-secondary);">Log in or sign up to leave a comment</span>
                <div>
                    <a href="login.php" class="btn btn-outline" style="margin-right: 10px;">Log In</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Comment List -->
        <div class="comments-list" style="display: flex; flex-direction: column; gap: 20px;">
            <?php if (empty($comments)): ?>
                <div style="text-align: center; padding: 40px 0; color: var(--text-secondary);">
                    <i class="fa-regular fa-comments" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
                    <p>No comments yet. Be the first to share your thoughts!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment" style="display: flex; gap: 10px;">
                        <div class="comment-avatar">
                            <?php if (!empty($comment['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($comment['avatar_url']); ?>" alt="" class="avatar-sm" style="width: 32px; height: 32px;">
                            <?php else: ?>
                                <div class="avatar-placeholder-sm" style="width: 32px; height: 32px; font-size: 1rem;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="comment-content" style="flex-grow: 1;">
                            <div class="comment-meta" style="font-size: 0.8rem; margin-bottom: 5px;">
                                <span style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($comment['username']); ?></span>
                                <span style="color: var(--text-secondary); margin-left: 5px;">&bull; <?php echo time_elapsed_string($comment['created_at']); ?></span>
                            </div>
                            <div class="comment-body" style="font-size: 0.95rem; color: var(--text-primary); line-height: 1.5;">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                            <div class="comment-actions" style="margin-top: 8px; display: flex; gap: 15px;">
                                <button class="action-btn" style="padding: 2px 5px; font-size: 0.8rem;">
                                    <i class="fa-solid fa-reply"></i> Reply
                                </button>
                                <button class="action-btn" style="padding: 2px 5px; font-size: 0.8rem;">
                                    <i class="fa-solid fa-flag"></i> Report
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
