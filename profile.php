<?php
require_once __DIR__ . '/includes/header.php';

// Determine whose profile to view (from URL or logged-in user)
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

if (!$profile_id) {
    echo "<div class='main-content'><div class='alert alert-danger'>User not found or you are not logged in.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

try {
    // Fetch User Details
    $stmt = $pdo->prepare("SELECT id, username, bio, avatar_url, created_at FROM users WHERE id = ?");
    $stmt->execute([$profile_id]);
    $profileUser = $stmt->fetch();

    if (!$profileUser) {
        echo "<div class='main-content'><div class='alert alert-danger'>User not found.</div></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    // Fetch User's Posts
    $post_stmt = $pdo->prepare("
        SELECT
            p.*,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as score,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $post_stmt->execute([$profile_id]);
    $userPosts = $post_stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Failed to load profile.";
}

$is_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_id;
?>

<div style="max-width: 900px; margin: 20px auto; padding: 0 20px;">
    <!-- Profile Header -->
    <div class="card" style="padding: 30px; margin-bottom: 20px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
        <div class="profile-avatar">
            <?php if (!empty($profileUser['avatar_url'])): ?>
                <img src="<?php echo htmlspecialchars($profileUser['avatar_url']); ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color);">
            <?php else: ?>
                <div style="width: 100px; height: 100px; border-radius: 50%; background-color: var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-info" style="flex-grow: 1;">
            <h1 style="margin-bottom: 5px; color: var(--text-primary);">n/<?php echo htmlspecialchars($profileUser['username']); ?></h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 15px;">
                <i class="fa-solid fa-cake-candles"></i> Joined <?php echo date('F j, Y', strtotime($profileUser['created_at'])); ?>
            </p>
            <p style="margin-bottom: 15px; color: var(--text-primary); line-height: 1.5;">
                <?php echo !empty($profileUser['bio']) ? nl2br(htmlspecialchars($profileUser['bio'])) : '<em style="color: var(--text-secondary);">This user hasn\'t added a bio yet.</em>'; ?>
            </p>
            <?php if ($is_own_profile): ?>
                <a href="settings.php" class="btn btn-outline">
                    <i class="fa-solid fa-gear"></i> Edit Profile
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- User's Posts -->
    <h3 style="margin-bottom: 15px; color: var(--text-primary);">Posts by <?php echo htmlspecialchars($profileUser['username']); ?></h3>
    <div class="post-feed">
        <?php if (empty($userPosts)): ?>
            <div class="card" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                <i class="fa-regular fa-folder-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <h3>No posts yet</h3>
                <p>It looks like this user hasn't shared anything.</p>
            </div>
        <?php else: ?>
            <?php foreach ($userPosts as $post): ?>
                <div class="card post" style="margin-bottom: 15px;">
                    <div class="post-vote-col">
                        <button class="vote-btn upvote" data-id="<?php echo $post['id']; ?>" data-type="up" aria-label="Upvote">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <span class="vote-count"><?php echo number_format($post['score']); ?></span>
                        <button class="vote-btn downvote" data-id="<?php echo $post['id']; ?>" data-type="down" aria-label="Downvote">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                    </div>
                    <div class="post-content-col">
                        <div class="post-meta" style="margin-bottom: 8px;">
                            <span class="post-time"><i class="fa-regular fa-clock"></i> Posted on <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                        <a href="post.php?id=<?php echo $post['id']; ?>">
                            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                        </a>
                        <div class="post-body" style="margin-bottom: 10px;">
                            <?php
                            $content = htmlspecialchars($post['content']);
                            echo strlen($content) > 150 ? substr($content, 0, 150) . '... <a href="post.php?id='.$post['id'].'">Read more</a>' : nl2br($content);
                            ?>
                        </div>
                        <div class="post-actions" style="margin-top: 10px;">
                            <a href="post.php?id=<?php echo $post['id']; ?>#comments" class="action-btn">
                                <i class="fa-regular fa-message"></i>
                                <span><?php echo number_format($post['comment_count']); ?> Comments</span>
                            </a>
                            <button class="action-btn" onclick="navigator.clipboard.writeText('<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/post.php?id='.$post['id']; ?>'); alert('Link copied!');">
                                <i class="fa-solid fa-share"></i>
                                <span>Share</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
