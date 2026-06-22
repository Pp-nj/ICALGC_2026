<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Certificate;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$errors = [];

// Bulk generate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));
    $certType = post('cert_type');
    $scope    = post('scope');

    $allowedTypes = ['attendance', 'presentation', 'acceptance'];
    if (!in_array($certType, $allowedTypes)) {
        flashSet('error', $_lang==='th' ? 'ประเภทไม่ถูกต้อง' : 'Invalid type.');
        redirect($appUrl . '/admin/certificates.php');
    }

    try {
        $db = Database::getInstance();

        if ($scope === 'all_authors') {
            // All authors who submitted at least one paper
            $users = $db->query("SELECT DISTINCT u.id, u.name FROM users u JOIN papers p ON p.submitter_id = u.id WHERE u.role = 'author'")->fetchAll();
        } elseif ($scope === 'accepted_authors') {
            $users = $db->query("SELECT DISTINCT u.id, u.name FROM users u JOIN papers p ON p.submitter_id = u.id WHERE p.status_code IN ('accepted','published')")->fetchAll();
        } else {
            $users = [];
        }

        $count = 0;
        foreach ($users as $u) {
            // Find latest qualifying paper
            $pStmt = $db->prepare("SELECT * FROM papers WHERE submitter_id = :uid AND status_code IN ('accepted','published') ORDER BY submitted_at DESC LIMIT 1");
            $pStmt->execute([':uid' => $u['id']]);
            $p = $pStmt->fetch();
            $paperTitle = $p ? ($p['title_en'] ?: $p['title_th']) : null;
            $paperId    = $p ? $p['id'] : null;

            $result = Certificate::generate($certType, $u['id'], $u['name'], $paperId, $paperTitle);
            if ($result) $count++;
        }

        flashSet('success', ($_lang==='th' ? "สร้างใบรับรอง $count ฉบับเรียบร้อย" : "Generated $count certificate(s) successfully."));

    } catch (\Throwable $e) {
        error_log($e->getMessage());
        flashSet('error', $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.');
    }
    redirect($appUrl . '/admin/certificates.php');
}

$page    = max(1, intGet('page', 1));
$perPage = 20;
$search  = sanitize(get('q'));
$typeFilter = sanitize(get('type'));

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = "(u.name ILIKE :q OR c.cert_type ILIKE :q)"; $params[':q'] = "%$search%"; }
if ($typeFilter) { $where[] = "c.cert_type = :type"; $params[':type'] = $typeFilter; }
$whereStr = implode(' AND ', $where);

try {
    $db = Database::getInstance();
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM certificates c JOIN users u ON u.id = c.user_id WHERE $whereStr");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();
    $pg    = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT c.*, u.name AS user_name, u.email AS user_email,
               p.paper_code, p.title_en AS paper_title
        FROM certificates c
        JOIN users u ON u.id = c.user_id
        LEFT JOIN papers p ON p.id = c.paper_id
        WHERE $whereStr
        ORDER BY c.issued_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $certs = $stmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $certs = []; $total = 0; $pg = paginate(0,$perPage,1);
}

$pageTitle  = t('author.certificates');
$activeMenu = 'certificates';

$certTypeLabels = [
    'acceptance'   => ['th'=>'ใบตอบรับ', 'en'=>'Acceptance'],
    'presentation' => ['th'=>'ใบนำเสนอ', 'en'=>'Presentation'],
    'attendance'   => ['th'=>'ใบเข้าร่วม', 'en'=>'Attendance'],
    'reviewer'     => ['th'=>'ใบผู้ทรง', 'en'=>'Reviewer'],
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
  <?php require_once __DIR__ . '/../../app/helpers/sidebar_admin.php'; ?>

  <main class="dashboard-content">
    <div class="dash-header d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="dash-title"><i class="fas fa-certificate me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th' ? 'ใบรับรอง' : 'certificate(s)' ?></p>
      </div>
    </div>

    <?= flashHtml() ?>

    <!-- Bulk Generate -->
    <div class="content-card mb-4">
      <div class="content-card-title">
        <i class="fas fa-magic me-2" style="color:var(--gold);"></i><?= $_lang==='th' ? 'สร้างใบรับรองจำนวนมาก' : 'Bulk Generate Certificates' ?>
      </div>
      <form method="POST" class="row g-3 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <div class="col-md-4">
          <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'ประเภทใบรับรอง' : 'Certificate Type' ?></label>
          <select name="cert_type" class="form-select" required>
            <option value="acceptance"><?= $_lang==='th' ? 'ใบตอบรับ' : 'Acceptance Certificate' ?></option>
            <option value="presentation"><?= $_lang==='th' ? 'ใบนำเสนอ' : 'Presentation Certificate' ?></option>
            <option value="attendance"><?= $_lang==='th' ? 'ใบเข้าร่วมงาน' : 'Attendance Certificate' ?></option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold" style="font-size:.85rem;"><?= $_lang==='th' ? 'กลุ่มเป้าหมาย' : 'Target Group' ?></label>
          <select name="scope" class="form-select" required>
            <option value="accepted_authors"><?= $_lang==='th' ? 'ผู้แต่งที่ผ่านการยอมรับ' : 'Accepted Paper Authors' ?></option>
            <option value="all_authors"><?= $_lang==='th' ? 'ผู้แต่งทุกคน' : 'All Submitting Authors' ?></option>
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn-primary-custom w-100"
                  data-confirm="<?= $_lang==='th' ? 'ยืนยันการสร้างใบรับรองจำนวนมาก?' : 'Confirm bulk certificate generation?' ?>">
            <i class="fas fa-magic me-2"></i><?= $_lang==='th' ? 'สร้างใบรับรอง' : 'Generate' ?>
          </button>
        </div>
      </form>
    </div>

    <!-- Filter -->
    <form method="GET" class="d-flex gap-2 mb-4 flex-wrap align-items-end">
      <input type="text" name="q" class="form-control" style="max-width:200px;" placeholder="<?= $_lang==='th'?'ค้นหาชื่อ...':'Search name...' ?>" value="<?= e($search) ?>">
      <select name="type" class="form-select" style="max-width:180px;">
        <option value=""><?= $_lang==='th'?'ทุกประเภท':'All Types' ?></option>
        <?php foreach ($certTypeLabels as $code => $lbl): ?>
          <option value="<?= $code ?>" <?= $typeFilter===$code?'selected':'' ?>><?= $_lang==='th'?$lbl['th']:$lbl['en'] ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary-custom" style="font-size:.85rem;"><i class="fas fa-search"></i></button>
      <a href="?" class="btn btn-outline-secondary rounded-pill"><?= $_lang==='th'?'ล้าง':'Clear' ?></a>
    </form>

    <div class="table-card">
      <?php if (empty($certs)): ?>
        <div class="p-5 text-center"><h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= $_lang==='th'?'ผู้รับ':'Recipient' ?></th>
                <th><?= $_lang==='th'?'ประเภท':'Type' ?></th>
                <th><?= $_lang==='th'?'บทความ':'Paper' ?></th>
                <th><?= $_lang==='th'?'วันที่ออก':'Issued' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($certs as $c):
                $typeInfo = $certTypeLabels[$c['cert_type']] ?? ['th'=>$c['cert_type'],'en'=>$c['cert_type']];
              ?>
                <tr>
                  <td>
                    <div style="font-weight:600;font-size:.88rem;"><?= e($c['user_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--gray-500);"><?= e($c['user_email']) ?></div>
                  </td>
                  <td>
                    <span class="badge" style="background:var(--blue-dark);color:var(--gold);font-size:.75rem;">
                      <?= $_lang==='th' ? $typeInfo['th'] : $typeInfo['en'] ?>
                    </span>
                  </td>
                  <td style="font-size:.82rem;">
                    <?php if ($c['paper_code']): ?>
                      <code style="color:var(--blue-mid);font-size:.78rem;"><?= e($c['paper_code']) ?></code>
                    <?php else: ?>
                      <span style="color:var(--gray-400);">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;"><?= humanDate($c['issued_at'], $_lang) ?></td>
                  <td>
                    <a href="<?= $appUrl ?>/download.php?cert_id=<?= (int)$c['id'] ?>"
                       class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;">
                      <i class="fas fa-download"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="p-3 d-flex justify-content-between align-items-center" style="border-top:1px solid var(--gray-200);">
            <span style="font-size:.85rem;color:var(--gray-500);"><?= t('common.page') ?> <?= $pg['page'] ?> <?= t('common.of') ?> <?= $pg['total_pages'] ?></span>
            <div class="d-flex gap-2">
              <?php if ($pg['has_prev']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']-1])) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
              <?php if ($pg['has_next']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$pg['page']+1])) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
