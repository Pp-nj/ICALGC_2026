<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Database;

$pageTitle = lang()==='th' ? 'คลังสิ่งพิมพ์' : 'Publication Repository';
$activeNav = 'pub';
$_lang     = lang();
$appUrl    = APP_URL;

// Search & filters
$search    = sanitize(get('q'));
$themeId   = intGet('theme_id');
$yearFilter= intGet('year');
$page      = max(1, intGet('page', 1));
$perPage   = 9;

// Build query
$where  = ["p.status_code = 'published'"];
$params = [];

try {
    $db = Database::getInstance();
    $isMysql = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';

    if ($search) {
        $where[] = $isMysql
            ? "(p.title_en LIKE :q OR p.title_th LIKE :q OR p.abstract_en LIKE :q OR p.keywords LIKE :q OR u.first_name LIKE :q OR u.last_name LIKE :q)"
            : "(p.title_en ILIKE :q OR p.title_th ILIKE :q OR p.abstract_en ILIKE :q OR p.keywords ILIKE :q OR u.first_name ILIKE :q OR u.last_name ILIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }
    if ($themeId) {
        $where[]         = "p.theme_id = :theme_id";
        $params[':theme_id'] = $themeId;
    }
    if ($yearFilter) {
        $where[]          = "EXTRACT(YEAR FROM pub.published_at) = :yr";
        $params[':yr']    = $yearFilter;
    }

    $whereStr = implode(' AND ', $where);

    // Total count
    $cntSql  = "SELECT COUNT(*) FROM papers p
                JOIN publications pub ON pub.paper_id = p.id
                JOIN users u ON u.id = p.submitter_id
                JOIN conference_themes ct ON ct.id = p.theme_id
                WHERE {$whereStr}";
    $cntStmt = $db->prepare($cntSql);
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    $pg     = paginate($total, $perPage, $page);
    $offset = $pg['offset'];

    // Fetch papers
    $sql = "SELECT p.*, pub.doi, pub.published_at, pub.download_count, pub.view_count, pub.id AS pub_id,
                   u.first_name, u.last_name, u.title AS u_title,
                   ct.name_th AS theme_th, ct.name_en AS theme_en
            FROM papers p
            JOIN publications pub ON pub.paper_id = p.id
            JOIN users u ON u.id = p.submitter_id
            JOIN conference_themes ct ON ct.id = p.theme_id
            WHERE {$whereStr}
            ORDER BY pub.published_at DESC
            LIMIT :lim OFFSET :off";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  \PDO::PARAM_INT);
    $stmt->execute();
    $papers = $stmt->fetchAll();

    // Fetch co-authors for each paper
    foreach ($papers as &$paper) {
        $coStmt = $db->prepare("SELECT full_name FROM paper_co_authors WHERE paper_id = :pid ORDER BY sort_order");
        $coStmt->execute([':pid' => $paper['id']]);
        $paper['co_authors'] = $coStmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    unset($paper);

    // Themes for filter
    $themes = $db->query("SELECT * FROM conference_themes WHERE is_active = TRUE ORDER BY code")->fetchAll();

    // Years for filter
    $yearsStmt = $db->query("SELECT DISTINCT EXTRACT(YEAR FROM pub.published_at)::int AS yr FROM publications pub ORDER BY yr DESC");
    $years = $yearsStmt->fetchAll(\PDO::FETCH_COLUMN);

    // Track view count (lightweight)
    // (Implemented in detail page)

} catch (\Throwable $e) {
    $papers = []; $total = 0; $pg = paginate(0, $perPage, 1); $themes = []; $years = [];
    error_log($e->getMessage());
}

require_once __DIR__ . '/../app/helpers/header.php';
?>

<!-- Search Banner -->
<section class="pub-search-section">
  <div class="container">
    <div class="section-header" style="margin-bottom:32px;">
      <span class="section-label" style="background:rgba(255,255,255,.15);color:var(--gold-light);" ><?= t('pub.label') ?></span>
      <h1 class="section-title" style="color:var(--white);"> <?= t('pub.title') ?></h1>
      <div class="section-divider"></div>
    </div>

    <form method="GET" action="" class="row g-3">
      <div class="col-lg-6">
        <div class="input-group" style="border-radius:8px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.2);">
          <span class="input-group-text" style="background:var(--white);border:none;">
            <i class="fas fa-search" style="color:var(--blue-dark);"></i>
          </span>
          <input type="text" name="q" class="form-control" style="border:none;font-size:.95rem;padding:14px;"
                 value="<?= e($search) ?>"
                 placeholder="<?= t('pub.search_placeholder') ?>">
          <button type="submit" class="btn" style="background:var(--gold);color:var(--blue-dark);font-weight:700;border:none;padding:0 24px;">
            <?= t('pub.search') ?>
          </button>
        </div>
      </div>
      <div class="col-lg-3">
        <select name="theme_id" class="form-select" style="border:none;border-radius:8px;padding:14px;box-shadow:0 4px 20px rgba(0,0,0,.2);" onchange="this.form.submit()">
          <option value=""><?= t('pub.all_themes') ?></option>
          <?php foreach ($themes as $th): ?>
            <option value="<?= (int)$th['id'] ?>" <?= $themeId===$th['id']?'selected':'' ?>>
              <?= e($_lang==='th'?$th['name_th']:$th['name_en']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-2">
        <select name="year" class="form-select" style="border:none;border-radius:8px;padding:14px;box-shadow:0 4px 20px rgba(0,0,0,.2);" onchange="this.form.submit()">
          <option value=""><?= t('pub.all_years') ?></option>
          <?php foreach ($years as $yr): ?>
            <option value="<?= (int)$yr ?>" <?= $yearFilter===$yr?'selected':'' ?>><?= (int)$yr ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-1">
        <a href="<?= $appUrl ?>/publication.php" class="btn-outline-custom d-block text-center" style="padding:13px;">
          <i class="fas fa-times"></i>
        </a>
      </div>
    </form>

    <?php if ($search || $themeId || $yearFilter): ?>
      <p class="mt-3 mb-0" style="color:rgba(255,255,255,.8);font-size:.9rem;">
        <i class="fas fa-info-circle me-1"></i>
        <?= $_lang==='th' ? "พบ {$total} บทคัดย่อ" : "Found {$total} publication(s)" ?>
        <?= $search ? " " . ($_lang==='th'?'สำหรับ':'for') . " \"" . e($search) . "\"" : '' ?>
      </p>
    <?php endif; ?>
  </div>
</section>

<!-- Results -->
<section class="page-section" style="background:var(--gray-100);padding:50px 0;">
  <div class="container">

    <?php if (empty($papers)): ?>
      <div class="text-center py-5">
        <i class="fas fa-search fa-3x mb-3" style="color:var(--gray-500);"></i>
        <h4 style="color:var(--gray-700);"><?= t('pub.no_results') ?></h4>
        <?php if ($search || $themeId || $yearFilter): ?>
          <a href="<?= $appUrl ?>/publication.php" class="btn-outline-custom mt-3">
            <i class="fas fa-refresh me-2"></i><?= t('common.reset') ?>
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>

      <div class="d-flex align-items-center justify-content-between mb-4">
        <h5 style="color:var(--blue-dark);font-weight:700;margin:0;">
          <?= $_lang==='th' ? "แสดง {$total} บทคัดย่อ" : "{$total} Publication(s)" ?>
        </h5>
      </div>

      <div class="row g-4">
        <?php foreach ($papers as $paper): ?>
          <?php
          // All authors
          $allAuthors = [$paper['u_title'] . ' ' . $paper['first_name'] . ' ' . $paper['last_name']];
          foreach ($paper['co_authors'] as $co) $allAuthors[] = $co;
          $authorStr = implode(', ', $allAuthors);
          $keywords  = array_map('trim', explode(',', $paper['keywords']));
          ?>
          <div class="col-lg-4 col-md-6">
            <div class="pub-card">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="keyword-tag" style="background:var(--blue-dark);color:var(--gold);">
                  <?= e($_lang==='th'?$paper['theme_th']:$paper['theme_en']) ?>
                </span>
                <span style="font-size:.75rem;color:var(--gray-500);">
                  <i class="fas fa-download me-1"></i><?= (int)$paper['download_count'] ?>
                </span>
              </div>
              <h3 class="pub-card-title">
                <a href="<?= $appUrl ?>/publication-detail.php?id=<?= (int)$paper['id'] ?>" style="color:inherit;text-decoration:none;">
                  <?= e($_lang==='th' ? $paper['title_th'] : $paper['title_en']) ?>
                </a>
              </h3>
              <p class="pub-card-authors">
                <i class="fas fa-users me-1"></i><?= e($authorStr) ?>
              </p>
              <p class="pub-card-abstract">
                <?= e($_lang==='th' ? $paper['abstract_th'] : $paper['abstract_en']) ?>
              </p>
              <div class="pub-card-keywords">
                <?php foreach (array_slice($keywords, 0, 4) as $kw): ?>
                  <span class="keyword-tag"><?= e(trim($kw)) ?></span>
                <?php endforeach; ?>
              </div>
              <div class="d-flex gap-2 mt-auto">
                <a href="<?= $appUrl ?>/publication-detail.php?id=<?= (int)$paper['id'] ?>"
                   class="btn-outline-custom flex-fill text-center" style="padding:8px 12px;font-size:.82rem;">
                  <i class="fas fa-eye me-1"></i><?= t('pub.view_detail') ?>
                </a>
                <a href="<?= $appUrl ?>/download.php?paper_id=<?= (int)$paper['id'] ?>&type=latest"
                   class="btn-primary-custom flex-fill text-center" style="padding:8px 12px;font-size:.82rem;">
                  <i class="fas fa-download me-1"></i>PDF
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pg['total_pages'] > 1): ?>
        <nav class="mt-5 d-flex justify-content-center" aria-label="Publication pages">
          <ul class="pagination gap-1">
            <?php if ($pg['has_prev']): ?>
              <li class="page-item">
                <a class="page-link rounded" style="background:var(--blue-dark);color:var(--white);border:none;"
                   href="?<?= http_build_query(array_merge($_GET, ['page' => $pg['page'] - 1])) ?>">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
            <?php endif; ?>

            <?php for ($i = max(1, $pg['page']-2); $i <= min($pg['total_pages'], $pg['page']+2); $i++): ?>
              <li class="page-item">
                <a class="page-link rounded" style="<?= $i===$pg['page']?'background:var(--gold);color:var(--blue-dark);':'background:var(--white);color:var(--blue-dark);' ?> border:1px solid var(--gray-200);"
                   href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if ($pg['has_next']): ?>
              <li class="page-item">
                <a class="page-link rounded" style="background:var(--blue-dark);color:var(--white);border:none;"
                   href="?<?= http_build_query(array_merge($_GET, ['page' => $pg['page'] + 1])) ?>">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../app/helpers/footer.php'; ?>
