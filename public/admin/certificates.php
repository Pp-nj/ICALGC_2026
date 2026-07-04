<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notification;
use App\Core\Mail;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

// ── Upload directory (outside public/) ──────────────────
define('CERT_UPLOAD_PATH', ROOT_PATH . '/uploads/certificates');
if (!is_dir(CERT_UPLOAD_PATH)) {
    mkdir(CERT_UPLOAD_PATH, 0755, true);
}

// ── Certificate type meta ────────────────────────────────
$certTypeMeta = [
    'acceptance'   => ['th' => 'ใบตอบรับ',              'en' => 'Acceptance',   'color' => '#0057b7', 'icon' => 'fa-file-contract',      'needs_paper' => true],
    'presentation' => ['th' => 'ใบนำเสนอ',              'en' => 'Presentation', 'color' => '#198754', 'icon' => 'fa-chalkboard-teacher', 'needs_paper' => true],
    'attendance'   => ['th' => 'ใบเข้าร่วม',            'en' => 'Attendance',   'color' => '#6f42c1', 'icon' => 'fa-users',              'needs_paper' => false],
    'reviewer'     => ['th' => 'ใบรับรองผู้ทรงคุณวุฒิ', 'en' => 'Reviewer',    'color' => '#a07c10', 'icon' => 'fa-user-tie',           'needs_paper' => false],
];

/* ── POST: upload or delete ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));
    $action = post('action', 'upload');

    // ── Delete ──────────────────────────────────────────
    if ($action === 'delete') {
        $delId = intPost('cert_id');
        try {
            $db = Database::getInstance();
            $row = $db->prepare("SELECT pdf_path, user_id, cert_type FROM certificates WHERE id = :id LIMIT 1");
            $row->execute([':id' => $delId]);
            $cert = $row->fetch();

            if ($cert) {
                // Remove physical file
                if ($cert['pdf_path']) {
                    $fp = ROOT_PATH . '/' . $cert['pdf_path'];
                    if (file_exists($fp)) @unlink($fp);
                }
                $db->prepare("DELETE FROM certificates WHERE id = :id")->execute([':id' => $delId]);
                auditLog('delete', 'certificate', 'Deleted cert #' . $delId . ' type=' . $cert['cert_type'] . ' user=' . $cert['user_id']);
                flashSet('success', $_lang === 'th' ? 'ลบใบรับรองเรียบร้อย' : 'Certificate deleted.');
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            flashSet('error', $_lang === 'th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.');
        }
        redirect($appUrl . '/admin/certificates.php');
    }

    // ── Upload ──────────────────────────────────────────
    $certType = post('cert_type');
    $userId   = intPost('user_id');
    $paperId  = intPost('paper_id') ?: null;
    $errors   = [];

    // Validate cert type
    if (!array_key_exists($certType, $certTypeMeta)) {
        $errors[] = $_lang === 'th' ? 'ประเภทใบรับรองไม่ถูกต้อง' : 'Invalid certificate type.';
    }

    // Validate file
    if (empty($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = $_lang === 'th' ? 'กรุณาเลือกไฟล์ PDF' : 'Please select a PDF file.';
    } elseif ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = $_lang === 'th' ? 'เกิดข้อผิดพลาดในการอัปโหลด (รหัส: ' . $_FILES['pdf_file']['error'] . ')' : 'Upload error (code: ' . $_FILES['pdf_file']['error'] . ').';
    } else {
        $f = $_FILES['pdf_file'];
        if ($f['size'] > 20 * 1024 * 1024) {
            $errors[] = $_lang === 'th' ? 'ไฟล์ขนาดเกิน 20 MB' : 'File exceeds 20 MB limit.';
        }
        // Strict PDF-only MIME check
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($f['tmp_name']);
        $ext   = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($mime !== 'application/pdf' || $ext !== 'pdf') {
            $errors[] = $_lang === 'th' ? 'อนุญาตเฉพาะไฟล์ PDF เท่านั้น' : 'Only PDF files are accepted.';
        }
    }

    if (!$errors) {
        try {
            $db = Database::getInstance();

            // Verify user exists
            $uStmt = $db->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = :uid LIMIT 1");
            $uStmt->execute([':uid' => $userId]);
            $uRow = $uStmt->fetch();
            if (!$uRow) {
                $errors[] = $_lang === 'th' ? 'ไม่พบผู้ใช้งาน' : 'User not found.';
            }
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $errors[] = $_lang === 'th' ? 'เกิดข้อผิดพลาด' : 'Database error.';
        }
    }

    if (!$errors) {
        try {
            $db = Database::getInstance();

            $recipientName = trim($uRow['first_name'] . ' ' . $uRow['last_name']);

            // Generate safe filename
            $filename = strtoupper($certType) . '_' . $userId . '_' . time() . '.pdf';
            $destPath = CERT_UPLOAD_PATH . '/' . $filename;
            $relPath  = 'uploads/certificates/' . $filename;

            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $destPath)) {
                throw new \RuntimeException('Failed to move uploaded file.');
            }

            // Upsert: if same cert_type + user + paper already exists, replace file
            $chk = $db->prepare("
                SELECT id, pdf_path FROM certificates
                WHERE cert_type = :ct AND user_id = :uid
                  AND (paper_id = :pid OR (:pid IS NULL AND paper_id IS NULL))
                LIMIT 1
            ");
            $chk->execute([':ct' => $certType, ':uid' => $userId, ':pid' => $paperId]);
            $existing = $chk->fetch();

            if ($existing) {
                // Remove old file
                if ($existing['pdf_path']) {
                    $oldFile = ROOT_PATH . '/' . $existing['pdf_path'];
                    if (file_exists($oldFile)) @unlink($oldFile);
                }
                $upd = $db->prepare("
                    UPDATE certificates
                    SET recipient_name = :rn, pdf_path = :pp, generated_at = NOW()
                    WHERE id = :id
                ");
                $upd->execute([':rn' => $recipientName, ':pp' => $relPath, ':id' => $existing['id']]);
                $certDbId = $existing['id'];
            } else {
                $ins = $db->prepare("
                    INSERT INTO certificates (cert_type, user_id, paper_id, recipient_name, pdf_path)
                    VALUES (:ct, :uid, :pid, :rn, :pp)
                ");
                $ins->execute([
                    ':ct'  => $certType,
                    ':uid' => $userId,
                    ':pid' => $paperId,
                    ':rn'  => $recipientName,
                    ':pp'  => $relPath,
                ]);
                $certDbId = (int)$db->lastInsertId();
            }

            // Notify recipient
            $typeTh = $certTypeMeta[$certType]['th'];
            $typeEn = $certTypeMeta[$certType]['en'];
            Notification::create(
                $userId,
                'certificate',
                'ใบรับรองของท่านพร้อมแล้ว',
                'Your certificate is ready',
                "ใบรับรอง{$typeTh}ได้ถูกออกให้แก่ท่านแล้ว กรุณาเข้าสู่หน้าใบรับรองเพื่อดาวน์โหลด",
                "Your {$typeEn} certificate has been issued. Please visit the Certificates page to download it.",
                $paperId,
                'system'
            );

            // Email notification (non-fatal — errors here don't block the upload)
            try {
                $certPaperCode = null;
                if ($paperId) {
                    $pcStmt = $db->prepare("SELECT paper_code FROM papers WHERE id = :pid LIMIT 1");
                    $pcStmt->execute([':pid' => $paperId]);
                    $certPaperCode = $pcStmt->fetchColumn() ?: null;
                }
                Mail::sendCertificateReady($uRow['email'], $recipientName, $typeEn, $certPaperCode, $uRow['role']);
            } catch (\Throwable $e) {
                error_log('Mail::sendCertificateReady failed: ' . $e->getMessage());
            }

            auditLog('upload', 'certificate', "Uploaded cert #{$certDbId} type={$certType} user={$userId}");
            flashSet('success', $_lang === 'th'
                ? "อัปโหลดใบรับรองให้ {$recipientName} เรียบร้อย"
                : "Certificate uploaded for {$recipientName} successfully.");

        } catch (\Throwable $e) {
            error_log($e->getMessage());
            flashSet('error', $_lang === 'th' ? 'เกิดข้อผิดพลาดในการบันทึก' : 'Failed to save certificate.');
        }
    } else {
        flashSet('error', implode(' ', $errors));
    }

    redirect($appUrl . '/admin/certificates.php');
}

/* ── GET: fetch data ────────────────────────────────────── */
$page      = max(1, intGet('page', 1));
$perPage   = 20;
$search    = sanitize(get('q'));
$typeFilter = sanitize(get('type'));

$where  = ['1=1'];
$params = [];
if ($typeFilter) {
    $where[]         = "c.cert_type = :type";
    $params[':type'] = $typeFilter;
}

try {
    $db = Database::getInstance();
    $isMysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
    if ($search) {
        $where[] = $isMysql
            ? "(CONCAT(u.first_name, ' ', u.last_name) LIKE :q OR u.email LIKE :q)"
            : "((u.first_name || ' ' || u.last_name) ILIKE :q OR u.email ILIKE :q)";
        $params[':q'] = "%{$search}%";
    }
    $whereStr = implode(' AND ', array_values($where));

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM certificates c JOIN users u ON u.id = c.user_id WHERE {$whereStr}");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();
    $pg    = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT c.*,
               (u.first_name || ' ' || u.last_name) AS user_name,
               u.email AS user_email,
               u.role  AS user_role,
               p.paper_code,
               (p.title_en) AS paper_title
        FROM certificates c
        JOIN users u ON u.id = c.user_id
        LEFT JOIN papers p ON p.id = c.paper_id
        WHERE {$whereStr}
        ORDER BY c.generated_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $certs = $stmt->fetchAll();

    // All users (author + reviewer) for upload form
    $allUsers = $db->query("
        SELECT id, first_name, last_name, email, role
        FROM users
        WHERE role IN ('author','reviewer')
        ORDER BY first_name, last_name
    ")->fetchAll();

    // All accepted/published papers for paper dropdown (preload as JSON)
    $allPapers = $db->query("
        SELECT id, submitter_id, paper_code, title_en, title_th, status_code
        FROM papers
        WHERE status_code IN ('accepted','published')
        ORDER BY paper_code
    ")->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $certs = []; $total = 0; $pg = paginate(0, $perPage, 1);
    $allUsers = []; $allPapers = [];
}

$papersJson = json_encode(array_values($allPapers), JSON_UNESCAPED_UNICODE);

$pageTitle  = $_lang === 'th' ? 'จัดการใบรับรอง' : 'Certificates';
$activeMenu = 'certificates';
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
  <style>
    /* ── Upload Zone ── */
    .upload-zone {
      border: 2px dashed var(--gray-200);
      border-radius: var(--radius-lg);
      padding: 28px 20px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
      background: var(--gray-100);
      position: relative;
    }
    .upload-zone:hover,
    .upload-zone.drag-over { border-color: var(--blue-mid); background: var(--blue-light); }
    .upload-zone input[type=file] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .upload-zone-icon { font-size: 2rem; color: var(--blue-mid); margin-bottom: 8px; }
    .upload-zone-label { font-size: .88rem; color: var(--gray-700); font-weight: 600; }
    .upload-zone-hint  { font-size: .78rem; color: var(--gray-500); margin-top: 4px; }
    .upload-zone.has-file { border-color: var(--success); background: #f0fdf4; }
    .upload-zone.has-file .upload-zone-icon { color: var(--success); }

    /* ── User search autocomplete ── */
    .user-search-wrap { position: relative; }
    .user-search-input {
      width: 100%;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 9px 14px;
      font-size: .88rem;
      outline: none;
      background: var(--white);
      display: block;
    }
    .user-search-input:focus { border-color: var(--blue-mid); }
    .user-search-input.has-selection {
      border-color: var(--success);
      background: #f0fdf4;
    }
    #userDropdown {
      display: none;
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      z-index: 999;
      background: var(--white);
      border: 1.5px solid var(--blue-mid);
      border-top: none;
      border-radius: 0 0 var(--radius) var(--radius);
      max-height: 200px;
      overflow-y: auto;
      box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }
    #userDropdown.open { display: block; }
    .user-option {
      padding: 8px 14px;
      font-size: .84rem;
      cursor: pointer;
      border-bottom: 1px solid var(--gray-100);
    }
    .user-option:last-child { border-bottom: none; }
    .user-option:hover, .user-option.active { background: var(--blue-light); }
    .user-option-name { font-weight: 600; }
    .user-option-sub { font-size: .75rem; color: var(--gray-500); }
    #userNoResult {
      padding: 10px 14px;
      font-size: .82rem;
      color: var(--gray-500);
    }

    /* ── Type badge colours ── */
    .cert-badge-acceptance   { background: #dbeafe; color: #1e40af; }
    .cert-badge-presentation { background: #dcfce7; color: #166534; }
    .cert-badge-attendance   { background: #ede9fe; color: #5b21b6; }
    .cert-badge-reviewer     { background: #fef9c3; color: #854d0e; }

    /* ── Responsive table tweaks ── */
    @media (max-width: 767px) {
      .dashboard-content { padding: 16px; }
      .table-custom th:nth-child(3),
      .table-custom td:nth-child(3) { display: none; }   /* hide Paper col on mobile */
    }
  </style>
</head>
<body>

<div class="dashboard-wrap">
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_admin.php'; ?>

  <main class="dashboard-content">

    <!-- Header -->
    <div class="dash-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
      <div>
        <h1 class="dash-title">
          <i class="fas fa-certificate me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
        </h1>
        <p class="dash-breadcrumb">
          <?= $total ?> <?= $_lang === 'th' ? 'ใบรับรองในระบบ' : 'certificate(s) in system' ?>
        </p>
      </div>
    </div>

    <?= flashHtml() ?>

    <!-- ── Upload Form ─────────────────────────────────── -->
    <div class="content-card mb-4">
      <div class="content-card-title">
        <i class="fas fa-cloud-upload-alt me-2" style="color:var(--gold);"></i>
        <?= $_lang === 'th' ? 'อัปโหลดใบรับรอง' : 'Upload Certificate' ?>
      </div>

      <form method="POST" enctype="multipart/form-data" id="uploadForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="upload">

        <div class="row g-3">

          <!-- Cert type -->
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label">
              <?= $_lang === 'th' ? 'ประเภทใบรับรอง' : 'Certificate Type' ?>
              <span class="required">*</span>
            </label>
            <select name="cert_type" id="certTypeSelect" class="form-select" required>
              <?php foreach ($certTypeMeta as $code => $m): ?>
                <option value="<?= $code ?>" data-needs-paper="<?= $m['needs_paper'] ? '1' : '0' ?>">
                  <?= $_lang === 'th' ? $m['th'] : $m['en'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- User search + select -->
          <div class="col-12 col-sm-6 col-lg-4">
            <label class="form-label">
              <?= $_lang === 'th' ? 'ผู้รับ' : 'Recipient' ?>
              <span class="required">*</span>
            </label>
            <div class="user-search-wrap">
              <input type="hidden" name="user_id" id="userIdInput">
              <input type="text" id="userSearchInput" class="user-search-input"
                     placeholder="<?= $_lang === 'th' ? 'ค้นหาชื่อหรืออีเมล…' : 'Search name or email…' ?>"
                     autocomplete="off">
              <div id="userDropdown">
                <!-- populated by JS -->
              </div>
            </div>
          </div>

          <!-- Paper (shown only for cert types that need it) -->
          <div class="col-12 col-sm-6 col-lg-3" id="paperGroup">
            <label class="form-label">
              <?= $_lang === 'th' ? 'บทคัดย่อที่เกี่ยวข้อง' : 'Linked Paper' ?>
            </label>
            <select name="paper_id" id="paperSelect" class="form-select">
              <option value=""><?= $_lang === 'th' ? '— ไม่ระบุ —' : '— None —' ?></option>
            </select>
            <div class="form-text" style="font-size:.75rem;">
              <?= $_lang === 'th' ? 'เฉพาะบทคัดย่อที่ได้รับการยอมรับ' : 'Only accepted / published papers' ?>
            </div>
          </div>

          <!-- PDF upload -->
          <div class="col-12 col-lg-2 d-flex flex-column justify-content-start">
            <label class="form-label">
              <?= $_lang === 'th' ? 'ไฟล์ PDF' : 'PDF File' ?>
              <span class="required">*</span>
            </label>
            <div class="upload-zone" id="uploadZone">
              <input type="file" name="pdf_file" id="pdfFileInput" accept=".pdf,application/pdf" required>
              <div class="upload-zone-icon"><i class="fas fa-file-pdf"></i></div>
              <div class="upload-zone-label" id="uploadZoneLabel">
                <?= $_lang === 'th' ? 'คลิกหรือลากไฟล์ PDF' : 'Click or drag PDF here' ?>
              </div>
              <div class="upload-zone-hint"><?= $_lang === 'th' ? 'สูงสุด 20 MB' : 'Max 20 MB' ?></div>
            </div>
          </div>

        </div><!-- /.row -->

        <div class="mt-3 d-flex gap-2 flex-wrap">
          <button type="submit" class="btn-primary-custom" id="submitBtn">
            <i class="fas fa-cloud-upload-alt me-2"></i>
            <?= $_lang === 'th' ? 'อัปโหลดใบรับรอง' : 'Upload Certificate' ?>
          </button>
          <button type="reset" class="btn btn-outline-secondary rounded-pill" style="font-size:.9rem;">
            <i class="fas fa-undo me-2"></i><?= $_lang === 'th' ? 'ล้างฟอร์ม' : 'Reset' ?>
          </button>
        </div>

      </form>
    </div><!-- /.content-card upload -->

    <!-- ── Filter ──────────────────────────────────────── -->
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap align-items-end">
      <input type="text" name="q" class="form-control" style="max-width:220px;"
             placeholder="<?= $_lang === 'th' ? 'ค้นหาชื่อ / อีเมล…' : 'Search name / email…' ?>"
             value="<?= e($search) ?>">
      <select name="type" class="form-select" style="max-width:180px;">
        <option value=""><?= $_lang === 'th' ? 'ทุกประเภท' : 'All Types' ?></option>
        <?php foreach ($certTypeMeta as $code => $m): ?>
          <option value="<?= $code ?>" <?= $typeFilter === $code ? 'selected' : '' ?>>
            <?= $_lang === 'th' ? $m['th'] : $m['en'] ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary-custom" style="padding:10px 20px;font-size:.85rem;">
        <i class="fas fa-search"></i>
      </button>
      <?php if ($search || $typeFilter): ?>
        <a href="?" class="btn btn-outline-secondary rounded-pill" style="font-size:.85rem;">
          <i class="fas fa-times me-1"></i><?= $_lang === 'th' ? 'ล้าง' : 'Clear' ?>
        </a>
      <?php endif; ?>
    </form>

    <!-- ── Certificate List ────────────────────────────── -->
    <div class="table-card">
      <?php if (empty($certs)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-certificate fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= $_lang === 'th' ? 'ยังไม่มีใบรับรอง' : 'No certificates yet' ?></h5>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= $_lang === 'th' ? 'ผู้รับ' : 'Recipient' ?></th>
                <th><?= $_lang === 'th' ? 'ประเภท' : 'Type' ?></th>
                <th><?= $_lang === 'th' ? 'บทคัดย่อ' : 'Paper' ?></th>
                <th><?= $_lang === 'th' ? 'วันที่ออก' : 'Issued' ?></th>
                <th style="width:100px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($certs as $c):
                $m = $certTypeMeta[$c['cert_type']] ?? ['th' => $c['cert_type'], 'en' => $c['cert_type'], 'color' => '#6c757d', 'icon' => 'fa-certificate'];
              ?>
                <tr>
                  <td>
                    <div style="font-weight:600;font-size:.88rem;"><?= e($c['user_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--gray-500);"><?= e($c['user_email']) ?></div>
                    <span class="badge" style="background:var(--gray-200);color:var(--gray-700);font-size:.68rem;font-weight:500;">
                      <?= $_lang === 'th'
                        ? ($c['user_role'] === 'author' ? 'ผู้แต่ง' : 'ผู้ทรง')
                        : ucfirst($c['user_role']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge cert-badge-<?= $c['cert_type'] ?>" style="font-size:.75rem;padding:5px 10px;border-radius:8px;">
                      <i class="fas <?= $m['icon'] ?> me-1"></i>
                      <?= $_lang === 'th' ? $m['th'] : $m['en'] ?>
                    </span>
                  </td>
                  <td style="font-size:.82rem;">
                    <?php if ($c['paper_code']): ?>
                      <code style="color:var(--blue-mid);font-size:.78rem;"><?= e($c['paper_code']) ?></code>
                      <?php if ($c['paper_title']): ?>
                        <div style="font-size:.75rem;color:var(--gray-500);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                             title="<?= e($c['paper_title']) ?>">
                          <?= e($c['paper_title']) ?>
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color:var(--gray-400);">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;white-space:nowrap;">
                    <?= humanDate($c['generated_at'], $_lang) ?>
                  </td>
                  <td>
                    <div class="d-flex gap-1 flex-wrap">
                      <?php if ($c['pdf_path']): ?>
                        <a href="<?= $appUrl ?>/download.php?cert_id=<?= (int)$c['id'] ?>"
                           class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;"
                           target="_blank" title="<?= $_lang === 'th' ? 'ดาวน์โหลด' : 'Download' ?>">
                          <i class="fas fa-download"></i>
                        </a>
                      <?php endif; ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="cert_id" value="<?= (int)$c['id'] ?>">
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger rounded-pill"
                                style="font-size:.72rem;"
                                title="<?= $_lang === 'th' ? 'ลบ' : 'Delete' ?>"
                                data-confirm="<?= $_lang === 'th' ? 'ยืนยันการลบใบรับรองนี้?' : 'Delete this certificate?' ?>">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
               style="border-top:1px solid var(--gray-200);">
            <span style="font-size:.85rem;color:var(--gray-500);">
              <?= t('common.page') ?> <?= $pg['page'] ?> <?= t('common.of') ?> <?= $pg['total_pages'] ?>
            </span>
            <div class="d-flex gap-2">
              <?php if ($pg['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pg['page'] - 1])) ?>"
                   class="btn btn-sm btn-outline-secondary rounded-pill">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php endif; ?>
              <?php if ($pg['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pg['page'] + 1])) ?>"
                   class="btn btn-sm btn-outline-secondary rounded-pill">
                  <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div><!-- /.table-card -->

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
(function () {
  /* ── Users data ── */
  const allUsers = <?= json_encode(array_values($allUsers), JSON_UNESCAPED_UNICODE) ?>;

  /* ── Papers data (keyed by submitter_id) ── */
  const papers = <?= $papersJson ?>;
  const papersByUser = {};
  papers.forEach(p => {
    if (!papersByUser[p.submitter_id]) papersByUser[p.submitter_id] = [];
    papersByUser[p.submitter_id].push(p);
  });

  const certTypeSelect = document.getElementById('certTypeSelect');
  const userIdInput    = document.getElementById('userIdInput');
  const userSearch     = document.getElementById('userSearchInput');
  const userDropdown   = document.getElementById('userDropdown');
  const paperGroup     = document.getElementById('paperGroup');
  const paperSelect    = document.getElementById('paperSelect');
  const uploadZone     = document.getElementById('uploadZone');
  const pdfInput       = document.getElementById('pdfFileInput');
  const uploadLabel    = document.getElementById('uploadZoneLabel');
  const submitBtn      = document.getElementById('submitBtn');

  const roleLabelTh = { author: 'ผู้แต่ง', reviewer: 'ผู้ทรง' };
  const isTh = <?= $_lang === 'th' ? 'true' : 'false' ?>;
  let selectedUserId = '';

  /* ── Select a user ── */
  function selectUser(u) {
    selectedUserId  = String(u.id);
    userIdInput.value = selectedUserId;
    const roleLabel = isTh ? (roleLabelTh[u.role] || u.role) : (u.role.charAt(0).toUpperCase() + u.role.slice(1));
    userSearch.value = u.first_name + ' ' + u.last_name + ' (' + u.email + ') [' + roleLabel + ']';
    userSearch.classList.add('has-selection');
    closeDropdown();
    updatePaperDropdown();
  }

  /* ── Open dropdown with filtered list ── */
  function openDropdown(q) {
    const term = (q || '').toLowerCase();
    const matched = allUsers.filter(u => {
      const full = (u.first_name + ' ' + u.last_name + ' ' + u.email).toLowerCase();
      return !term || full.includes(term);
    });

    userDropdown.innerHTML = '';
    if (matched.length === 0) {
      const noRes = document.createElement('div');
      noRes.id = 'userNoResult';
      noRes.textContent = isTh ? 'ไม่พบผู้ใช้งาน' : 'No users found';
      userDropdown.appendChild(noRes);
    } else {
      matched.forEach(u => {
        const roleLabel = isTh ? (roleLabelTh[u.role] || u.role) : (u.role.charAt(0).toUpperCase() + u.role.slice(1));
        const div = document.createElement('div');
        div.className = 'user-option';
        div.innerHTML = '<div class="user-option-name">' + u.first_name + ' ' + u.last_name +
          ' <span style="font-weight:400;font-size:.75rem;">[' + roleLabel + ']</span></div>' +
          '<div class="user-option-sub">' + u.email + '</div>';
        div.addEventListener('mousedown', function (e) {
          e.preventDefault(); // prevent blur before click registers
          selectUser(u);
        });
        userDropdown.appendChild(div);
      });
    }
    userDropdown.classList.add('open');
  }

  function closeDropdown() {
    userDropdown.classList.remove('open');
  }

  userSearch.addEventListener('focus', function () {
    if (!selectedUserId) openDropdown(this.value);
  });

  userSearch.addEventListener('input', function () {
    selectedUserId = '';
    userIdInput.value = '';
    userSearch.classList.remove('has-selection');
    openDropdown(this.value);
    updatePaperDropdown();
  });

  userSearch.addEventListener('blur', function () {
    setTimeout(closeDropdown, 150);
  });

  // Reset button: clear selection
  document.querySelector('button[type=reset]').addEventListener('click', function () {
    setTimeout(() => {
      selectedUserId = '';
      userIdInput.value = '';
      userSearch.value = '';
      userSearch.classList.remove('has-selection');
      closeDropdown();
      updatePaperDropdown();
    }, 0);
  });

  /* ── Paper dropdown update ── */
  function updatePaperDropdown() {
    const userId    = parseInt(selectedUserId, 10);
    const certType  = certTypeSelect.value;
    const needsPaper = certTypeSelect.selectedOptions[0]?.dataset.needsPaper === '1';

    paperGroup.style.display = needsPaper ? '' : 'none';
    paperSelect.innerHTML = '<option value=""><?= $_lang === 'th' ? '— ไม่ระบุ —' : '— None —' ?></option>';

    if (!needsPaper || !userId) return;

    const userPapers = papersByUser[userId] || [];
    userPapers.forEach(p => {
      if (certType === 'presentation' && p.status_code !== 'published') return;
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.paper_code + ' — ' + (p.title_en || p.title_th || '');
      paperSelect.appendChild(opt);
    });
  }

  certTypeSelect.addEventListener('change', updatePaperDropdown);
  updatePaperDropdown(); // initial

  /* ── Upload zone feedback ── */
  pdfInput.addEventListener('change', function () {
    if (this.files.length) {
      const name = this.files[0].name;
      uploadLabel.textContent = name.length > 28 ? name.slice(0, 26) + '…' : name;
      uploadZone.classList.add('has-file');
    } else {
      uploadLabel.textContent = '<?= $_lang === 'th' ? 'คลิกหรือลากไฟล์ PDF' : 'Click or drag PDF here' ?>';
      uploadZone.classList.remove('has-file');
    }
  });

  uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
  uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
  uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
      pdfInput.files = e.dataTransfer.files;
      pdfInput.dispatchEvent(new Event('change'));
    }
  });

  /* ── Confirm delete ── */
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
  });

  /* ── Form submit guard ── */
  document.getElementById('uploadForm').addEventListener('submit', function (e) {
    if (!userIdInput.value) {
      e.preventDefault();
      alert('<?= $_lang === 'th' ? 'กรุณาเลือกผู้รับ' : 'Please select a recipient.' ?>');
      userSearch.focus();
      return;
    }
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?= $_lang === 'th' ? 'กำลังอัปโหลด…' : 'Uploading…' ?>';
  });
})();
</script>
</body>
</html>
