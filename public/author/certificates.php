<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Certificate;

Auth::require('author');
$user   = Auth::user();
$_lang  = lang();
$appUrl = APP_URL;
$uid    = $user['id'];

// Handle certificate generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $certType = post('cert_type');
    $paperId  = intPost('paper_id') ?: null;

    $allowedTypes = ['acceptance', 'presentation'];
    if (!in_array($certType, $allowedTypes)) {
        flashSet('error', $_lang==='th' ? 'ประเภทใบรับรองไม่ถูกต้อง' : 'Invalid certificate type.');
        redirect($appUrl . '/author/certificates.php');
    }

    try {
        $db = Database::getInstance();

        // Verify paper belongs to user and is in correct status
        if ($paperId) {
            $pStmt = $db->prepare("SELECT * FROM papers WHERE id = :pid AND submitter_id = :uid AND status_code IN ('accepted','published')");
            $pStmt->execute([':pid' => $paperId, ':uid' => $uid]);
            $pData = $pStmt->fetch();
            if (!$pData) {
                flashSet('error', $_lang==='th' ? 'ไม่พบบทความหรือสถานะไม่ถูกต้อง' : 'Paper not found or status invalid.');
                redirect($appUrl . '/author/certificates.php');
            }
        }

        // Check if already generated
        $existCheck = $db->prepare("SELECT * FROM certificates WHERE user_id = :uid AND cert_type = :ct AND paper_id " . ($paperId ? "= :pid" : "IS NULL") . " LIMIT 1");
        $existParams = [':uid' => $uid, ':ct' => $certType];
        if ($paperId) $existParams[':pid'] = $paperId;
        $existCheck->execute($existParams);
        $existing = $existCheck->fetch();

        if ($existing && $existing['file_path'] && file_exists(ROOT_PATH . '/' . $existing['file_path'])) {
            // Return existing
            redirect($appUrl . '/download.php?cert_id=' . $existing['id']);
        }

        // Generate new
        $paperTitle = $paperId ? ($pData['title_en'] ?: $pData['title_th']) : null;
        $filePath = Certificate::generate($certType, $uid, $user['name'], $paperId, $paperTitle);

        if ($filePath) {
            flashSet('success', $_lang==='th' ? 'สร้างใบรับรองเรียบร้อย' : 'Certificate generated successfully.');
            // Find the newly created cert
            $newCert = $db->prepare("SELECT id FROM certificates WHERE user_id = :uid AND cert_type = :ct ORDER BY issued_at DESC LIMIT 1");
            $newCert->execute([':uid' => $uid, ':ct' => $certType]);
            $certRow = $newCert->fetch();
            if ($certRow) {
                redirect($appUrl . '/download.php?cert_id=' . $certRow['id']);
            }
        } else {
            flashSet('error', $_lang==='th' ? 'ไม่สามารถสร้างใบรับรองได้' : 'Could not generate certificate.');
        }

    } catch (\Throwable $e) {
        error_log($e->getMessage());
        flashSet('error', $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.');
    }

    redirect($appUrl . '/author/certificates.php');
}

try {
    $db = Database::getInstance();

    // User's papers that qualify for certificates
    $papersStmt = $db->prepare("
        SELECT p.id, p.paper_code, p.title_th, p.title_en, p.status_code
        FROM papers p
        WHERE p.submitter_id = :uid AND p.status_code IN ('accepted', 'published')
        ORDER BY p.submitted_at DESC
    ");
    $papersStmt->execute([':uid' => $uid]);
    $qualifiedPapers = $papersStmt->fetchAll();

    // Existing certificates
    $certStmt = $db->prepare("SELECT * FROM certificates WHERE user_id = :uid ORDER BY issued_at DESC");
    $certStmt->execute([':uid' => $uid]);
    $myCerts = $certStmt->fetchAll();

    // Index by type+paper
    $certIndex = [];
    foreach ($myCerts as $c) {
        $key = $c['cert_type'] . '_' . ($c['paper_id'] ?? 'null');
        $certIndex[$key] = $c;
    }

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $qualifiedPapers = []; $myCerts = []; $certIndex = [];
}

$pageTitle  = t('author.certificates');
$activeMenu = 'certificates';

$certTypes = [
    'acceptance' => [
        'icon'    => 'fa-file-contract',
        'name_th' => 'ใบตอบรับบทความ',
        'name_en' => 'Acceptance Certificate',
        'desc_th' => 'ใบรับรองว่าบทความได้รับการยอมรับเพื่อนำเสนอในงานประชุม',
        'desc_en' => 'Certifies that the paper has been accepted for presentation',
        'status'  => ['accepted', 'published'],
    ],
    'presentation' => [
        'icon'    => 'fa-chalkboard-teacher',
        'name_th' => 'ใบรับรองการนำเสนอ',
        'name_en' => 'Presentation Certificate',
        'desc_th' => 'ใบรับรองการนำเสนอบทความในงานประชุม ICALGC 2026',
        'desc_en' => 'Certifies participation as a presenter at ICALGC 2026',
        'status'  => ['published'],
    ],
];
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
</head>
<body>

<div class="dashboard-wrap">
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_author.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header">
      <h1 class="dash-title">
        <i class="fas fa-certificate me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?>
      </h1>
      <p class="dash-breadcrumb"><?= $_lang==='th' ? 'ดาวน์โหลดและจัดการใบรับรอง' : 'Download and manage your certificates' ?></p>
    </div>

    <?= flashHtml() ?>

    <?php if (empty($qualifiedPapers)): ?>
      <div class="content-card text-center p-5">
        <i class="fas fa-certificate fa-3x mb-3" style="color:var(--gray-200);"></i>
        <h5 style="color:var(--gray-500);">
          <?= $_lang==='th' ? 'ยังไม่มีบทความที่ผ่านการยอมรับ' : 'No accepted papers yet' ?>
        </h5>
        <p style="color:var(--gray-400);font-size:.9rem;">
          <?= $_lang==='th'
            ? 'ใบรับรองจะพร้อมให้ดาวน์โหลดเมื่อบทความของท่านได้รับการยอมรับ'
            : 'Certificates will be available once your paper is accepted.' ?>
        </p>
        <a href="<?= $appUrl ?>/author/submit.php" class="btn-primary-custom mt-2">
          <i class="fas fa-file-upload me-2"></i><?= t('author.submit_paper') ?>
        </a>
      </div>
    <?php else: ?>

      <?php foreach ($qualifiedPapers as $p): ?>
        <div class="content-card mb-4">
          <div class="content-card-title">
            <i class="fas fa-file-alt me-2" style="color:var(--gold);"></i>
            <code style="color:var(--blue-mid);font-size:.9rem;"><?= e($p['paper_code']) ?></code>
            <span class="ms-2" style="color:var(--gray-600);font-size:.85rem;font-weight:400;">
              <?= e($_lang==='th' ? $p['title_th'] : $p['title_en']) ?>
            </span>
            <span class="ms-2"><?= statusBadge($p['status_code']) ?></span>
          </div>

          <div class="row g-3">
            <?php foreach ($certTypes as $type => $ct):
              if (!in_array($p['status_code'], $ct['status'])) continue;
              $certKey = $type . '_' . $p['id'];
              $existing = $certIndex[$certKey] ?? null;
            ?>
              <div class="col-md-6">
                <div class="p-4 rounded" style="border:1px solid var(--gray-200);background:var(--gray-100);height:100%;">
                  <div class="d-flex align-items-start gap-3 mb-3">
                    <div style="width:48px;height:48px;background:var(--blue-dark);color:var(--gold);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
                      <i class="fas <?= $ct['icon'] ?>"></i>
                    </div>
                    <div>
                      <div style="font-weight:700;color:var(--blue-dark);font-size:.95rem;">
                        <?= $_lang==='th' ? $ct['name_th'] : $ct['name_en'] ?>
                      </div>
                      <div style="font-size:.8rem;color:var(--gray-500);">
                        <?= $_lang==='th' ? $ct['desc_th'] : $ct['desc_en'] ?>
                      </div>
                    </div>
                  </div>

                  <?php if ($existing): ?>
                    <div class="d-flex gap-2 align-items-center">
                      <span style="font-size:.78rem;color:#198754;"><i class="fas fa-check-circle me-1"></i><?= humanDate($existing['issued_at'], $_lang) ?></span>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                      <a href="<?= $appUrl ?>/download.php?cert_id=<?= (int)$existing['id'] ?>"
                         class="btn btn-success btn-sm rounded-pill">
                        <i class="fas fa-download me-1"></i><?= $_lang==='th' ? 'ดาวน์โหลด' : 'Download' ?>
                      </a>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                        <input type="hidden" name="cert_type" value="<?= $type ?>">
                        <input type="hidden" name="paper_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill">
                          <i class="fas fa-sync-alt me-1"></i><?= $_lang==='th' ? 'สร้างใหม่' : 'Regenerate' ?>
                        </button>
                      </form>
                    </div>
                  <?php else: ?>
                    <form method="POST">
                      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                      <input type="hidden" name="cert_type" value="<?= $type ?>">
                      <input type="hidden" name="paper_id" value="<?= (int)$p['id'] ?>">
                      <button type="submit" class="btn-gold btn-sm" style="font-size:.82rem;">
                        <i class="fas fa-magic me-2"></i><?= $_lang==='th' ? 'สร้างใบรับรอง' : 'Generate Certificate' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- All Certificates History -->
      <?php if (!empty($myCerts)): ?>
      <div class="table-card mt-4">
        <div class="table-card-header">
          <span class="table-card-title"><i class="fas fa-history me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'ประวัติใบรับรอง' : 'Certificate History' ?></span>
        </div>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= $_lang==='th' ? 'ประเภท' : 'Type' ?></th>
                <th><?= $_lang==='th' ? 'ชื่อผู้รับ' : 'Recipient' ?></th>
                <th><?= $_lang==='th' ? 'วันที่ออก' : 'Issued' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myCerts as $c):
                $ct = $certTypes[$c['cert_type']] ?? null;
              ?>
                <tr>
                  <td>
                    <?php if ($ct): ?>
                      <i class="fas <?= $ct['icon'] ?> me-2" style="color:var(--blue-mid);"></i>
                      <?= $_lang==='th' ? $ct['name_th'] : $ct['name_en'] ?>
                    <?php else: ?>
                      <?= e(ucfirst($c['cert_type'])) ?>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.85rem;"><?= e($c['recipient_name']) ?></td>
                  <td style="font-size:.85rem;"><?= humanDate($c['issued_at'], $_lang) ?></td>
                  <td>
                    <a href="<?= $appUrl ?>/download.php?cert_id=<?= (int)$c['id'] ?>"
                       class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.75rem;">
                      <i class="fas fa-download"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
