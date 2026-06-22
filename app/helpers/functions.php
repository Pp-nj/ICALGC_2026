<?php
/**
 * Global helper functions — loaded on every request
 */

use App\Core\Database;

// ── Language & Translation ────────────────────────────────

function lang(): string
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['lang'] ?? DEFAULT_LANG;
}

function t(string $key, array $replace = []): string
{
    static $translations = [];
    $l = lang();
    if (empty($translations[$l])) {
        $file = LANG_PATH . '/' . $l . '.php';
        $translations[$l] = file_exists($file) ? require $file : [];
    }
    $text = $translations[$l][$key] ?? $key;
    foreach ($replace as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', $value, $text);
    }
    return $text;
}

function setLang(string $l): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (in_array($l, ['th', 'en'])) $_SESSION['lang'] = $l;
}

// ── Sanitization & Security ───────────────────────────────

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(string $s): string
{
    return trim(strip_tags($s));
}

function sanitizeEmail(string $email): string
{
    return strtolower(trim(filter_var($email, FILTER_SANITIZE_EMAIL)));
}

function validateEmail(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken(int $length = 64): string
{
    return bin2hex(random_bytes($length / 2));
}

// ── Paper Code Generation ─────────────────────────────────

function generatePaperCode(): string
{
    $db   = Database::getInstance();
    $stmt = $db->query("SELECT COUNT(*) FROM papers");
    $count = (int)$stmt->fetchColumn() + 1;
    return PAPER_CODE_PREFIX . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ── Date / Time Helpers ───────────────────────────────────

function formatDate(string $date, string $format = 'd M Y'): string
{
    if (!$date) return '-';
    return date($format, strtotime($date));
}

function daysUntil(string $dateStr): int
{
    $now    = new DateTime('today', new DateTimeZone('Asia/Bangkok'));
    $target = new DateTime($dateStr, new DateTimeZone('Asia/Bangkok'));
    $diff   = $now->diff($target);
    return $diff->invert ? -$diff->days : $diff->days;
}

function humanDate(string $dateStr, string $lang = ''): string
{
    if (!$lang) $lang = lang();
    $ts = strtotime($dateStr);
    if ($lang === 'th') {
        $monthsTh = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                       'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        $year     = (int)date('Y', $ts) + 543; // Buddhist Era
        return date('j', $ts) . ' ' . $monthsTh[(int)date('n', $ts)] . ' ' . $year;
    }
    return date('j F Y', $ts);
}

// ── Flash Messages ────────────────────────────────────────

function flashSet(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flashGet(): ?array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function flashHtml(): string
{
    $f = flashGet();
    if (!$f) return '';
    $type    = e($f['type']);
    $message = e($f['message']);
    $icon = match($f['type']) {
        'success' => 'check-circle',
        'danger'  => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        default   => 'info-circle',
    };
    return <<<HTML
<div class="alert alert-{$type} alert-dismissible fade show d-flex align-items-center" role="alert">
  <i class="fas fa-{$icon} me-2"></i>
  <span>{$message}</span>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
HTML;
}

// ── Redirect ──────────────────────────────────────────────

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ── Upload helpers ────────────────────────────────────────

function validateUpload(array $file): array
{
    $errors = [];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error code: ' . $file['error'];
        return $errors;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        $errors[] = 'File size exceeds 20 MB limit.';
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Only PDF and DOCX files are allowed.';
    }
    // MIME check
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
        $errors[] = 'Invalid file type detected.';
    }
    return $errors;
}

function moveUpload(array $file, string $subDir = ''): ?string
{
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir  = UPLOADS_PATH . ($subDir ? '/' . $subDir : '');
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $newName = uniqid('paper_', true) . '.' . $ext;
    $dest    = $dir . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $newName;
}

// ── Audit Log ─────────────────────────────────────────────

function auditLog(string $action, string $module, string $detail = '', ?int $userId = null): void
{
    try {
        $db   = Database::getInstance();
        $uid  = $userId ?? (session_status() !== PHP_SESSION_NONE && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
        $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $db->prepare(
            "INSERT INTO audit_logs (user_id, action, module, detail, ip_address) VALUES (:uid, :act, :mod, :det, :ip)"
        );
        $stmt->execute([':uid' => $uid, ':act' => $action, ':mod' => $module, ':det' => $detail, ':ip' => $ip]);
    } catch (\Throwable $e) {
        error_log('AuditLog error: ' . $e->getMessage());
    }
}

// ── Pagination ────────────────────────────────────────────

function paginate(int $total, int $perPage, int $page): array
{
    $totalPages  = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($page, $totalPages));
    $offset      = ($currentPage - 1) * $perPage;
    return [
        'total'      => $total,
        'per_page'   => $perPage,
        'page'       => $currentPage,
        'total_pages'=> $totalPages,
        'offset'     => $offset,
        'has_prev'   => $currentPage > 1,
        'has_next'   => $currentPage < $totalPages,
    ];
}

// ── File Size Format ──────────────────────────────────────

function formatFileSize(int $bytes): string
{
    if ($bytes < 1024)            return $bytes . ' B';
    if ($bytes < 1048576)         return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824)      return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}

// ── Status Badge HTML ─────────────────────────────────────

function statusBadge(string $code, string $lang = ''): string
{
    if (!$lang) $lang = lang();
    static $statuses = null;
    if ($statuses === null) {
        $db    = Database::getInstance();
        $rows  = $db->query("SELECT * FROM paper_statuses")->fetchAll();
        foreach ($rows as $r) $statuses[$r['code']] = $r;
    }
    $s     = $statuses[$code] ?? ['name_en' => $code, 'name_th' => $code, 'color_hex' => '#999'];
    $label = $lang === 'th' ? $s['name_th'] : $s['name_en'];
    $color = $s['color_hex'];
    return "<span class='badge status-badge' style='background:{$color};'>" . e($label) . '</span>';
}

// ── Input from POST/GET ───────────────────────────────────

function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = ''): mixed
{
    return $_GET[$key] ?? $default;
}

function intPost(string $key, int $default = 0): int
{
    return (int)($_POST[$key] ?? $default);
}

function intGet(string $key, int $default = 0): int
{
    return (int)($_GET[$key] ?? $default);
}
