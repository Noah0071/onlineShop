<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เข้าสู่ระบบ</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
<style>
  body{background:linear-gradient(135deg,#a1c4fd 0%,#c2e9fb 100%);font-family:'Sarabun',sans-serif;min-height:100vh;}
  .register-card{background:#fff;border-radius:20px;padding:40px;box-shadow:0 10px 25px rgba(0,0,0,.1);}
  .btn-rounded{border-radius:30px;padding:.6rem 1.25rem}
  .form-label{font-weight:600}
</style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-xl-5">
        <div class="register-card">
          <h2 class="fw-bold mb-3">เข้าสู่ระบบ</h2>
          <p class="text-muted mb-4">กรอกชื่อผู้ใช้และรหัสผ่านเพื่อเข้าสู่ระบบ</p>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="post" action="login.php" autocomplete="off">
            <div class="mb-3">
              <label class="form-label" for="username">ชื่อผู้ใช้</label>
              <input type="text" class="form-control form-control-lg" id="username" name="username" required>
            </div>
            <div class="mb-4">
              <label class="form-label" for="password">รหัสผ่าน</label>
              <input type="password" class="form-control form-control-lg" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-rounded">เข้าสู่ระบบ</button>
          </form>

          <hr class="my-4">
          <div class="text-center">
            ยังไม่มีบัญชี? <a href="register.php" class="text-decoration-none">สมัครสมาชิก</a>
          </div>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
