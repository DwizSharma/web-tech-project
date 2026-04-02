<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to update theme.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$theme = isset($_POST['theme']) ? $_POST['theme'] : '';

// Validate theme value
if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid theme value.']);
    exit;
}

try {
    // Update the user's theme preference in the database
    $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->execute([$theme, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Theme updated successfully.'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
