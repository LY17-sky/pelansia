<?php
require_once __DIR__ . '/inc/functions.php';

$user = getUser();
if ($user && isset($user['id'])) {
    logActivity($user['id'], 'logout', 'User logout dari sistem');
    $conn = getDbConn();
    if ($conn) {
        $stmt = $conn->prepare("DELETE FROM tokens WHERE user_id = ?");
        $stmt->execute([$user['id']]);
    }
}

session_destroy();
redirect('login.php');
?>