<?php 
    session_start();
    require_once 'config.php';

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usernameOrEmail = trim($_POST['username_or_email']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] === 'admin'){
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<style>
  :root{ --bg:#f6f7fb; --ink:#0f172a; --muted:#64748b; --card:#ffffffcc; --stroke:#e6e8ef; --brand:#5b8cff; --brand2:#8b5bff; }
  html,body{height:100%}
  body{ margin:0;background:var(--bg);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Thai","Prompt",Arial,sans-serif; }
  .bg-abstract{ position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
      radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
      radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);
  }
  .bg-abstract::after{ content:""; position:absolute; inset:0; background:conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%); filter:blur(28px); opacity:.6; }
  .page{ min-height:100svh; display:grid; place-items:center; padding:24px; }
  .card-glass{ width:min(720px,96vw); background:var(--card); backdrop-filter:blur(12px); border:1px solid var(--stroke); border-radius:24px; box-shadow:0 12px 36px rgba(15,23,42,.12); overflow:hidden; }
  .head{ padding:22px 22px 12px; border-bottom:1px solid rgba(15,23,42,.06);}
  .title{ margin:0; font-weight:900; letter-spacing:.2px; background:linear-gradient(135deg,var(--brand),var(--brand2)); -webkit-background-clip:text; background-clip:text; color:transparent; font-size:clamp(1.2rem,.9rem+1.2vw,1.8rem);}
  .sub{ margin:6px 0 0; color:var(--muted);}
  .body{ padding:22px;}
  .form-label{ font-weight:700; color:#1f2937;}
  .form-control{ background:#fff; border:1px solid #dfe3f3; color:#0f172a;}
  .form-control::placeholder{ color:#9aa3b8;}
  .form-control:focus{ border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18);}
  .btn-brand{ background:linear-gradient(135deg,var(--brand),var(--brand2)); border:none; color:#fff; font-weight:800; box-shadow:0 10px 24px rgba(91,140,255,.25);}
  .btn-brand:hover{ filter:brightness(1.05);}
  .alert{ border-radius:14px; border:1px solid #ffe1e1; background:#fff5f5; color:#b42318;}
</style>

</head>
<body>
<div class="bg-abstract"></div>

<div class="page">
  <div class="card-glass">
    <div class="head">
      <h1 class="title">เข้าสู่ระบบ</h1>
      <p class="sub">กรอกชื่อผู้ใช้หรืออีเมลและรหัสผ่านเพื่อเข้าใช้งาน</p>
    </div>

    <div class="body">
      <?php if (!empty($error)): ?>
        <div class="alert mb-3">
          <?php if (is_array($error)): ?>
            <ul class="mb-0">
              <?php foreach ($error as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- เปลี่ยนเฉพาะชื่อฟิลด์ให้ตรงกับหลังบ้าน -->
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label for="username_or_email" class="form-label">ชื่อผู้ใช้หรืออีเมล</label>
          <input type="text" id="username_or_email" name="username_or_email" class="form-control"
                 placeholder="ชื่อผู้ใช้หรืออีเมล"
                 value="<?= htmlspecialchars($_POST['username_or_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label">รหัสผ่าน</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-brand btn-lg">เข้าสู่ระบบ</button>
          
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
