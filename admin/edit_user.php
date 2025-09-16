<?php
require '../config.php'; // TODO-1: เชื่อมต่อข้อมูลด้วย PDO
require 'auth_admin.php'; // TODO-2: กำหนดสิทธิ์ (Admin Guard)

// TODO-3: ตรวจสอบว่ามีพารามิเตอร์ id มาจริงไหม (ผ่าน GET)
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}
// TODO-4: ดึงค่า id และ "แคสต์เป็น int" เพื่อความปลอดภัย
$user_id = (int)$_GET['id'];

// ดึงข้อมูลสมาชิกที่จะถูกแก้ไข (เฉพาะ role = 'member')
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'member'");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// TODO-6: ถ้าไม่พบข้อมูล -> แสดงข้อความและ exit;
if (!$user) {
    echo "<h3>ไม่พบสมาชิก</h3>";
    exit;
}

// ========== เมื่อผู้ใช้กด Submit ฟอร์ม ==========
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if ($username === '' || $email === '') {
        $error = "กรุณากรอกข้อมูลให้ครบ";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบ Email ไม่ถูกต้อง";
    }

    if (!$error) {
        // ตรวจซ้ำ username/email กับคนอื่น
        $chk = $conn->prepare("SELECT 1 FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $chk->execute([$username, $email, $user_id]);
        if ($chk->fetch()) {
            $error = "Username หรือ Email มีอยู่ในระบบแล้ว";
        }
    }

    // ตรวจรหัสผ่าน (ปล่อยว่างได้ถ้าไม่เปลี่ยน)
    $updatePassword = false;
    $hashed = null;
    if (!$error && ($password !== '' || $confirm !== '')) {
        if (strlen($password) < 6) {
            $error = "รหัสผ่านต้องมีอย่างน้อย 6 ตัว";
        } elseif ($password !== $confirm) {
            $error = "รหัสใหม่กับยืนยันรหัสผ่านไม่ตรงกัน";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $updatePassword = true;
        }
    }

    if (!$error) {
        if ($updatePassword) {
            $sql  = "UPDATE users SET username = ?, full_name = ?, email = ?, password = ? WHERE user_id = ?";
            $args = [$username, $full_name, $email, $hashed, $user_id];
        } else {
            $sql  = "UPDATE users SET username = ?, full_name = ?, email = ? WHERE user_id = ?";
            $args = [$username, $full_name, $email, $user_id];
        }
        $upd = $conn->prepare($sql);
        $upd->execute($args);
        header("Location: users.php");
        exit;
    }

    // สะท้อนค่ากลับกรณี error
    $user['username']  = $username;
    $user['full_name'] = $full_name;
    $user['email']     = $email;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แก้ไขสมาชิก</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- โทนสว่าง-มินิมอล + Glass + พื้นหลังนามธรรม ให้เหมือน register.php -->
  <style>
    :root{
      --bg:#f6f7fb;
      --ink:#0f172a;
      --muted:#64748b;
      --card:#ffffffcc;        /* glass */
      --stroke:#e6e8ef;
      --brand:#5b8cff;         /* ฟ้าพาสเทล */
      --brand2:#8b5bff;        /* ม่วงพาสเทล */
    }

    html,body{height:100%}
    body{
      margin:0; background:var(--bg); color:var(--ink);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
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
      background: conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
      filter: blur(28px); opacity:.6;
    }

    .page{ min-height:100svh; display:grid; place-items:start center; padding:24px; }

    /* การ์ดครอบหน้า (Glassmorphism) */
    .card-glass{
      width:min(900px, 96vw);
      background:var(--card);
      backdrop-filter: blur(12px);
      border:1px solid var(--stroke);
      border-radius:24px;
      box-shadow:0 12px 36px rgba(15,23,42,.12);
      overflow:hidden;
    }

    .head{
      padding:22px 22px 12px;
      border-bottom:1px solid rgba(15,23,42,.06);
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
    }

    .body{ padding:22px; }
    .block{
      background:#fff; border:1px solid #e8eaf3; border-radius:16px;
      box-shadow:0 10px 24px rgba(15,23,42,.06); padding:18px;
    }

    .form-label{ font-weight:700; color:#1f2937; }
    .form-control{
      background:#fff; color:#0f172a; border:1px solid #dfe3f3;
    }
    .form-control::placeholder{ color:#9aa3b8; }
    .form-control:focus{
      border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18);
    }

    .btn-brand{
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      border:none; color:#fff; font-weight:800;
      box-shadow:0 10px 24px rgba(91,140,255,.25);
    }
    .btn-ghost{
      border:1px solid #dfe3f3; background:#fff; font-weight:700; color:#334155;
    }

    .grid{ display:grid; gap:14px; grid-template-columns: repeat(2,1fr); }
    @media (max-width: 720px){ .grid{ grid-template-columns: 1fr; } }

    .mb-18{ margin-bottom:18px; }
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="page">
    <div class="card-glass">
      <div class="head">
        <h1 class="title">แก้ไขข้อมูลสมาชิก</h1>
        <a href="users.php" class="btn btn-ghost">← กลับหน้ารายชื่อสมาชิก</a>
      </div>

      <div class="body">
        <?php if (isset($error) && $error): ?>
          <div class="alert alert-danger mb-18"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="block">
          <!-- คงชื่อฟิลด์/เมธอดเดิมทุกอย่าง -->
          <form method="post" class="row g-3">
            <div class="grid">
              <div>
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       required value="<?= htmlspecialchars($user['username']) ?>">
              </div>

              <div>
                <label class="form-label">Fullname</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($user['full_name']) ?>">
              </div>

              <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       required value="<?= htmlspecialchars($user['email']) ?>">
              </div>
              <div></div><!-- เว้นช่องให้กริดบาลานซ์ -->
            </div>

            <hr class="my-2">

            <div class="grid">
              <div>
                <label class="form-label">
                  รหัสผ่านใหม่
                  <small class="text-muted">(ถ้าไม่ต้องการเปลี่ยนให้เว้นว่าง)</small>
                </label>
                <input type="password" name="password" class="form-control">
              </div>

              <div>
                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="confirm_password" class="form-control">
              </div>
            </div>

            <div class="mt-3 d-flex gap-2">
              <button type="submit" class="btn btn-brand">บันทึกข้อมูล</button>
              <a href="users.php" class="btn btn-ghost">ยกเลิก</a>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
