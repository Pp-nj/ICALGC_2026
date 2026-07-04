<?php
require_once __DIR__ . '/../../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notification;
use App\Core\Mail;

Auth::require('admin');
$_lang  = lang();
$appUrl = APP_URL;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'unpublish') {
    Auth::verifyCsrf(post('csrf_token'));
    $paperId = intPost('paper_id');
    if ($paperId) {
        try {
            $db = Database::getInstance();
            $pStmt = $db->prepare("SELECT * FROM papers WHERE id = :id AND status_code = 'published'");
            $pStmt->execute([':id' => $paperId]);
            $paper = $pStmt->fetch();
            if ($paper) {
                $db->beginTransaction();
                $db->prepare("UPDATE papers SET status_code = 'accepted', updated_at = NOW() WHERE id = :id")
                   ->execute([':id' => $paperId]);
                $db->prepare("DELETE FROM publications WHERE paper_id = :pid")
                   ->execute([':pid' => $paperId]);
                Notification::paperUnpublished((int)$paper['submitter_id'], $paper['paper_code'], (int)$paperId);
                auditLog('unpublish_paper', 'papers', "Unpublished paper {$paper['paper_code']}", Auth::id());
                $db->commit();
                flashSet('success', $_lang==='th' ? 'ยกเลิกการเผยแพร่บทความเรียบร้อย' : 'Paper unpublished successfully.');
            } else {
                flashSet('error', $_lang==='th' ? 'ไม่พบบทความหรือสถานะไม่ถูกต้อง' : 'Paper not found or not published.');
            }
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log($e->getMessage());
            flashSet('error', $_lang==='th' ? 'เกิดข้อผิดพลาด' : 'An error occurred.');
        }
    }
    redirect($appUrl . '/admin/publications.php?tab=published');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(post('csrf_token'));

    $paperId = intPost('paper_id');
    $doi     = trim(post('doi'));
    $note    = trim(post('note'));

    if (!$paperId) $errors[] = $_lang==='th' ? 'ไม่พบบทความ' : 'Paper not found.';

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            $pStmt = $db->prepare("SELECT * FROM papers WHERE id = :id AND status_code = 'accepted'");
            $pStmt->execute([':id' => $paperId]);
            $paper = $pStmt->fetch();

            if (!$paper) {
                $errors[] = $_lang==='th' ? 'บทความไม่ได้อยู่ในสถานะยอมรับ' : 'Paper is not in accepted status.';
            } else {
                $db->beginTransaction();

                // Update paper status
                $db->prepare("UPDATE papers SET status_code = 'published', updated_at = NOW() WHERE id = :id")
                   ->execute([':id' => $paperId]);

                // Get latest file
                $fStmt = $db->prepare("SELECT * FROM paper_files WHERE paper_id = :pid ORDER BY uploaded_at DESC LIMIT 1");
                $fStmt->execute([':pid' => $paperId]);
                $file = $fStmt->fetch();

                // Create publication record
                $ins = $db->prepare("
                    INSERT INTO publications (paper_id, doi, published_by, published_at)
                    VALUES (:pid, :doi, :by, NOW())
                    ON CONFLICT (paper_id) DO UPDATE SET doi = EXCLUDED.doi, published_at = NOW()
                ");
                $ins->execute([':pid' => $paperId, ':doi' => $doi ?: null, ':by' => Auth::id()]);

                // Notify author
                $submitterStmt = $db->prepare("SELECT * FROM users WHERE id = :uid");
                $submitterStmt->execute([':uid' => $paper['submitter_id']]);
                $submitter = $submitterStmt->fetch();

                Notification::paperPublished((int)$paper['submitter_id'], $paper['paper_code'], (int)$paperId);

                auditLog('publish_paper', 'papers', "Published paper {$paper['paper_code']}", Auth::id());
                $db->commit();

                try {
                    Mail::sendPublished($submitter['email'], $submitter['first_name'] . ' ' . $submitter['last_name'], $paper['paper_code'], $paper['title_en']);
                } catch (\Throwable $mailErr) {
                    error_log('Mail::sendPublished failed: ' . $mailErr->getMessage());
                }

                flashSet('success', $_lang==='th' ? 'เผยแพร่บทความเรียบร้อย' : 'Paper published successfully.');
                redirect($appUrl . '/admin/publications.php');
            }
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log($e->getMessage());
            $errors[] = 'DEBUG: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
        }
    }
}

$page    = max(1, intGet('page', 1));
$perPage = 15;
$tab     = get('tab', 'accepted');

try {
    $db = Database::getInstance();

    $statusCode = $tab === 'published' ? 'published' : 'accepted';
    $cntStmt    = $db->prepare("SELECT COUNT(*) FROM papers WHERE status_code = :sc");
    $cntStmt->execute([':sc' => $statusCode]);
    $total = (int)$cntStmt->fetchColumn();

    $pg = paginate($total, $perPage, $page);

    $stmt = $db->prepare("
        SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS submitter_name,
               pub.id AS pub_id, pub.doi, pub.published_at, pub.download_count, pub.view_count
        FROM papers p
        JOIN users u ON u.id = p.submitter_id
        LEFT JOIN publications pub ON pub.paper_id = p.id
        WHERE p.status_code = :sc
        ORDER BY p.submitted_at DESC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':sc', $statusCode);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $pg['offset'], \PDO::PARAM_INT);
    $stmt->execute();
    $papers = $stmt->fetchAll();

    $acceptedCount  = (int)$db->query("SELECT COUNT(*) FROM papers WHERE status_code = 'accepted'")->fetchColumn();
    $publishedCount = (int)$db->query("SELECT COUNT(*) FROM papers WHERE status_code = 'published'")->fetchColumn();

} catch (\Throwable $e) {
    error_log($e->getMessage());
    $papers = []; $total = 0; $pg = paginate(0,$perPage,1);
    $acceptedCount = 0; $publishedCount = 0;
}

$pageTitle  = $_lang==='th' ? 'เผยแพร่บทความ' : 'Publish Papers';
$activeMenu = 'publications';
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
    <div class="dash-header">
      <h1 class="dash-title"><i class="fas fa-globe me-2" style="color:var(--gold);"></i><?= e($pageTitle) ?></h1>
    </div>

    <?= flashHtml() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="d-flex gap-2 mb-4">
      <a href="?tab=accepted" class="btn btn-sm rounded-pill fw-bold <?= $tab==='accepted'?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= $tab==='accepted'?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <?= $_lang==='th' ? 'รอเผยแพร่' : 'Ready to Publish' ?> (<?= $acceptedCount ?>)
      </a>
      <a href="?tab=published" class="btn btn-sm rounded-pill fw-bold <?= $tab==='published'?'btn-warning':'btn-outline-secondary' ?>"
         style="<?= $tab==='published'?'background:var(--gold);color:var(--blue-dark);border-color:var(--gold);':'' ?>">
        <?= $_lang==='th' ? 'เผยแพร่แล้ว' : 'Published' ?> (<?= $publishedCount ?>)
      </a>
    </div>

    <div class="table-card">
      <?php if (empty($papers)): ?>
        <div class="p-5 text-center">
          <i class="fas fa-globe fa-3x mb-3" style="color:var(--gray-200);"></i>
          <h5 style="color:var(--gray-500);">
            <?= $tab==='accepted' ? ($_lang==='th'?'ไม่มีบทความที่รอเผยแพร่':'No papers ready to publish') : ($_lang==='th'?'ยังไม่มีบทความที่เผยแพร่':'No published papers yet') ?>
          </h5>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th><?= t('paper.code') ?></th>
                <th><?= $_lang==='th' ? 'บทความ' : 'Paper' ?></th>
                <th><?= $_lang==='th' ? 'ผู้แต่ง' : 'Author' ?></th>
                <?php if ($tab === 'published'): ?>
                  <th>DOI</th>
                  <th><?= $_lang==='th' ? 'วันเผยแพร่' : 'Published' ?></th>
                  <th><?= $_lang==='th' ? 'ดาวน์โหลด' : 'Downloads' ?></th>
                <?php else: ?>
                  <th><?= $_lang==='th' ? 'วันที่ยอมรับ' : 'Accepted Date' ?></th>
                <?php endif; ?>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($papers as $p): ?>
                <tr>
                  <td><code style="font-size:.78rem;color:var(--blue-mid);"><?= e($p['paper_code']) ?></code></td>
                  <td style="max-width:200px;">
                    <div style="font-weight:600;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= e($_lang==='th'?$p['title_th']:$p['title_en']) ?>
                    </div>
                  </td>
                  <td style="font-size:.82rem;"><?= e($p['submitter_name']) ?></td>
                  <?php if ($tab === 'published'): ?>
                    <td style="font-size:.8rem;"><?= e($p['doi'] ?? '—') ?></td>
                    <td style="font-size:.78rem;"><?= humanDate($p['published_at'], $_lang) ?></td>
                    <td style="font-weight:700;font-size:.88rem;color:var(--blue-dark);"><?= number_format((int)$p['download_count']) ?></td>
                  <?php else: ?>
                    <td style="font-size:.78rem;"><?= humanDate($p['updated_at'], $_lang) ?></td>
                  <?php endif; ?>
                  <td>
                    <?php if ($tab === 'accepted'): ?>
                      <button type="button" class="btn btn-sm btn-success rounded-pill" style="font-size:.72rem;"
                              onclick="openPublishModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['paper_code'])) ?>')">
                        <i class="fas fa-globe me-1"></i><?= $_lang==='th' ? 'เผยแพร่' : 'Publish' ?>
                      </button>
                    <?php else: ?>
                      <div class="d-flex gap-1 flex-wrap">
                        <a href="<?= $appUrl ?>/publication-detail.php?id=<?= (int)$p['pub_id'] ?>"
                           class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.72rem;" target="_blank">
                          <i class="fas fa-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;"
                                onclick="openUnpublishModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['paper_code'])) ?>')">
                          <i class="fas fa-ban me-1"></i><?= $_lang==='th' ? 'ยกเลิก' : 'Unpublish' ?>
                        </button>
                      </div>
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

    <!-- Unpublish Modal -->
    <div class="modal fade" id="unpublishModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header" style="background:#dc3545;color:#fff;">
            <h5 class="modal-title"><i class="fas fa-ban me-2"></i><?= $_lang==='th' ? 'ยกเลิกการเผยแพร่' : 'Unpublish Paper' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="unpublish">
            <input type="hidden" name="paper_id" id="unpublishPaperId">
            <div class="modal-body">
              <p><strong id="unpublishPaperCode"></strong></p>
              <p style="font-size:.88rem;color:var(--gray-600);">
                <?= $_lang==='th'
                  ? 'บทความนี้จะถูกถอดออกจากหน้าสาธารณะและสถานะจะเปลี่ยนกลับเป็น "ยอมรับแล้ว" ผู้แต่งจะได้รับการแจ้งเตือน'
                  : 'This paper will be removed from the public page and its status reverted to "Accepted". The author will be notified.' ?>
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
              <button type="submit" class="btn btn-danger"><i class="fas fa-ban me-2"></i><?= $_lang==='th'?'ยืนยันยกเลิกการเผยแพร่':'Confirm Unpublish' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Publish Modal -->
    <div class="modal fade" id="publishModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header" style="background:var(--blue-dark);color:#fff;">
            <h5 class="modal-title"><i class="fas fa-globe me-2"></i><?= $_lang==='th' ? 'เผยแพร่บทความ' : 'Publish Paper' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="paper_id" id="modalPaperId">
            <div class="modal-body">
              <p><strong id="modalPaperCode"></strong></p>
              <p style="font-size:.88rem;color:var(--gray-600);">
                <?= $_lang==='th'
                  ? 'บทความนี้จะแสดงในหน้า Publication สาธารณะ ผู้แต่งจะได้รับการแจ้งเตือน'
                  : 'This paper will be visible on the public Publication page. The author will be notified.' ?>
              </p>
              <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.85rem;">DOI (<?= $_lang==='th'?'ถ้ามี':'optional' ?>)</label>
                <input type="text" name="doi" class="form-control" placeholder="10.xxxxx/xxxxx">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= $_lang==='th'?'ยกเลิก':'Cancel' ?></button>
              <button type="submit" class="btn-primary-custom"><i class="fas fa-globe me-2"></i><?= $_lang==='th'?'เผยแพร่':'Publish' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $appUrl ?>/assets/js/main.js"></script>
<script>
function openPublishModal(paperId, paperCode) {
  document.getElementById('modalPaperId').value = paperId;
  document.getElementById('modalPaperCode').textContent = paperCode;
  new bootstrap.Modal(document.getElementById('publishModal')).show();
}
function openUnpublishModal(paperId, paperCode) {
  document.getElementById('unpublishPaperId').value = paperId;
  document.getElementById('unpublishPaperCode').textContent = paperCode;
  new bootstrap.Modal(document.getElementById('unpublishModal')).show();
}
</script>
</body>
</html>
