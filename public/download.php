<?php
/**
 * Secure File Download Handler
 * Downloads are served through PHP — never via direct URL
 * Access control: Published papers are public; other papers require auth + ownership/role
 */
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

$paperId  = intGet('paper_id');
$fileId   = intGet('file_id');
$type     = sanitize(get('type', 'latest')); // 'latest' | 'id'

if (!$paperId && !$fileId) {
    http_response_code(400);
    die('Invalid request.');
}

$db = Database::getInstance();

try {
    if ($fileId) {
        // Specific file by ID (dashboard users)
        Auth::require();
        $user = Auth::user();

        $stmt = $db->prepare("
            SELECT pf.*, p.submitter_id, p.paper_code, p.status_code
            FROM paper_files pf
            JOIN papers p ON p.id = pf.paper_id
            WHERE pf.id = :fid
            LIMIT 1
        ");
        $stmt->execute([':fid' => $fileId]);
        $file = $stmt->fetch();

        if (!$file) { http_response_code(404); die('File not found.'); }

        // Access: admin, assigned reviewer, or paper owner
        $canAccess = Auth::isAdmin()
            || $file['submitter_id'] === $user['id']
            || Auth::isReviewer();

        if (!$canAccess) { http_response_code(403); die('Access denied.'); }

    } else {
        // By paper_id + type
        $stmt = $db->prepare("SELECT status_code, submitter_id FROM papers WHERE id = :pid LIMIT 1");
        $stmt->execute([':pid' => $paperId]);
        $paper = $stmt->fetch();

        if (!$paper) { http_response_code(404); die('Paper not found.'); }

        // Public access only for published papers
        if ($paper['status_code'] !== 'published') {
            Auth::require();
            $user = Auth::user();
            $canAccess = Auth::isAdmin()
                || $paper['submitter_id'] === $user['id']
                || Auth::isReviewer();
            if (!$canAccess) { http_response_code(403); die('Access denied.'); }
        }

        $fileStmt = $db->prepare("
            SELECT * FROM paper_files
            WHERE paper_id = :pid
            ORDER BY version_number DESC, uploaded_at DESC
            LIMIT 1
        ");
        $fileStmt->execute([':pid' => $paperId]);
        $file = $fileStmt->fetch();

        if (!$file) { http_response_code(404); die('No file attached to this paper.'); }
    }

    // Build absolute path (file is stored outside public/)
    $fullPath = ROOT_PATH . '/uploads/papers/' . $file['stored_name'];

    if (!file_exists($fullPath)) {
        http_response_code(404);
        die('File not found on server.');
    }

    // Increment download count for published papers
    if ($paperId) {
        $db->prepare("UPDATE publications SET download_count = download_count + 1 WHERE paper_id = :pid")
           ->execute([':pid' => $paperId]);
    }

    auditLog('download', 'paper', 'Downloaded: ' . $file['original_name']);

    // Serve file
    $mime = $file['file_type'] === 'pdf' ? 'application/pdf'
          : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . rawurlencode($file['original_name']) . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('X-Content-Type-Options: nosniff');

    readfile($fullPath);
    exit;

} catch (\Throwable $e) {
    error_log('Download error: ' . $e->getMessage());
    http_response_code(500);
    die('Server error.');
}
