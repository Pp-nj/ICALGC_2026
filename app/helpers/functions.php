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

// ── Asset Cache-Busting ────────────────────────────────────

function asset_ver(string $relativePath): string
{
    $path = PUBLIC_PATH . '/' . ltrim($relativePath, '/');
    $ver  = @filemtime($path) ?: time();
    return rtrim(APP_URL, '/') . '/' . ltrim($relativePath, '/') . '?v=' . $ver;
}

// ── Sanitization & Security ───────────────────────────────

/**
 * Coerce any value into a plain string.
 *
 * The helpers below are fed two kinds of value that are not strings:
 *  - NULL, from nullable database columns (phone, institution, doi, …)
 *  - arrays, from request input — "?token[]=x" makes $_GET['token'] an array
 *
 * Declaring those helpers as `string` turned both cases into an uncaught
 * TypeError (a 500 page), and several of the call sites sit outside any
 * try/catch. Normalising here keeps one rule in one place: scalars are cast,
 * anything else becomes an empty string.
 */
function strv(mixed $value): string
{
    return is_scalar($value) ? (string)$value : '';
}

function e(mixed $s): string
{
    return htmlspecialchars(strv($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(mixed $s): string
{
    return trim(strip_tags(strv($s)));
}

function sanitizeEmail(mixed $email): string
{
    return strtolower(trim(filter_var(strv($email), FILTER_SANITIZE_EMAIL)));
}

function validateEmail(mixed $email): bool
{
    return (bool)filter_var(strv($email), FILTER_VALIDATE_EMAIL);
}

// ── Person names ──────────────────────────────────────────

/**
 * Compose a person's display name from a users row: title, first, middle, last,
 * with every missing or empty part dropped.
 *
 * Kept in one place so "how do we write someone's name" is a single decision
 * instead of thirty copies of string concatenation. Note that the row must
 * actually contain these columns — a query selecting only first_name/last_name
 * produces a shorter name with no warning, so widen the SELECT too.
 */
function fullName(array $row): string
{
    $parts = [
        strv($row['title']       ?? ''),
        strv($row['first_name']  ?? ''),
        strv($row['middle_name'] ?? ''),
        strv($row['last_name']   ?? ''),
    ];
    return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
}

/**
 * The SQL counterpart of fullName(), for queries that compose the name
 * themselves. Pass the table alias used in the query, e.g. sqlFullName('u').
 *
 * CONCAT_WS works on both MySQL and PostgreSQL 9.1+ and skips NULL, so no
 * per-driver branching is needed. NULLIF is required because CONCAT_WS skips
 * NULL but NOT empty strings, and registration stores an unset title as ''
 * rather than NULL — without it those rows come back with a leading space.
 */
function sqlFullName(string $alias = ''): string
{
    $p = $alias !== '' ? $alias . '.' : '';
    return "CONCAT_WS(' ', NULLIF({$p}title,''), NULLIF({$p}first_name,''),"
         . " NULLIF({$p}middle_name,''), NULLIF({$p}last_name,''))";
}

/**
 * Search condition matching a person by name or email.
 *
 * Matches the full name both with and without the middle name: an admin who
 * types "John Smith" should still find "John Paul Smith", which a single match
 * against the composed full name would miss.
 *
 * The three comparisons each get their own placeholder ($base . '1'..'3'),
 * because PDO forbids repeating one named placeholder while
 * ATTR_EMULATE_PREPARES is false. Bind them with nameSearchParams().
 *
 * @param string $alias    Table alias used in the query ('' for none).
 * @param string $base     Placeholder prefix, e.g. ':q' -> :q1, :q2, :q3.
 * @param bool   $isMysql  MySQL has no ILIKE; PostgreSQL needs it for
 *                         case-insensitive matching.
 */
function sqlNameSearch(string $alias, string $base, bool $isMysql): string
{
    $p    = $alias !== '' ? $alias . '.' : '';
    $like = $isMysql ? 'LIKE' : 'ILIKE';

    return '(' . sqlFullName($alias) . " {$like} {$base}1"
         . " OR CONCAT_WS(' ', NULLIF({$p}first_name,''), NULLIF({$p}last_name,'')) {$like} {$base}2"
         . " OR {$p}email {$like} {$base}3)";
}

/**
 * The bindings that go with sqlNameSearch() — the same "%term%" under each of
 * its three placeholders.
 */
function nameSearchParams(string $base, string $term): array
{
    $value = '%' . $term . '%';
    return [$base . '1' => $value, $base . '2' => $value, $base . '3' => $value];
}

function generateToken(int $length = 64): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Verify the CSRF token of the current POST request, or stop the request.
 *
 * Auth::verifyCsrf() only *returns* a bool, which is easy to call and then
 * ignore — leaving the handler unprotected while still looking guarded.
 * This wrapper never returns when the token is missing or wrong, so a POST
 * handler cannot accidentally run without protection.
 *
 * @param string $redirectTo Where to send the user back to. Defaults to the
 *                           URL the request was made to (same page, via GET).
 */
function requireCsrf(string $redirectTo = ''): void
{
    // A non-string (e.g. csrf_token[]=x) would make verifyCsrf() raise a
    // TypeError, so normalise it to a value that simply fails the comparison.
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token)) $token = '';

    if (\App\Core\Auth::verifyCsrf($token)) return;

    flashSet('danger', lang() === 'th'
        ? 'คำขอไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่อีกครั้ง'
        : 'Invalid or expired request. Please try again.');

    if ($redirectTo === '') {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Only follow a plain same-site path — never a '//host' style value.
        $redirectTo = (isset($uri[0]) && $uri[0] === '/' && !str_starts_with($uri, '//'))
            ? $uri
            : APP_URL . '/';
    }
    redirect($redirectTo);
}

// ── Paper Code Generation ─────────────────────────────────

function generatePaperCode(): string
{
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT MAX(CAST(SUBSTRING(paper_code, :prefixLen) AS UNSIGNED))
        FROM papers
        WHERE paper_code LIKE :prefix
    ");
    $stmt->execute([
        ':prefixLen' => strlen(PAPER_CODE_PREFIX) + 1,
        ':prefix'    => PAPER_CODE_PREFIX . '%',
    ]);
    $next = (int)$stmt->fetchColumn() + 1;
    return PAPER_CODE_PREFIX . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// ── Date / Time Helpers ───────────────────────────────────

function formatDate(mixed $date, string $format = 'd M Y'): string
{
    $date = strv($date);
    if ($date === '') return '-';
    // An unparseable date gives strtotime() false, which date() would read as
    // timestamp 0 and render as 1 Jan 1970. Show the placeholder instead.
    $ts = strtotime($date);
    return $ts === false ? '-' : date($format, $ts);
}

function daysUntil(mixed $dateStr): int
{
    $dateStr = strv($dateStr);
    if ($dateStr === '') return 0;

    try {
        $now    = new DateTime('today', new DateTimeZone('Asia/Bangkok'));
        $target = new DateTime($dateStr, new DateTimeZone('Asia/Bangkok'));
    } catch (\Exception $e) {
        // DateTime throws on an unparseable string; treat it as "no date".
        return 0;
    }
    $diff = $now->diff($target);
    return $diff->invert ? -$diff->days : $diff->days;
}

function humanDate(mixed $dateStr, string $lang = ''): string
{
    if (!$lang) $lang = lang();
    $ts = strtotime(strv($dateStr));
    if ($ts === false) return '-';
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
    // Callers are expected to run validateUpload() first, but this function must
    // not depend on that: the extension comes straight from the client-supplied
    // filename, so re-check it here rather than writing whatever was asked for.
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;

    $ext = strtolower(pathinfo(strv($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) return null;

    $dir = UPLOADS_PATH . ($subDir ? '/' . $subDir : '');
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        error_log('moveUpload: could not create upload directory ' . $dir);
        return null;
    }

    $newName = uniqid('paper_', true) . '.' . $ext;
    $dest    = $dir . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $newName;
}

// ── Site Settings ─────────────────────────────────────────

/**
 * Read one admin-editable setting from site_settings.
 *
 * Returns $default when the key is unset or the table does not exist yet
 * (a database created before site_settings was added), so pages keep working
 * without a manual migration. The table is created on the first settingSet().
 */
function settingGet(string $key, ?string $default = null): ?string
{
    try {
        $stmt = Database::getInstance()->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :k");
        $stmt->execute([':k' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    } catch (\Throwable $e) {
        return $default;
    }
}

function settingSet(string $key, string $value): void
{
    $db = Database::getInstance();
    $db->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            setting_key   VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $db->prepare("
        INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ")->execute([':k' => $key, ':v' => $value]);
}

/**
 * Whether authors may submit new papers. Open unless an admin has closed it.
 * Revisions of existing papers are not affected.
 */
function isSubmissionOpen(): bool
{
    return settingGet('submission_open', '1') === '1';
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
    // Canonical 6-status definitions (override DB to ensure consistency)
    $map = [
        'submitted'         => ['th' => 'ส่งแล้ว',             'en' => 'Submitted',         'color' => '#0d6efd'],
        'under_review'      => ['th' => 'อยู่ระหว่างพิจารณา',   'en' => 'Under Review',      'color' => '#6f42c1'],
        'revision_required' => ['th' => 'ต้องการแก้ไข',         'en' => 'Revision Required', 'color' => '#fd7e14'],
        'accepted'          => ['th' => 'ได้รับการยอมรับ',      'en' => 'Accepted',          'color' => '#198754'],
        'rejected'          => ['th' => 'ถูกปฏิเสธ',           'en' => 'Rejected',          'color' => '#dc3545'],
        'published'         => ['th' => 'เผยแพร่แล้ว',         'en' => 'Published',         'color' => '#0f5132'],
    ];
    $s     = $map[$code] ?? ['th' => $code, 'en' => $code, 'color' => '#6c757d'];
    $label = $lang === 'th' ? $s['th'] : $s['en'];
    $color = $s['color'];
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
