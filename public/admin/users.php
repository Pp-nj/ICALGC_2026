<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

// Handle suspend/activate/reset-password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));
    $userId = intPost('user_id');
    $action = post('action');
    if ($userId && in_array($action, ['suspend', 'activate', 'reset_password'])) {
        try {
            $db = Database::getInstance();
            if ($action === 'reset_password') {
                $newPwd = post('new_password');
                if (strlen($newPwd) < 8) {
                    flashSet('danger', $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.');
                } else {
                    $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
                    $db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :uid")
                       ->execute([':hash' => $hash, ':uid' => $userId]);
                    auditLog('reset_password', 'users', "Admin reset password for user $userId");
                    flashSet('success', $_lang==='th' ? 'รีเซ็ตรหัสผ่านเรียบร้อย' : 'Password reset successfully.');
                }
            } else {
                $val = $action === 'suspend' ? 'suspended' : 'active';
                $db->prepare("UPDATE users SET account_status = :v WHERE id = :uid AND id != :me")
                   ->execute([':v' => $val, ':uid' => $userId, ':me' => Auth::id()]);
                flashSet('success', $action === 'suspend'
                    ? ($_lang==='th' ? 'ระงับผู้ใช้เรียบร้อย' : 'User suspended.')
                    : ($_lang==='th' ? 'เปิดใช้งานผู้ใช้เรียบร้อย' : 'User activated.'));
            }
        } catch (\Throwable $e) { error_log($e->getMessage()); }
    }
    redirect($appUrl . '/admin/users.php');
}

$search     = sanitize(get('q'));
$roleFilter = sanitize(get('role'));
$page       = max(1, intGet('page', 1));
$perPage    = 20;

$where  = ['1=1'];
$params = [];
if ($roleFilter) { $where[] = "u.role = :role"; $params[':role'] = $roleFilter; }

try {
    $db = Database::getInstance();
    $isMysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
    if ($search) {
        $where[] = $isMysql
            ? "(CONCAT(u.first_name, ' ', u.last_name) LIKE :q OR u.email LIKE :q OR u.affiliation LIKE :q)"
            : "((u.first_name || ' ' || u.last_name) ILIKE :q OR u.email ILIKE :q OR u.affiliation ILIKE :q)";
        $params[':q'] = "%$search%";
    }
    $whereStr = implode(' AND ', $where);
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $whereStr");
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();
    $pg    = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT u.*,
               COUNT(DISTINCT p.id) AS paper_count
        FROM users u
        LEFT JOIN papers p ON p.submitter_id = u.id
        WHERE $whereStr
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT :lim OFFSET :off
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $users = []; $total = 0; $pg = paginate(0,$perPage,1);
}

$pageTitle  = $_lang==='th' ? 'จัดการผู้ใช้' : 'Manage Users';
$activeMenu = 'users';
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
        <h1 class="dash-title"><i class="fas fa-users me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
        <p class="dash-breadcrumb"><?= $total ?> <?= $_lang==='th' ? 'ผู้ใช้' : 'user(s)' ?></p>
      </div>
      <a href="<?= $appUrl ?>/admin/reviewers.php" class="btn-outline-custom">
        <i class="fas fa-user-plus me-2"></i><?= $_lang==='th' ? 'เพิ่มผู้ทรงคุณวุฒิ' : 'Add Reviewer' ?>
      </a>
    </div>

    <?= flashHtml() ?>

    <!-- Filters -->
    <form method="GET" class="content-card mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-5">
          <input type="text" name="q" class="form-control" placeholder="<?= $_lang==='th' ? 'ชื่อ, อีเมล, สังกัด...' : 'Name, email, affiliation...' ?>" value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
          <select name="role" class="form-select">
            <option value=""><?= $_lang==='th' ? 'ทุกบทบาท' : 'All Roles' ?></option>
            <option value="author" <?= $roleFilter==='author'?'selected':'' ?>><?= $_lang==='th' ? 'ผู้แต่ง' : 'Author' ?></option>
            <option value="reviewer" <?= $roleFilter==='reviewer'?'selected':'' ?>><?= $_lang==='th' ? 'ผู้ทรงคุณวุฒิ' : 'Reviewer' ?></option>
            <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn-primary-custom w-100" style="font-size:.85rem;"><i class="fas fa-search"></i></button>
        </div>
        <div class="col-md-2">
          <a href="?" class="btn btn-outline-secondary w-100 rounded-pill"><?= $_lang==='th' ? 'ล้าง' : 'Clear' ?></a>
        </div>
      </div>
    </form>

    <div class="table-card">
      <?php if (empty($users)): ?>
        <div class="p-5 text-center"><i class="fas fa-search fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);"><?= t('common.no_data') ?></h5></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= $_lang==='th' ? 'ชื่อ' : 'Name' ?></th>
                <th><?= $_lang==='th' ? 'อีเมล' : 'Email' ?></th>
                <th><?= $_lang==='th' ? 'สังกัด' : 'Affiliation' ?></th>
                <th><?= $_lang==='th' ? 'บทบาท' : 'Role' ?></th>
                <th><?= $_lang==='th' ? 'บทความ' : 'Papers' ?></th>
                <th><?= $_lang==='th' ? 'สถานะ' : 'Status' ?></th>
                <th><?= $_lang==='th' ? 'สมัคร' : 'Registered' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u):
                $roleColor = ['author'=>'#0057b7','reviewer'=>'#6f42c1','admin'=>'#dc3545'][$u['role']] ?? '#6c757d';
              ?>
                <tr>
                  <td style="font-weight:600;font-size:.88rem;">
                    <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                    <?php if (!$u['email_verified']): ?>
                      <i class="fas fa-exclamation-circle ms-1" style="color:#fd7e14;font-size:.72rem;" title="Unverified email"></i>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;"><?= e($u['email']) ?></td>
                  <td style="font-size:.8rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($u['affiliation'] ?? '—') ?></td>
                  <td>
                    <span class="badge rounded-pill" style="background:<?= $roleColor ?>;color:#fff;font-size:.72rem;">
                      <?= ucfirst($u['role']) ?>
                    </span>
                  </td>
                  <td style="text-align:center;font-weight:700;font-size:.88rem;color:var(--blue-dark);"><?= (int)$u['paper_count'] ?></td>
                  <td>
                    <?php if ($u['account_status'] === 'suspended'): ?>
                      <span class="badge" style="background:#dc3545;color:#fff;font-size:.72rem;"><?= $_lang==='th'?'ระงับ':'Suspended' ?></span>
                    <?php else: ?>
                      <span class="badge" style="background:#198754;color:#fff;font-size:.72rem;"><?= $_lang==='th'?'ใช้งาน':'Active' ?></span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.78rem;"><?= humanDate($u['created_at'], $_lang) ?></td>
                  <td class="d-flex gap-1 flex-wrap">
                    <a href="<?= $appUrl ?>/admin/user-detail.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;"
                       title="<?= $_lang==='th'?'ดู/แก้ไขข้อมูล':'View/Edit Profile' ?>">
                      <i class="fas fa-pen"></i>
                    </a>
                    <?php if ($u['id'] !== Auth::id()): ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <?php if ($u['account_status'] === 'suspended'): ?>
                          <input type="hidden" name="action" value="activate">
                          <button type="submit" class="btn btn-sm btn-outline-success rounded-pill" style="font-size:.72rem;"
                                  data-confirm="<?= $_lang==='th'?'ยืนยันการเปิดใช้งาน?':'Confirm activate user?' ?>">
                            <i class="fas fa-check"></i>
                          </button>
                        <?php else: ?>
                          <input type="hidden" name="action" value="suspend">
                          <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                                  data-confirm="<?= $_lang==='th'?'ยืนยันการระงับผู้ใช้?':'Confirm suspend user?' ?>">
                            <i class="fas fa-ban"></i>
                          </button>
                        <?php endif; ?>
                      </form>
                      <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" style="font-size:.72rem;"
                              onclick="openResetPwd(<?= (int)$u['id'] ?>, '<?= e($u['first_name'].' '.$u['last_name']) ?>')"
                              title="<?= $_lang==='th'?'ตั้งค่ารหัสผ่านใหม่':'Reset Password' ?>">
                        <i class="fas fa-key"></i>
                      </button>
                    <?php endif; ?>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwdModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-size:.95rem;">
          <i class="fas fa-key me-2" style="color:var(--gold);"></i>
          <?= $_lang==='th'?'ตั้งค่ารหัสผ่านใหม่':'Reset Password' ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="user_id" id="resetPwdUserId">
          <p class="mb-3" style="font-size:.85rem;color:var(--gray-700);">
            <?= $_lang==='th'?'ผู้ใช้:':'User:' ?> <strong id="resetPwdUserName"></strong>
          </p>
          <label class="form-label fw-semibold" style="font-size:.85rem;">
            <?= $_lang==='th'?'รหัสผ่านใหม่':'New Password' ?> <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" name="new_password" id="resetPwdInput" class="form-control"
                   placeholder="<?= $_lang==='th'?'อย่างน้อย 8 ตัวอักษร':'Min 8 characters' ?>"
                   minlength="8" required>
            <button class="input-group-text" type="button"
                    onclick="var i=document.getElementById('resetPwdInput');i.type=i.type==='password'?'text':'password';"
                    style="cursor:pointer;"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold">
            <i class="fas fa-save me-1"></i><?= $_lang==='th'?'บันทึก':'Save' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
function openResetPwd(userId, userName) {
  document.getElementById('resetPwdUserId').value = userId;
  document.getElementById('resetPwdUserName').textContent = userName;
  document.getElementById('resetPwdInput').value = '';
  new bootstrap.Modal(document.getElementById('resetPwdModal')).show();
}
</script>
</body>
</html>
