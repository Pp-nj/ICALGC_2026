<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Database;

$paperId = intGet('id');
$_lang   = lang();
$appUrl  = APP_URL;

if (!$paperId) redirect('/publication.php');

try {
    $db   = Database::getInstance();
    $stmt = $db->prepare("
        SELECT p.*, pub.doi, pub.published_at, pub.download_count, pub.view_count, pub.id AS pub_id,
               u.first_name, u.last_name, u.title AS u_title, u.affiliation, u.email,
               ct.name_th AS theme_th, ct.name_en AS theme_en
        FROM papers p
        JOIN publications pub ON pub.paper_id = p.id
        JOIN users u ON u.id = p.submitter_id
        JOIN conference_themes ct ON ct.id = p.theme_id
        WHERE p.id = :pid AND p.status_code = 'published'
        LIMIT 1
    ");
    $stmt->execute([':pid' => $paperId]);
    $paper = $stmt->fetch();
    if (!$paper) redirect('/publication.php');

    // Co-authors
    $coStmt = $db->prepare("SELECT * FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order");
    $coStmt->execute([':pid' => $paperId]);
    $coAuthors = $coStmt->fetchAll();

    // Track view
    $db->prepare("UPDATE publications SET view_count = view_count + 1 WHERE paper_id = :pid")->execute([':pid' => $paperId]);

} catch (\Throwable $e) {
    redirect('/publication.php');
}

$pageTitle = $_lang==='th' ? $paper['title_th'] : $paper['title_en'];
$activeNav = 'pub';
require_once __DIR__ . '/../app/helpers/header.php';

$allAuthors = [$paper['u_title'] . ' ' . $paper['first_name'] . ' ' . $paper['last_name']];
foreach ($coAuthors as $co) $allAuthors[] = $co['full_name'];
?>

<!-- Breadcrumb -->
<div style="background:var(--gray-100);padding:14px 0;border-bottom:1px solid var(--gray-200);">
  <div class="container" style="font-size:.85rem;color:var(--gray-500);">
    <a href="<?= $appUrl ?>" style="color:var(--blue-mid);"><?= t('nav.home') ?></a>
    <span class="mx-2">/</span>
    <a href="<?= $appUrl ?>/publication.php" style="color:var(--blue-mid);"><?= t('pub.title') ?></a>
    <span class="mx-2">/</span>
    <span><?= e($paper['paper_code']) ?></span>
  </div>
</div>

<section class="page-section" style="padding:50px 0;">
  <div class="container">
    <div class="row g-5">

      <!-- Main -->
      <div class="col-lg-8">
        <div class="content-card">
          <!-- Theme -->
          <span class="keyword-tag" style="background:var(--blue-dark);color:var(--gold);margin-bottom:16px;display:inline-block;">
            <?= e($_lang==='th'?$paper['theme_th']:$paper['theme_en']) ?>
          </span>

          <!-- Title -->
          <h1 style="font-size:1.5rem;font-weight:800;color:var(--blue-dark);line-height:1.4;margin-bottom:8px;">
            <?= e($_lang==='th' ? $paper['title_th'] : $paper['title_en']) ?>
          </h1>
          <?php if ($_lang==='th' && $paper['title_en']): ?>
            <p style="font-size:1rem;color:var(--gray-500);font-style:italic;margin-bottom:16px;"><?= e($paper['title_en']) ?></p>
          <?php elseif ($_lang==='en' && $paper['title_th']): ?>
            <p style="font-size:1rem;color:var(--gray-500);margin-bottom:16px;"><?= e($paper['title_th']) ?></p>
          <?php endif; ?>

          <!-- Authors -->
          <div class="mb-4 p-3 rounded" style="background:var(--gray-100);">
            <div style="font-size:.82rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
              <i class="fas fa-users me-1"></i><?= t('pub.authors') ?>
            </div>
            <div style="font-size:.95rem;color:var(--blue-dark);font-weight:600;">
              <?= e(implode('; ', $allAuthors)) ?>
            </div>
            <?php if ($paper['affiliation']): ?>
              <div style="font-size:.85rem;color:var(--gray-500);margin-top:4px;"><?= e($paper['affiliation']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Keywords -->
          <div class="mb-4">
            <div style="font-size:.82rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">
              <i class="fas fa-tags me-1"></i><?= t('pub.keywords') ?>
            </div>
            <div class="pub-card-keywords">
              <?php foreach (array_map('trim', explode(',', $paper['keywords'])) as $kw): ?>
                <a href="<?= $appUrl ?>/publication.php?q=<?= urlencode(trim($kw)) ?>" class="keyword-tag text-decoration-none">
                  <?= e(trim($kw)) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Download -->
          <div class="d-flex gap-3 flex-wrap mt-4 pt-4" style="border-top:1px solid var(--gray-200);">
            <a href="<?= $appUrl ?>/download.php?paper_id=<?= (int)$paper['id'] ?>&type=latest"
               class="btn-primary-custom d-flex align-items-center gap-2">
              <i class="fas fa-file-pdf"></i><?= t('pub.download') ?>
            </a>
            <a href="<?= $appUrl ?>/publication.php" class="btn-outline-custom d-flex align-items-center gap-2">
              <i class="fas fa-arrow-left"></i><?= t('common.back') ?>
            </a>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="content-card">
          <div class="content-card-title"><i class="fas fa-info-circle me-2" style="color:var(--gold);"></i>Details</div>
          <table style="width:100%;font-size:.88rem;">
            <tr style="border-bottom:1px solid var(--gray-200);">
              <td style="padding:10px 0;color:var(--gray-500);font-weight:600;width:40%;"><?= t('paper.code') ?></td>
              <td style="padding:10px 0;"><?= e($paper['paper_code']) ?></td>
            </tr>
            <tr style="border-bottom:1px solid var(--gray-200);">
              <td style="padding:10px 0;color:var(--gray-500);font-weight:600;"><?= t('pub.published_date') ?></td>
              <td style="padding:10px 0;"><?= humanDate($paper['published_at']) ?></td>
            </tr>
            <?php if ($paper['doi']): ?>
              <tr style="border-bottom:1px solid var(--gray-200);">
                <td style="padding:10px 0;color:var(--gray-500);font-weight:600;"><?= t('pub.doi') ?></td>
                <td style="padding:10px 0;word-break:break-all;font-size:.8rem;"><?= e($paper['doi']) ?></td>
              </tr>
            <?php endif; ?>
            <tr style="border-bottom:1px solid var(--gray-200);">
              <td style="padding:10px 0;color:var(--gray-500);font-weight:600;"><?= $_lang==='th'?'ดาวน์โหลด':'Downloads' ?></td>
              <td style="padding:10px 0;"><?= (int)$paper['download_count'] ?></td>
            </tr>
            <tr>
              <td style="padding:10px 0;color:var(--gray-500);font-weight:600;"><?= $_lang==='th'?'การเข้าชม':'Views' ?></td>
              <td style="padding:10px 0;"><?= (int)$paper['view_count'] + 1 ?></td>
            </tr>
          </table>
        </div>

        <!-- Co-authors card -->
        <?php if ($coAuthors): ?>
          <div class="content-card">
            <div class="content-card-title"><i class="fas fa-users me-2" style="color:var(--gold);"></i><?= t('pub.authors') ?></div>
            <?php foreach ($coAuthors as $co): ?>
              <div class="d-flex gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--gray-200);">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:36px;height:36px;background:var(--blue-light);color:var(--blue-dark);font-weight:700;font-size:.85rem;">
                  <?= strtoupper(substr($co['full_name'], 0, 1)) ?>
                </div>
                <div>
                  <div style="font-weight:700;font-size:.9rem;color:var(--blue-dark);"><?= e($co['full_name']) ?></div>
                  <?php if ($co['institution']): ?>
                    <div style="font-size:.8rem;color:var(--gray-500);"><?= e($co['institution']) ?></div>
                  <?php endif; ?>
                  <?php if ($co['is_corresponding']): ?>
                    <span class="status-badge" style="background:var(--gold);color:var(--blue-dark);font-size:.7rem;">✉ Corresponding</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
