<?php
require_once '../../includes/config.php';

if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$user_id = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$current_password = $input['current_password'] ?? null;
$new_password = $input['new_password'] ?? null;

if (!$current_password || !$new_password) {
    sendJSON(['success' => false, 'message' => 'Missing required fields']);
}

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($current_password, $user['password'])) {
        sendJSON(['success' => false, 'message' => 'Current password is incorrect']);
    }

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    sendJSON(['success' => true, 'message' => 'Password changed successfully']);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}
?>
