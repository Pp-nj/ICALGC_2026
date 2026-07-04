<?php
require_once __DIR__ . '/../app/helpers/init.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mail;
use App\Core\Notification;

Auth::redirectIfLoggedIn();

$error   = '';
$success = '';
$appUrl  = APP_URL;
$_lang   = lang();
$csrf    = Auth::csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf(post('csrf_token'))) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title         = sanitize(post('title'));
        $titleOther    = sanitize(post('title_other'));
        $finalTitle    = ($title === 'Other') ? $titleOther : $title;
        $fname         = sanitize(post('first_name'));
        $mname         = sanitize(post('middle_name'));
        $lname         = sanitize(post('last_name'));
        $certName      = sanitize(post('cert_name'));
        $affil         = sanitize(post('affiliation'));
        $department    = sanitize(post('department'));
        $position      = sanitize(post('position'));
        $country       = sanitize(post('country', 'Thailand'));
        $email         = sanitizeEmail(post('email'));
        $phoneCode     = sanitize(post('phone_code', '+66'));
        $phoneNum      = sanitize(post('phone_number'));
        $phone         = $phoneCode . ' ' . $phoneNum;
        $partType      = sanitize(post('participation_type'));
        $dietary       = sanitize(post('dietary'));
        $dietaryAllergy = sanitize(post('dietary_allergy'));
        $pwd           = post('password');
        $cpwd          = post('confirm_password');
        $consent3      = post('consent3');

        $errors = [];
        if (!$fname)               $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อ' : 'First name is required.';
        if (!$lname)               $errors[] = $_lang==='th' ? 'กรุณากรอกนามสกุล' : 'Last name is required.';
        if (!$certName)            $errors[] = $_lang==='th' ? 'กรุณากรอกชื่อสำหรับออกเกียรติบัตร' : 'Name for certificate is required.';
        if (!$affil)               $errors[] = $_lang==='th' ? 'กรุณากรอกหน่วยงาน' : 'Affiliation / Institution is required.';
        if (!$country)             $errors[] = $_lang==='th' ? 'กรุณาเลือกประเทศ' : 'Country is required.';
        if (!validateEmail($email)) $errors[] = $_lang==='th' ? 'อีเมลไม่ถูกต้อง' : 'Invalid email address.';
        if (!$phoneNum)            $errors[] = $_lang==='th' ? 'กรุณากรอกเบอร์โทรศัพท์' : 'Phone number is required.';
        if (!$partType)            $errors[] = $_lang==='th' ? 'กรุณาเลือกประเภทการเข้าร่วม' : 'Participation type is required.';
        if ($dietary === 'allergy' && !$dietaryAllergy) $errors[] = $_lang==='th' ? 'กรุณาระบุอาหารที่แพ้' : 'Please specify your food allergy.';
        if (!$consent3) $errors[] = $_lang==='th' ? 'กรุณายอมรับข้อกำหนดและเงื่อนไข' : 'Please accept the terms and conditions.';
        if (strlen($pwd) < 8)      $errors[] = $_lang==='th' ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.';
        if ($pwd !== $cpwd)        $errors[] = $_lang==='th' ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.';

        if (empty($errors)) {
            try {
                $db = Database::getInstance();

                $chk = $db->prepare("SELECT id FROM users WHERE email = :email");
                $chk->execute([':email' => $email]);
                if ($chk->fetch()) {
                    $errors[] = $_lang==='th' ? 'อีเมลนี้ถูกใช้แล้ว' : 'This email is already registered.';
                }

                if (empty($errors)) {
                    $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);

                    $ins = $db->prepare("
                        INSERT INTO users
                            (role, title, first_name, middle_name, last_name, cert_name,
                             email, phone, affiliation, department, position,
                             country,
                             participation_type, dietary, dietary_allergy,
                             password_hash, account_status)
                        VALUES
                            ('author', :title, :fn, :mname, :ln, :cert_name,
                             :email, :phone, :affil, :dept, :pos,
                             :country,
                             :part_type, :dietary, :dietary_allergy,
                             :hash, 'active')
                    ");
                    $ins->execute([
                        ':title'           => $finalTitle,
                        ':fn'              => $fname,
                        ':mname'           => $mname ?: null,
                        ':ln'              => $lname,
                        ':cert_name'       => $certName,
                        ':email'           => $email,
                        ':phone'           => $phone,
                        ':affil'           => $affil,
                        ':dept'            => $department ?: null,
                        ':pos'             => $position ?: null,
                        ':country'         => $country,
                        ':part_type'       => $partType ?: null,
                        ':dietary'         => $dietary ?: null,
                        ':dietary_allergy' => ($dietary === 'allergy' && $dietaryAllergy) ? $dietaryAllergy : null,
                        ':hash'            => $hash,
                    ]);
                    $userId = (int)$db->lastInsertId();

                    $token   = generateToken();
                    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:uid, :tok, :exp)")
                       ->execute([':uid' => $userId, ':tok' => $token, ':exp' => $expires]);

                    Mail::sendEmailVerification($email, $fname . ' ' . $lname, $token);
                    auditLog('register', 'auth', 'New author: ' . $email, $userId);
                    flashSet('success', t('auth.register_success'));
                    redirect('/login.php');
                }
            } catch (\Throwable $e) {
                $errors[] = 'System error. Please try again.';
                error_log($e->getMessage());
            }
        }

        $error = implode('<br>', $errors);
    }
}

// ── Data helpers ────────────────────────────────────────────────────────────
$isTh = ($_lang === 'th');

// Title options are language-aware; value stored in DB is the displayed label
$titleOptions = $isTh
    ? [
        ['value' => 'นาย',    'label' => 'นาย'],
        ['value' => 'นาง',    'label' => 'นาง'],
        ['value' => 'นางสาว', 'label' => 'นางสาว'],
        ['value' => 'ศ.',     'label' => 'ศ.'],
        ['value' => 'รศ.',    'label' => 'รศ.'],
        ['value' => 'ผศ.',    'label' => 'ผศ.'],
        ['value' => 'ดร.',    'label' => 'ดร.'],
        ['value' => 'Other',  'label' => 'อื่นๆ'],
      ]
    : [
        ['value' => 'Mr.',          'label' => 'Mr.'],
        ['value' => 'Mrs.',         'label' => 'Mrs.'],
        ['value' => 'Ms.',          'label' => 'Ms.'],
        ['value' => 'Prof.',        'label' => 'Prof.'],
        ['value' => 'Assoc. Prof.', 'label' => 'Assoc. Prof.'],
        ['value' => 'Asst. Prof.',  'label' => 'Asst. Prof.'],
        ['value' => 'Dr.',          'label' => 'Dr.'],
        ['value' => 'Other',        'label' => 'Other'],
      ];

$countries = [
    'Thailand','Afghanistan','Albania','Algeria','Argentina','Armenia','Australia',
    'Austria','Azerbaijan','Bahrain','Bangladesh','Belarus','Belgium','Bolivia','Brazil',
    'Brunei','Bulgaria','Cambodia','Canada','Chile','China','Colombia','Croatia','Cuba',
    'Czech Republic','Denmark','Ecuador','Egypt','Ethiopia','Finland','France','Georgia',
    'Germany','Ghana','Greece','Guatemala','Hungary','India','Indonesia','Iran','Iraq',
    'Ireland','Israel','Italy','Japan','Jordan','Kazakhstan','Kenya','South Korea',
    'Kuwait','Laos','Latvia','Lebanon','Libya','Lithuania','Luxembourg','Malaysia',
    'Maldives','Malta','Mexico','Mongolia','Morocco','Myanmar','Nepal','Netherlands',
    'New Zealand','Nigeria','Norway','Oman','Pakistan','Palestine','Panama','Peru',
    'Philippines','Poland','Portugal','Qatar','Romania','Russia','Saudi Arabia','Senegal',
    'Serbia','Singapore','Slovakia','Slovenia','Somalia','South Africa','Spain','Sri Lanka',
    'Sudan','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Timor-Leste',
    'Tunisia','Turkey','Turkmenistan','Uganda','Ukraine','United Arab Emirates',
    'United Kingdom','United States','Uruguay','Uzbekistan','Venezuela','Vietnam','Yemen','Zimbabwe',
];
sort($countries);
array_unshift($countries, 'Thailand'); // Thailand first
$countries = array_unique($countries);

$phoneCodes = [
    '+66'  => ['TH', '🇹🇭', 'Thailand'],
    '+1'   => ['US', '🇺🇸', 'USA / Canada'],
    '+44'  => ['GB', '🇬🇧', 'United Kingdom'],
    '+61'  => ['AU', '🇦🇺', 'Australia'],
    '+81'  => ['JP', '🇯🇵', 'Japan'],
    '+82'  => ['KR', '🇰🇷', 'Korea'],
    '+86'  => ['CN', '🇨🇳', 'China'],
    '+852' => ['HK', '🇭🇰', 'Hong Kong'],
    '+853' => ['MO', '🇲🇴', 'Macau'],
    '+886' => ['TW', '🇹🇼', 'Taiwan'],
    '+60'  => ['MY', '🇲🇾', 'Malaysia'],
    '+65'  => ['SG', '🇸🇬', 'Singapore'],
    '+62'  => ['ID', '🇮🇩', 'Indonesia'],
    '+63'  => ['PH', '🇵🇭', 'Philippines'],
    '+84'  => ['VN', '🇻🇳', 'Vietnam'],
    '+95'  => ['MM', '🇲🇲', 'Myanmar'],
    '+855' => ['KH', '🇰🇭', 'Cambodia'],
    '+856' => ['LA', '🇱🇦', 'Laos'],
    '+673' => ['BN', '🇧🇳', 'Brunei'],
    '+670' => ['TL', '🇹🇱', 'Timor-Leste'],
    '+91'  => ['IN', '🇮🇳', 'India'],
    '+92'  => ['PK', '🇵🇰', 'Pakistan'],
    '+880' => ['BD', '🇧🇩', 'Bangladesh'],
    '+94'  => ['LK', '🇱🇰', 'Sri Lanka'],
    '+977' => ['NP', '🇳🇵', 'Nepal'],
    '+7'   => ['RU', '🇷🇺', 'Russia'],
    '+49'  => ['DE', '🇩🇪', 'Germany'],
    '+33'  => ['FR', '🇫🇷', 'France'],
    '+39'  => ['IT', '🇮🇹', 'Italy'],
    '+34'  => ['ES', '🇪🇸', 'Spain'],
    '+55'  => ['BR', '🇧🇷', 'Brazil'],
    '+971' => ['AE', '🇦🇪', 'UAE'],
    '+966' => ['SA', '🇸🇦', 'Saudi Arabia'],
    '+20'  => ['EG', '🇪🇬', 'Egypt'],
    '+234' => ['NG', '🇳🇬', 'Nigeria'],
    '+27'  => ['ZA', '🇿🇦', 'South Africa'],
];

$savedPhoneCode = post('phone_code', '+66');
$savedPartType  = post('participation_type', '');
$savedDietary   = post('dietary', '');
$savedSpecial   = is_array(post('special_assistance')) ? post('special_assistance') : [];
?>
<!DOCTYPE html>
<html lang="<?= $_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('auth.register') ?> — ICALGC 2026</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
  <style>
    .reg-section-header {
      font-size: .95rem;
      font-weight: 700;
      color: var(--blue-dark, #002864);
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 12px;
      margin-bottom: 20px;
      margin-top: 32px;
    }
    .reg-section-header:first-of-type { margin-top: 0; }

    /* Participation cards */
    .participation-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    @media (max-width: 480px) {
      .participation-grid { grid-template-columns: 1fr; }
    }
    .part-card {
      position: relative;
      border: 2px solid #d1d5db;
      border-radius: 12px;
      padding: 20px 14px;
      text-align: center;
      cursor: pointer;
      transition: all .2s;
    }
    .part-card:hover { border-color: var(--blue-mid, #1a56db); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
    .part-card.selected { border-color: var(--blue-dark, #002864); background: rgba(0,40,100,.04); }
    .part-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .part-card-icon { font-size: 2rem; margin-bottom: 8px; display: block; }
    .part-card-title { font-weight: 700; font-size: .9rem; color: var(--blue-dark, #002864); }
    .part-card-sub { font-size: .75rem; color: #6b7280; margin-top: 4px; }
    .part-card .check-badge {
      position: absolute; top: 8px; right: 8px;
      width: 20px; height: 20px; border-radius: 50%;
      background: var(--blue-dark, #002864); color: #fff;
      display: none; align-items: center; justify-content: center;
      font-size: .65rem; font-weight: 700;
    }
    .part-card.selected .check-badge { display: flex; }

    /* Dietary & assistance toggles */
    .toggle-options { display: flex; flex-wrap: wrap; gap: 8px; }
    .toggle-btn {
      display: flex; align-items: center; gap: 7px;
      padding: 7px 14px;
      border: 2px solid #d1d5db;
      border-radius: 24px;
      cursor: pointer;
      font-size: .84rem;
      font-weight: 500;
      transition: all .2s;
      background: #fff;
      user-select: none;
    }
    .toggle-btn:hover { border-color: var(--blue-mid, #1a56db); }
    .toggle-btn.selected { border-color: var(--blue-dark, #002864); background: var(--blue-dark, #002864); color: #fff; }

    /* Consent items */
    .consent-item {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px;
      border: 1.5px solid #d1d5db;
      border-radius: 8px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: border-color .2s;
    }
    .consent-item:hover { border-color: var(--blue-mid, #1a56db); }
    .consent-item.checked { border-color: var(--blue-dark, #002864); background: rgba(0,40,100,.03); }
    .consent-box {
      flex-shrink: 0;
      width: 22px; height: 22px;
      border: 2px solid #9ca3af;
      border-radius: 4px;
      display: flex; align-items: center; justify-content: center;
      margin-top: 1px;
      transition: all .2s;
      font-size: .8rem;
      color: #fff;
      font-weight: 700;
    }
    .consent-item.checked .consent-box { background: var(--blue-dark, #002864); border-color: var(--blue-dark, #002864); }
    .consent-item input[type="checkbox"] { display: none; }

    /* Phone row */
    .phone-row { display: flex; gap: 8px; }
    .phone-row .code-wrap { flex: 0 0 160px; }
    .phone-row .num-wrap  { flex: 1; }
    @media (max-width: 400px) {
      .phone-row { flex-direction: column; }
      .phone-row .code-wrap { flex: none; }
    }

    /* Submit button disabled state */
    #btnSubmit:disabled { opacity: .55; cursor: not-allowed; }

    /* Step indicator */
    .step-indicator {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 28px; position: relative;
    }
    .step-indicator::before {
      content: ''; position: absolute; top: 16px; left: 0; right: 0; height: 2px;
      background: #e5e7eb; z-index: 0;
    }
    .step-dot {
      position: relative; z-index: 1;
      width: 32px; height: 32px; border-radius: 50%;
      background: #e5e7eb; color: #6b7280;
      display: flex; align-items: center; justify-content: center;
      font-size: .75rem; font-weight: 700;
      flex-direction: column; gap: 2px;
    }
    .step-dot.active { background: var(--blue-dark, #002864); color: #fff; }
    .step-dot.done { background: #059669; color: #fff; }
  </style>
</head>
<body>

<div class="auth-page" style="align-items:flex-start;padding:40px 16px;">
  <div class="auth-card" style="max-width:720px;">

    <!-- Logo -->
    <div class="auth-logo">
      <div class="d-flex justify-content-center gap-3 align-items-center mb-2">
        <img src="<?= $appUrl ?>/assets/images/swu_Logo.png" alt="SWU" style="height:50px;" onerror="this.style.display='none'">
        <img src="<?= $appUrl ?>/assets/images/Guangdong University of Foreign Studies 02.png" alt="GDUF" style="height:50px;" onerror="this.style.display='none'">
      </div>
      <h4>ICALGC 2026</h4>
    </div>

    <h2 class="auth-title"><?= $isTh ? 'ลงทะเบียนเข้าร่วมงาน' : 'Conference Registration' ?></h2>
    <p class="auth-subtitle">
      <?= $isTh
        ? 'กรอกข้อมูลเพื่อลงทะเบียนเข้าร่วมการประชุมวิชาการนานาชาติ ICALGC 2026'
        : 'Complete the form below to register for ICALGC 2026.' ?>
    </p>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
        <i class="fas fa-exclamation-circle mt-1 flex-shrink-0"></i>
        <span><?= $error ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate id="regForm">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <!-- ══════════════════════════════════════════════════════
           SECTION 1: Personal Information
      ══════════════════════════════════════════════════════ -->
      <div class="reg-section-header">
        <i class="fas fa-user me-2" style="color:var(--gold,#c49a2d);"></i>
        <?= $isTh ? 'ส่วนที่ 1 — ข้อมูลทั่วไป' : 'Section 1 — Personal Information' ?>
      </div>

      <!-- Title Dropdown -->
      <div class="mb-3">
        <label class="form-label fw-semibold" for="title_select">
          <?= $isTh ? 'คำนำหน้านาม' : 'Title / Prefix' ?>
        </label>
        <select name="title" id="title_select" class="form-select" style="max-width:260px;"
                onchange="handleTitleChange(this)">
          <option value=""><?= $isTh ? '— เลือกคำนำหน้า —' : '— Select Title —' ?></option>
          <?php foreach ($titleOptions as $opt):
            $sel = (post('title') === $opt['value']) ? 'selected' : ''; ?>
            <option value="<?= e($opt['value']) ?>" <?= $sel ?><?= $opt['value']==='Other' ? ' data-other="1"' : '' ?>><?= e($opt['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <div id="titleOtherWrap" class="mt-2" style="display:<?= (post('title')==='Other') ? 'block' : 'none' ?>;">
          <input type="text" name="title_other" id="titleOtherInput" class="form-control"
                 placeholder="<?= $isTh ? 'ระบุคำนำหน้า...' : 'Specify title...' ?>"
                 value="<?= e(post('title_other')) ?>"
                 style="max-width:220px;">
        </div>
      </div>

      <div class="row g-3">
        <!-- First Name -->
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="first_name">
            <?= $isTh ? 'ชื่อ' : 'First Name' ?>
            <span class="text-danger">*</span>
          </label>
          <input type="text" id="first_name" name="first_name" class="form-control"
                 value="<?= e(post('first_name')) ?>"
                 placeholder="<?= $isTh ? 'ชื่อ' : 'e.g. Somchai' ?>" required>
        </div>

        <!-- Middle Name -->
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="middle_name">
            <?= $isTh ? 'ชื่อกลาง' : 'Middle Name' ?>
            <span class="text-muted" style="font-size:.78rem;font-weight:400;"><?= $isTh ? '(ถ้ามี)' : '(optional)' ?></span>
          </label>
          <input type="text" id="middle_name" name="middle_name" class="form-control"
                 value="<?= e(post('middle_name')) ?>"
                 placeholder="<?= $isTh ? 'ชื่อกลาง' : 'Middle name' ?>">
        </div>

        <!-- Last Name -->
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="last_name">
            <?= $isTh ? 'นามสกุล' : 'Last Name' ?>
            <span class="text-danger">*</span>
          </label>
          <input type="text" id="last_name" name="last_name" class="form-control"
                 value="<?= e(post('last_name')) ?>"
                 placeholder="<?= $isTh ? 'นามสกุล' : 'e.g. Srisuk' ?>" required>
        </div>

        <!-- Name for Certificate -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="cert_name">
            <?= $isTh ? 'ชื่อสำหรับออกเกียรติบัตร' : 'Name for Certificate' ?>
            <span class="text-danger">*</span>
          </label>
          <input type="text" id="cert_name" name="cert_name" class="form-control"
                 value="<?= e(post('cert_name')) ?>"
                 placeholder="<?= $isTh ? 'ชื่อที่ต้องการให้ปรากฏบนเกียรติบัตร' : 'Name as it should appear on the certificate' ?>" required>
        </div>

        <!-- Affiliation -->
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="affiliation">
            <?= $isTh ? 'หน่วยงาน / สถาบัน' : 'Affiliation / Institution' ?>
            <span class="text-danger">*</span>
          </label>
          <input type="text" id="affiliation" name="affiliation" class="form-control"
                 value="<?= e(post('affiliation')) ?>"
                 placeholder="<?= $isTh ? 'มหาวิทยาลัย / องค์กร' : 'University / Organization' ?>" required>
        </div>

        <!-- Department / Faculty -->
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="department">
            <?= $isTh ? 'ภาควิชา / คณะ' : 'Department / Faculty' ?>
          </label>
          <input type="text" id="department" name="department" class="form-control"
                 value="<?= e(post('department')) ?>"
                 placeholder="<?= $isTh ? 'ภาควิชา หรือ คณะ' : 'Department or Faculty' ?>">
        </div>

        <!-- Position -->
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="position">
            <?= $isTh ? 'ตำแหน่ง' : 'Position' ?>
          </label>
          <input type="text" id="position" name="position" class="form-control"
                 value="<?= e(post('position')) ?>"
                 placeholder="<?= $isTh ? 'เช่น อาจารย์, นักวิจัย, นักศึกษา' : 'e.g. Lecturer, Researcher, Student' ?>">
        </div>

        <!-- Country -->
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="country">
            <?= $isTh ? 'ประเทศ' : 'Country' ?>
            <span class="text-danger">*</span>
          </label>
          <select id="country" name="country" class="form-select" required>
            <option value=""><?= $isTh ? '— เลือกประเทศ —' : '— Select Country —' ?></option>
            <?php foreach ($countries as $c):
              $sel = (post('country', 'Thailand') === $c) ? 'selected' : ''; ?>
              <option value="<?= e($c) ?>" <?= $sel ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Email -->
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="email">
            <?= $isTh ? 'อีเมล' : 'Email Address' ?>
            <span class="text-danger">*</span>
          </label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e(post('email')) ?>"
                 placeholder="your@email.com" required>
        </div>

        <!-- Phone -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">
            <?= $isTh ? 'เบอร์โทรศัพท์' : 'Phone Number' ?>
            <span class="text-danger">*</span>
          </label>
          <div class="phone-row">
            <div class="code-wrap">
              <select name="phone_code" id="phone_code" class="form-select">
                <?php foreach ($phoneCodes as $code => [$iso, $flag, $name]):
                  $sel = ($savedPhoneCode === $code) ? 'selected' : ''; ?>
                  <option value="<?= e($code) ?>" <?= $sel ?>><?= $flag ?> <?= e($code) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="num-wrap">
              <input type="tel" name="phone_number" id="phone_number" class="form-control"
                     value="<?= e(post('phone_number')) ?>"
                     placeholder="<?= $isTh ? 'หมายเลขโทรศัพท์' : 'Phone number' ?>" required>
            </div>
          </div>
        </div>
      </div><!-- /row -->

      <!-- ══════════════════════════════════════════════════════
           SECTION 2: Participation Type
      ══════════════════════════════════════════════════════ -->
      <div class="reg-section-header">
        <i class="fas fa-id-badge me-2" style="color:var(--gold,#c49a2d);"></i>
        <?= $isTh ? 'ส่วนที่ 2 — ประเภทการเข้าร่วม' : 'Section 2 — Participation Type' ?>
        <span class="text-danger ms-1">*</span>
      </div>

      <div class="participation-grid">
        <?php
        $partOptions = [
          'presenter' => [
            'icon'  => '🎤',
            'th'    => 'ผู้นำเสนอผลงาน',
            'en'    => 'Presenter',
            'th_s'  => 'นำเสนอบทคัดย่อวิจัย',
            'en_s'  => 'Present research paper',
          ],
          'coauthor' => [
            'icon'  => '✍️',
            'th'    => 'ผู้ร่วมแต่ง',
            'en'    => 'Co-author',
            'th_s'  => 'ผู้แต่งร่วมในบทคัดย่อ',
            'en_s'  => 'Co-author of a paper',
          ],
          'participant' => [
            'icon'  => '👤',
            'th'    => 'ผู้เข้าร่วม',
            'en'    => 'Participant',
            'th_s'  => 'เข้าร่วมรับฟังการบรรยาย',
            'en_s'  => 'Attend lectures & sessions',
          ],
          'student' => [
            'icon'  => '🎓',
            'th'    => 'นักศึกษา',
            'en'    => 'Student',
            'th_s'  => 'ผู้เข้าร่วมในฐานะนักศึกษา',
            'en_s'  => 'Attending as a student',
          ],
        ];
        foreach ($partOptions as $val => $opt):
          $isSelected = ($savedPartType === $val); ?>
          <label class="part-card <?= $isSelected ? 'selected' : '' ?>" onclick="selectPartType(this, '<?= $val ?>')">
            <input type="radio" name="participation_type" value="<?= $val ?>" <?= $isSelected ? 'checked' : '' ?>>
            <span class="check-badge"><i class="fas fa-check"></i></span>
            <span class="part-card-icon"><?= $opt['icon'] ?></span>
            <div class="part-card-title"><?= $isTh ? $opt['th'] : $opt['en'] ?></div>
            <div class="part-card-sub"><?= $isTh ? $opt['th_s'] : $opt['en_s'] ?></div>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- ══════════════════════════════════════════════════════
           SECTION 4: Additional Information
      ══════════════════════════════════════════════════════ -->
      <div class="reg-section-header">
        <i class="fas fa-plus-circle me-2" style="color:var(--gold,#c49a2d);"></i>
        <?= $isTh ? 'ส่วนที่ 3 — ความต้องการเพิ่มเติม' : 'Section 3 — Additional Information' ?>
        <span class="text-muted ms-2" style="font-size:.8rem;font-weight:400;"><?= $isTh ? '(ไม่บังคับ)' : '(Optional)' ?></span>
      </div>

      <!-- Dietary Requirements -->
      <div class="mb-4">
        <label class="form-label fw-semibold mb-2">
          <?= $isTh ? 'ความต้องการด้านอาหาร' : 'Dietary Requirements' ?>
        </label>
        <?php
        $dietaryOpts = [
          'none'       => ['🍽️', $isTh ? 'ไม่มีข้อจำกัด' : 'No Restriction'],
          'vegetarian' => ['🥦', $isTh ? 'มังสวิรัติ' : 'Vegetarian'],
          'halal'      => ['☪️', $isTh ? 'ฮาลาล' : 'Halal'],
          'allergy'    => ['⚠️', $isTh ? 'แพ้อาหาร' : 'Food Allergy'],
        ];
        ?>
        <div class="toggle-options" id="dietaryOptions">
          <?php foreach ($dietaryOpts as $dval => [$icon, $label]):
            $isSelected = ($savedDietary === $dval); ?>
            <button type="button"
                    class="toggle-btn <?= $isSelected ? 'selected' : '' ?>"
                    data-val="<?= $dval ?>"
                    onclick="selectDietary(this)">
              <?= $icon ?> <?= $label ?>
            </button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="dietary" id="dietaryInput" value="<?= e($savedDietary) ?>">
        <!-- Food Allergy detail -->
        <div id="allergyWrap" class="mt-2" style="display:<?= ($savedDietary === 'allergy') ? 'block' : 'none' ?>;">
          <input type="text" name="dietary_allergy" class="form-control"
                 value="<?= e(post('dietary_allergy')) ?>"
                 placeholder="<?= $isTh ? 'ระบุอาหารที่แพ้ เช่น กุ้ง ถั่ว นม...' : 'e.g. shrimp, peanuts, dairy...' ?>"
                 style="max-width:380px;">
        </div>
      </div>


      <!-- ══════════════════════════════════════════════════════
           Password Section (required for account creation)
      ══════════════════════════════════════════════════════ -->
      <div class="reg-section-header">
        <i class="fas fa-shield-alt me-2" style="color:var(--gold,#c49a2d);"></i>
        <?= $isTh ? 'ส่วนที่ 4 — ความปลอดภัยของบัญชี' : 'Section 4 — Account Security' ?>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="password">
            <?= t('auth.password') ?> <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="<?= $isTh ? 'อย่างน้อย 8 ตัวอักษร' : 'Min 8 characters' ?>" required>
            <button class="input-group-text" type="button" onclick="togglePwd('password',this)" style="cursor:pointer;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="mt-1" id="pwdStrength" style="font-size:.75rem;"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="confirm_password">
            <?= t('auth.confirm_password') ?> <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                   placeholder="<?= $isTh ? 'ยืนยันรหัสผ่าน' : 'Confirm password' ?>" required>
            <button class="input-group-text" type="button" onclick="togglePwd('confirm_password',this)" style="cursor:pointer;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div id="pwdMatchMsg" style="font-size:.75rem;margin-top:4px;"></div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           SECTION 5: Confirmation & Consent
      ══════════════════════════════════════════════════════ -->
      <div class="reg-section-header">
        <i class="fas fa-check-circle me-2" style="color:var(--gold,#c49a2d);"></i>
        <?= $isTh ? 'ส่วนที่ 5 — ยืนยันข้อมูล' : 'Section 5 — Confirmation' ?>
        <span class="text-danger ms-1">*</span>
      </div>

      <div class="mb-4">
        <!-- Consent: Terms & Conditions only -->
        <div class="consent-item <?= post('consent3') ? 'checked' : '' ?>" onclick="toggleConsent(this, 'consent3')">
          <input type="checkbox" name="consent3" id="consent3" <?= post('consent3') ? 'checked' : '' ?>>
          <div class="consent-box">✓</div>
          <div style="font-size:.88rem;line-height:1.5;">
            <strong><?= $isTh ? 'ยอมรับข้อกำหนดและเงื่อนไข' : 'Terms & Conditions' ?></strong><br>
            <?= $isTh
              ? 'ข้าพเจ้ายอมรับข้อกำหนดและเงื่อนไขของการประชุมวิชาการนานาชาติ ICALGC 2026 ทุกประการ'
              : 'I accept all terms and conditions of the ICALGC 2026 international conference.' ?>
          </div>
        </div>
      </div>

      <!-- Info notice -->
      <div class="p-3 rounded mb-4" style="background:var(--blue-light,#eff6ff);font-size:.83rem;color:var(--blue-dark,#002864);">
        <i class="fas fa-info-circle me-1"></i>
        <?= $isTh
          ? 'หลังจากลงทะเบียน ระบบจะส่งอีเมลยืนยันไปที่อีเมลของคุณ กรุณายืนยันก่อนเข้าสู่ระบบ'
          : 'After registration, a verification email will be sent to your email address. Please verify before logging in.' ?>
      </div>

      <!-- Submit -->
      <button type="submit" id="btnSubmit"
              class="btn w-100 py-3 fw-bold"
              style="background:var(--blue-dark,#002864);color:#fff;border-radius:8px;font-size:1rem;">
        <i class="fas fa-paper-plane me-2"></i>
        <?= $isTh ? 'ส่งข้อมูลการลงทะเบียน' : 'Submit Registration' ?>
        <span style="opacity:.8;font-size:.85rem;display:block;font-weight:400;margin-top:2px;">
          <?= $isTh ? 'Submit Registration' : 'ส่งข้อมูลการลงทะเบียน' ?>
        </span>
      </button>

    </form>

    <div class="text-center mt-4" style="font-size:.88rem;">
      <?= t('auth.have_account') ?>
      <a href="<?= $appUrl ?>/login.php" style="color:var(--blue-mid,#1a56db);font-weight:700;"><?= t('auth.login') ?></a>
    </div>

    <div class="text-center mt-2">
      <a href="<?= $appUrl ?>/" style="font-size:.8rem;color:var(--gray-500,#6b7280);">
        <i class="fas fa-arrow-left me-1"></i>
        <?= $isTh ? 'กลับหน้าหลัก' : 'Back to Home' ?>
      </a>
    </div>

  </div><!-- /auth-card -->
</div><!-- /auth-page -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Title "Other" show/hide ────────────────────────────────────────────────
function handleTitleChange(sel) {
  const wrap = document.getElementById('titleOtherWrap');
  wrap.style.display = (sel.value === 'Other') ? 'block' : 'none';
  if (sel.value !== 'Other') {
    document.getElementById('titleOtherInput').value = '';
  }
}

// ── Participation type card selection ─────────────────────────────────────
function selectPartType(card, val) {
  document.querySelectorAll('.part-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  card.querySelector('input[type="radio"]').checked = true;
  updateSubmitBtn();
}

// ── Dietary single-select ─────────────────────────────────────────────────
function selectDietary(btn) {
  document.querySelectorAll('#dietaryOptions .toggle-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('dietaryInput').value = btn.dataset.val;
  const allergyWrap = document.getElementById('allergyWrap');
  allergyWrap.style.display = (btn.dataset.val === 'allergy') ? 'block' : 'none';
  if (btn.dataset.val !== 'allergy') {
    allergyWrap.querySelector('input').value = '';
  }
}

// ── Consent checkboxes ────────────────────────────────────────────────────
function toggleConsent(item, name) {
  item.classList.toggle('checked');
  const cb = document.getElementById(name);
  if (cb) cb.checked = item.classList.contains('checked');
  updateSubmitBtn();
}

// ── Password toggle ────────────────────────────────────────────────────────
function togglePwd(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
  else { input.type = 'password'; icon.className = 'fas fa-eye'; }
}

// ── Password strength ─────────────────────────────────────────────────────
document.getElementById('password')?.addEventListener('input', function() {
  const val = this.value;
  const el  = document.getElementById('pwdStrength');
  if (!el) return;
  let strength = 0;
  if (val.length >= 8)          strength++;
  if (/[A-Z]/.test(val))        strength++;
  if (/[0-9]/.test(val))        strength++;
  if (/[^A-Za-z0-9]/.test(val)) strength++;
  const levels = [
    { label: '<?= $isTh ? "อ่อนมาก" : "Very Weak" ?>',    color: '#dc3545' },
    { label: '<?= $isTh ? "อ่อน" : "Weak" ?>',            color: '#fd7e14' },
    { label: '<?= $isTh ? "ปานกลาง" : "Fair" ?>',         color: '#ffc107' },
    { label: '<?= $isTh ? "แข็งแรง" : "Strong" ?>',       color: '#198754' },
    { label: '<?= $isTh ? "แข็งแรงมาก" : "Very Strong" ?>', color: '#0f5132' },
  ];
  if (val.length === 0) { el.innerHTML = ''; return; }
  const l = levels[strength] || levels[0];
  el.innerHTML = `<span style="color:${l.color};">● ${l.label}</span>`;
  updateSubmitBtn();
});

// ── Password match indicator ──────────────────────────────────────────────
document.getElementById('confirm_password')?.addEventListener('input', function() {
  const pwd  = document.getElementById('password').value;
  const msg  = document.getElementById('pwdMatchMsg');
  if (!this.value) { msg.innerHTML = ''; return; }
  if (this.value === pwd) {
    msg.innerHTML = '<span style="color:#198754;">● <?= $isTh ? "รหัสผ่านตรงกัน" : "Passwords match" ?></span>';
  } else {
    msg.innerHTML = '<span style="color:#dc3545;">● <?= $isTh ? "รหัสผ่านไม่ตรงกัน" : "Passwords do not match" ?></span>';
  }
  updateSubmitBtn();
});

// ── Submit button enablement ──────────────────────────────────────────────
function updateSubmitBtn() {
  const btn       = document.getElementById('btnSubmit');
  if (!btn) return;
  const fname     = document.getElementById('first_name')?.value.trim();
  const lname     = document.getElementById('last_name')?.value.trim();
  const certName  = document.getElementById('cert_name')?.value.trim();
  const affil     = document.getElementById('affiliation')?.value.trim();
  const country   = document.getElementById('country')?.value;
  const email     = document.getElementById('email')?.value.trim();
  const phoneNum  = document.getElementById('phone_number')?.value.trim();
  const partType  = document.querySelector('input[name="participation_type"]:checked');
  const consent3  = document.getElementById('consent3')?.checked;
  const pwd       = document.getElementById('password')?.value;
  const cpwd      = document.getElementById('confirm_password')?.value;
  const emailOk   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  const ok = fname && lname && certName && affil && country && emailOk
          && phoneNum && partType && consent3
          && pwd && pwd.length >= 8 && pwd === cpwd;

  btn.disabled = !ok;
}

// Run on input for all required fields
['first_name','last_name','cert_name','affiliation','country','email','phone_number']
  .forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateSubmitBtn);
    document.getElementById(id)?.addEventListener('change', updateSubmitBtn);
  });

// Form submit validation (server-side handles final validation, this is UX)
document.getElementById('regForm')?.addEventListener('submit', function(e) {
  const errors = [];
  const lang   = '<?= $_lang ?>';
  const isTh   = lang === 'th';

  if (!document.getElementById('first_name')?.value.trim())
    errors.push(isTh ? 'กรุณากรอกชื่อ' : 'First name is required.');
  if (!document.getElementById('last_name')?.value.trim())
    errors.push(isTh ? 'กรุณากรอกนามสกุล' : 'Last name is required.');
  if (!document.getElementById('cert_name')?.value.trim())
    errors.push(isTh ? 'กรุณากรอกชื่อสำหรับออกเกียรติบัตร' : 'Name for certificate is required.');
  if (!document.getElementById('affiliation')?.value.trim())
    errors.push(isTh ? 'กรุณากรอกหน่วยงาน' : 'Affiliation is required.');
  if (!document.getElementById('country')?.value)
    errors.push(isTh ? 'กรุณาเลือกประเทศ' : 'Country is required.');

  const email = document.getElementById('email')?.value.trim();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
    errors.push(isTh ? 'อีเมลไม่ถูกต้อง' : 'Invalid email address.');

  if (!document.getElementById('phone_number')?.value.trim())
    errors.push(isTh ? 'กรุณากรอกเบอร์โทรศัพท์' : 'Phone number is required.');

  if (!document.querySelector('input[name="participation_type"]:checked'))
    errors.push(isTh ? 'กรุณาเลือกประเภทการเข้าร่วม' : 'Participation type is required.');

  if (!document.getElementById('consent3')?.checked)
    errors.push(isTh ? 'กรุณายอมรับข้อกำหนดและเงื่อนไข' : 'Please accept the terms and conditions.');

  const pwd  = document.getElementById('password')?.value;
  const cpwd = document.getElementById('confirm_password')?.value;
  if (!pwd || pwd.length < 8)
    errors.push(isTh ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.');
  if (pwd !== cpwd)
    errors.push(isTh ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.');

  if (errors.length) {
    e.preventDefault();
    const alertEl = document.createElement('div');
    alertEl.className = 'alert alert-danger d-flex align-items-start gap-2 mb-4';
    alertEl.innerHTML = '<i class="fas fa-exclamation-circle mt-1 flex-shrink-0"></i><span>' + errors.join('<br>') + '</span>';
    const existing = document.querySelector('.alert-danger');
    if (existing) existing.remove();
    this.parentElement.insertBefore(alertEl, this);
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});

// Init on load
updateSubmitBtn();
</script>
</body>
</html>
