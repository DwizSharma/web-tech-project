<?php
require_once __DIR__ . '/includes/header.php';

// Handle search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];

// Base query for fetching posts with user info, like count, and comment count
$query = "
    SELECT
        p.*,
        u.username,
        u.avatar_url,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as score,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
";

// Apply search filter if present
if ($search !== '') {
    $query .= " WHERE p.title LIKE ? OR p.content LIKE ? OR u.username LIKE ?";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$query .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    $error = "Failed to load posts.";
}

// Function to format time ago
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
?>

<!-- Left Column: Post Feed -->
<div class="post-feed">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($search !== ''): ?>
        <h3 style="margin-bottom: 15px;">Search results for "<?php echo htmlspecialchars($search); ?>"</h3>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="card" style="padding: 40px; text-align: center; color: var(--text-secondary);">
            <i class="fa-solid fa-ghost" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>No posts found</h3>
            <p>Be the first to share something interesting!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create_post.php" class="btn btn-primary" style="margin-top: 15px;">Create Post</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="card post">
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
                            <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" alt="" class="avatar-sm" style="width: 20px; height: 20px;">
                        <?php else: ?>
                            <i class="fa-solid fa-circle-user"></i>
                        <?php endif; ?>
                        <span class="post-author">n/<?php echo htmlspecialchars($post['username']); ?></span>
                        <span class="dot">&bull;</span>
                        <span class="post-time"><?php echo time_elapsed_string($post['created_at']); ?></span>
                    </div>

                    <a href="post.php?id=<?php echo $post['id']; ?>">
                        <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                    </a>

                    <div class="post-body">
                        <?php
                        // Simple truncation for feed
                        $content = htmlspecialchars($post['content']);
                        if (strlen($content) > 300) {
                            $content = substr($content, 0, 300) . '... <a href="post.php?id='.$post['id'].'">Read more</a>';
                        }
                        echo nl2br($content);
                        ?>
                    </div>

                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post Image" class="post-image">
                    <?php endif; ?>

                    <div class="post-actions">
                        <a href="post.php?id=<?php echo $post['id']; ?>#comments" class="action-btn">
                            <i class="fa-regular fa-message"></i>
                            <span><?php echo number_format($post['comment_count']); ?> Comments</span>
                        </a>
                        <button class="action-btn" onclick="navigator.clipboard.writeText('<?php echo 'http://'.$_SERVER['HTTP_HOST'].'/post.php?id='.$post['id']; ?>'); alert('Link copied!');">
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
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Right Column: Sidebar -->
<aside class="sidebar">
    <div class="card" style="padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-bottom: 10px; font-size: 1.1rem;"><i class="fa-solid fa-rocket" style="color: var(--accent-color);"></i> About Nexus</h3>
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 15px;">
            Your college project platform to connect, share ideas, and build a community. Join discussions, share media, and upvote the best content!
        </p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">Join Now</a>
            <a href="login.php" class="btn btn-outline" style="width: 100%;">Log In</a>
        <?php else: ?>
            <a href="create_post.php" class="btn btn-primary" style="width: 100%;">Create Post</a>
        <?php endif; ?>
    </div>

    <div class="card" style="padding: 20px;">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem;">Trending Topics</h3>
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 10px; font-size: 0.9rem;">
            <li><a href="index.php?search=college"><i class="fa-solid fa-arrow-trend-up"></i> College Life</a></li>
            <li><a href="index.php?search=tech"><i class="fa-solid fa-arrow-trend-up"></i> Technology</a></li>
            <li><a href="index.php?search=project"><i class="fa-solid fa-arrow-trend-up"></i> Final Year Projects</a></li>
            <li><a href="index.php?search=memes"><i class="fa-solid fa-arrow-trend-up"></i> Memes</a></li>
        </ul>
    </div>
</aside>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
