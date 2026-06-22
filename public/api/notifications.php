<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Notification;

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

Auth::verifyCsrf(post('csrf_token'));

$action = post('action');

try {
    if ($action === 'mark_read') {
        $notifId = intPost('notif_id');
        if (!$notifId) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Missing notif_id']);
            exit;
        }
        Notification::markRead($notifId, Auth::id());
        $unread = Notification::countUnread(Auth::id());
        echo json_encode(['ok' => true, 'unread' => $unread]);

    } elseif ($action === 'mark_all') {
        Notification::markAllRead(Auth::id());
        echo json_encode(['ok' => true, 'unread' => 0]);

    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}
