<?php
require 'session_timeout.php'; 
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
$user_id = $_SESSION['user_id'];

$errors = [];
$success = "";

// ดึงข้อมูลสมาชิก
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// เมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name        = trim($_POST['full_name']);
  $email            = trim($_POST['email']);
  $current_password = $_POST['current_password'] ?? '';
  $new_password     = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  // ตรวจสอบชื่อ/อีเมล
  if (empty($full_name) || empty($email)) {
    $errors[] = "กรุณากรอกชื่อ-นามสกุลและอีเมล";
  }

  // ตรวจสอบอีเมลซ้ำ
  $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND user_id != ?");
  $stmt->execute([$email, $user_id]);
  if ($stmt->fetchColumn()) {
    $errors[] = "อีเมลนี้ถูกใช้งานแล้ว";
  }

  // ตรวจสอบการเปลี่ยนรหัสผ่าน (ถ้ามี)
  if ($current_password || $new_password || $confirm_password) {
    if (!password_verify($current_password, $user['password'])) {
      $errors[] = "รหัสผ่านเดิมไม่ถูกต้อง";
    } elseif (strlen($new_password) < 6) {
      $errors[] = "รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร";
    } elseif ($new_password !== $confirm_password) {
      $errors[] = "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน";
    } else {
      $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
    }
  }

  // อัปเดตข้อมูลหากไม่มี error
  if (!$errors) {
    if (!empty($new_hashed)) {
      $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE user_id = ?");
      $stmt->execute([$full_name, $email, $new_hashed, $user_id]);
    } else {
      $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
      $stmt->execute([$full_name, $email, $user_id]);
    }
    $success = "บันทึกข้อมูลเรียบร้อยแล้ว";
    // อัปเดตค่าในหน้านี้
    $user['full_name'] = $full_name;
    $user['email']     = $email;
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>โปรไฟล์สมาชิก</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root{
      --bg:#f6f7fb; --ink:#0f172a; --muted:#64748b;
      --card:#ffffffcc; --stroke:#e6e8ef;
      --brand:#5b8cff; --brand2:#8b5bff;
    }
    html,body{height:100%}
    body{
      background:var(--bg); color:var(--ink); margin:0;
      font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Thai","Prompt",Arial,sans-serif;
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }
    /* พื้นหลังนามธรรม */
    .bg-abstract{
      position:fixed; inset:0; z-index:-1; pointer-events:none;
      background:
        radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
        radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
        radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);
    }
    .bg-abstract::after{
      content:""; position:absolute; inset:0;
      background:conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
      filter:blur(28px); opacity:.6;
    }

    .wrap{max-width:min(900px,96vw); margin:36px auto; padding:0 10px}
    .card-glass{
      background:var(--card); border:1px solid var(--stroke);
      backdrop-filter:saturate(110%) blur(12px);
      border-radius:24px; box-shadow:0 12px 36px rgba(15,23,42,.12);
      overflow:hidden;
    }
    .card-head{
      padding:18px 22px; border-bottom:1px solid rgba(15,23,42,.06);
      display:flex; gap:12px; justify-content:space-between; align-items:center; flex-wrap:wrap;
      background:#fff9;
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size:clamp(1.2rem,.9rem + 1.2vw,1.8rem);
    }
    .sub{margin:0; color:var(--muted); font-size:.95rem}
    .body{padding:22px}

    /* ปุ่มโทนเดียวกับธีม */
    .btn-brand{
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      border:none; color:#fff; font-weight:800;
      box-shadow:0 10px 24px rgba(91,140,255,.25);
    }
    .btn-ghost{
      border:1px solid #dfe3f3; background:#fff; color:#334155; font-weight:700;
    }
    .btn-ghost:hover{background:linear-gradient(135deg,var(--brand),var(--brand2)); color:#fff; border-color:transparent;}

    /* ฟอร์มสวยๆ */
    .form-label{font-weight:700; color:#334155}
    .form-control{
      border:1px solid #e6e8ef; padding:.65rem .9rem; border-radius:12px;
      box-shadow:0 2px 6px rgba(15,23,42,.02) inset;
    }
    .form-control:focus{
      border-color:#c7d2fe; box-shadow:0 0 0 .25rem rgba(91,140,255,.15);
    }
    .section-title{
      font-weight:800; color:#334155; margin:2px 0 10px;
    }
    .hr-soft{border:none; border-top:1px solid rgba(15,23,42,.06); margin:10px 0 4px}

    .alert{
      border-radius:14px; border:1px solid rgba(15,23,42,.06);
      box-shadow:0 8px 20px rgba(15,23,42,.06);
    }
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <main class="wrap">
    <div class="card-glass">
      <div class="card-head">
        <div>
          <h1 class="title">โปรไฟล์ของคุณ</h1>
          <p class="sub">แก้ไขชื่อ อีเมล และตั้งรหัสผ่านใหม่ได้ที่นี่</p>
        </div>
        <div class="d-flex gap-2">
          <a href="index.php" class="btn btn-ghost"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
        </div>
      </div>

      <div class="body">

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle"></i> มีข้อผิดพลาด</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif ($success): ?>
          <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3">
          <div class="col-md-6">
            <label for="full_name" class="form-label">ชื่อ - นามสกุล</label>
            <input type="text" id="full_name" name="full_name" class="form-control" required
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
          </div>

          <div class="col-12"><hr class="hr-soft"><div class="section-title">เปลี่ยนรหัสผ่าน (ไม่จำเป็น)</div></div>

          <div class="col-md-6">
            <label for="current_password" class="form-label">รหัสผ่านเดิม</label>
            <input type="password" id="current_password" name="current_password" class="form-control" autocomplete="current-password">
          </div>
          <div class="col-md-6">
            <label for="new_password" class="form-label">รหัสผ่านใหม่ (≥ 6 ตัวอักษร)</label>
            <input type="password" id="new_password" name="new_password" class="form-control" autocomplete="new-password">
          </div>
          <div class="col-md-6">
            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" autocomplete="new-password">
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><i class="bi bi-save2"></i> บันทึกการเปลี่ยนแปลง</button>
            <a href="orders.php" class="btn btn-ghost"><i class="bi bi-receipt"></i> ประวัติการสั่งซื้อ</a>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
