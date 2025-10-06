<?php
require 'session_timeout.php'; 
require_once 'config.php';

$error = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $fname     = trim($_POST['fname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    // ตรวจอินพุตพื้นฐาน (ยึดตามไฟล์เดิม)
    if ($username === '' || $fname === '' || $email === '' || $password === '' || $cpassword === '') {
        $error[] = "กรุณากรอกให้ครบทุกช่อง";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error[] = "กรุณากรอกอีเมลให้ถูกต้อง";
    } elseif ($password !== $cpassword) {
        $error[] = "รหัสผ่านไม่ตรงกัน";
    } elseif (strlen($password) < 6) {
        $error[] = "รหัสผ่านควรมีอย่างน้อย 6 ตัวอักษร";
    }

    if (empty($error)) {
        try {
            // เช็คซ้ำ (อิงลอจิกเดิม)
            $sql  = "SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error[] = "ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql  = "INSERT INTO users (username, full_name, email, password, role) 
                         VALUES (?, ?, ?, ?, 'member')";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $fname, $email, $hashedPassword]);

                header("Location: login.php?register=success");
                exit;
            }
        } catch (PDOException $e) {
            $error[] = "ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง";
            // error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<title>สมัครสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />

<style>
  :root{
    --bg: #f6f7fb;
    --ink: #0f172a;
    --muted: #64748b;
    --card: #ffffffcc;          /* glass */
    --stroke: #e6e8ef;
    --brand: #5b8cff;           /* ฟ้าพาสเทล */
    --brand2: #8b5bff;          /* ม่วงพาสเทล */
  }

  html,body{height:100%}
  body{
    margin:0; background:var(--bg); color:var(--ink);
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
  }

  /* พื้นหลังนามธรรม (abstract background) โทนสว่าง */
  .bg-abstract{
    position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
      radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
      radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);
  }
  .bg-abstract::after{
    content:"";
    position:absolute; inset:0;
    background: conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
    filter: blur(28px); opacity:.6;
  }

  .page{
    min-height:100svh; display:grid; place-items:center; padding:24px;
  }

  /* การ์ดแบบกลาสโมร์ฟิค */
  .card-glass{
    width:min(920px, 96vw);
    background: var(--card);
    backdrop-filter: blur(12px);
    border:1px solid var(--stroke);
    border-radius:24px;
    box-shadow: 0 12px 36px rgba(15,23,42,.12);
    overflow: hidden;
  }

  .head{
    padding:22px 22px 12px;
    border-bottom: 1px solid rgba(15,23,42,.06);
  }
  .title{
    margin:0; font-weight:900; letter-spacing:.2px;
    background: linear-gradient(135deg, var(--brand), var(--brand2));
    -webkit-background-clip: text; background-clip: text; color: transparent;
    font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
  }
  .sub{ margin:6px 0 0; color:var(--muted); }

  .body{ padding:22px; }
  .grid{ display:grid; gap:14px; grid-template-columns: repeat(2,1fr); }
  @media (max-width: 720px){ .grid{ grid-template-columns: 1fr; } }

  .form-label{ font-weight:700; color:#1f2937; }
  .form-control{
    background:#fff;
    border:1px solid #dfe3f3;
    color:#0f172a;
  }
  .form-control::placeholder{ color:#9aa3b8; }
  .form-control:focus{
    border-color:#b9c7ff;
    box-shadow: 0 0 0 .25rem rgba(91,140,255,.18);
  }

  .btn-brand{
    background: linear-gradient(135deg, var(--brand), var(--brand2));
    border: none; color: #fff; font-weight:800;
    box-shadow: 0 10px 24px rgba(91,140,255,.25);
  }
  .btn-brand:hover{ filter:brightness(1.05); }

  .alert{
    border-radius:14px; border:1px solid #ffe1e1;
    background:#fff5f5; color:#b42318;
  }
</style>
</head>
<body>
<div class="bg-abstract"></div>

<div class="page">
  <div class="card-glass">
    <div class="head">
      <h1 class="title">สมัครสมาชิก</h1>
      <p class="sub">กรอกข้อมูลให้ครบถ้วนเพื่อสร้างบัญชีของคุณ</p>
    </div>

    <div class="body">
      <?php if (!empty($error)): ?>
        <div class="alert mb-3">
          <ul class="mb-0">
            <?php foreach ($error as $e): ?>
              <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- คงฟิลด์/ชื่อ name เดิมทุกอย่าง (ไม่เพิ่ม/ไม่ลด) -->
      <form method="post" autocomplete="off">
        <div class="grid">
          <div>
            <label for="username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="ตั้งชื่อผู้ใช้"
                   value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div>
            <label for="fname" class="form-label">ชื่อ - นามสกุล</label>
            <input type="text" id="fname" name="fname" class="form-control" placeholder="ชื่อ-นามสกุล"
                   value="<?= htmlspecialchars($_POST['fname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div>
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <!-- ช่องว่างเพื่อบาลานซ์กริด (ไม่เพิ่มฟีเจอร์ใหม่) -->
          <div class="d-none d-md-block"></div>

          <div>
            <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
          </div>

          <div>
            <label for="cpassword" class="form-label">ยืนยันรหัสผ่าน</label>
            <input type="password" id="cpassword" name="cpassword" class="form-control" placeholder="พิมพ์รหัสผ่านอีกครั้ง" required>
          </div>
        </div>

        <div class="mt-4 d-grid">
          <button type="submit" class="btn btn-brand btn-lg mb-1">สมัครสมาชิก</button>
          <a href="login.php" class="btn btn-brand btn-lg">เข้าสู่ระบบ</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>