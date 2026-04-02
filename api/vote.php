<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to vote.',
        'redirect' => 'login.php'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (!$post_id || !in_array($type, ['up', 'down'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

try {
    // For simplicity in this schema, we only have a 'likes' table.
    // Let's assume a 'like' is an upvote. If we want downvotes, we need a way to store them.
    // Given the schema: id, user_id, post_id, comment_id, created_at
    // We can only store upvotes (likes) currently, or we can use it to store both by modifying it,
    // but without altering schema, let's treat "up" as inserting a like, and "down" as removing it,
    // or if we really need up/down, we might be limited by the schema.
    // Let's implement upvote as 'like' and downvote as 'unlike/remove like' for now based on the provided schema.

    // Check if the user already liked the post
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing_like = $stmt->fetch();

    if ($type === 'up') {
        if (!$existing_like) {
            // Add like
            $insert = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $insert->execute([$user_id, $post_id]);
        }
    } else if ($type === 'down') {
        if ($existing_like) {
            // Remove like
            $delete = $pdo->prepare("DELETE FROM likes WHERE id = ?");
            $delete->execute([$existing_like['id']]);
        }
    }

    // Get the new count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $count_stmt->execute([$post_id]);
    $new_count = $count_stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'new_count' => $new_count
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
