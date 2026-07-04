<?php
/**
 * Secure File Download Handler
 * Serves paper files and certificate PDFs through PHP — never via direct URL.
 * Access control:
 *   cert_id  → owner or admin only
 *   file_id  → admin | paper owner | assigned reviewer
 *   paper_id → public if published, else admin | owner | reviewer
 */
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

$certId  = intGet('cert_id');
$paperId = intGet('paper_id');
$fileId  = intGet('file_id');

if (!$certId && !$paperId && !$fileId) {
    http_response_code(400);
    die('Invalid request.');
}

$db = Database::getInstance();

/* ── Certificate download ─────────────────────────────── */
if ($certId) {
    Auth::require();
    $user = Auth::user();

    $stmt = $db->prepare("SELECT * FROM certificates WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $certId]);
    $cert = $stmt->fetch();

    if (!$cert) {
        http_response_code(404);
        die('Certificate not found.');
    }

    if ((int)$cert['user_id'] !== (int)$user['id'] && !Auth::isAdmin()) {
        http_response_code(403);
        die('Access denied.');
    }

    if (empty($cert['pdf_path'])) {
        http_response_code(404);
        die('No file attached to this certificate.');
    }

    $fullPath = ROOT_PATH . '/' . $cert['pdf_path'];
    if (!file_exists($fullPath)) {
        http_response_code(404);
        die('Certificate file not found on server.');
    }

    auditLog('download', 'certificate', 'cert_id=' . $certId . ' type=' . $cert['cert_type']);

    $safeName = strtoupper($cert['cert_type']) . '_Certificate_'
              . preg_replace('/[^A-Za-z0-9_\-]/', '_', $cert['recipient_name'])
              . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . rawurlencode($safeName) . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    readfile($fullPath);
    exit;
}

/* ── Paper / file download ────────────────────────────── */
try {
    if ($fileId) {
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

        $canAccess = Auth::isAdmin()
                  || (int)$file['submitter_id'] === (int)$user['id']
                  || Auth::isReviewer();

        if (!$canAccess) { http_response_code(403); die('Access denied.'); }

    } else {
        $stmt = $db->prepare("SELECT status_code, submitter_id FROM papers WHERE id = :pid LIMIT 1");
        $stmt->execute([':pid' => $paperId]);
        $paper = $stmt->fetch();

        if (!$paper) { http_response_code(404); die('Paper not found.'); }

        if ($paper['status_code'] !== 'published') {
            Auth::require();
            $user = Auth::user();
            $canAccess = Auth::isAdmin()
                      || (int)$paper['submitter_id'] === (int)$user['id']
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

    $fullPath = ROOT_PATH . '/uploads/papers/' . $file['stored_name'];

    if (!file_exists($fullPath)) {
        http_response_code(404);
        die('File not found on server.');
    }

    if ($paperId) {
        $db->prepare("UPDATE publications SET download_count = download_count + 1 WHERE paper_id = :pid")
           ->execute([':pid' => $paperId]);
    }

    auditLog('download', 'paper', 'Downloaded: ' . $file['original_name']);

    $mime = $file['file_type'] === 'pdf'
          ? 'application/pdf'
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
