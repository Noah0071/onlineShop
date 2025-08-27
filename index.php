<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>หน้าแรก</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
<style>
  body{background:linear-gradient(135deg,#a1c4fd 0%,#c2e9fb 100%);font-family:'Sarabun',sans-serif;min-height:100vh;}
  .register-card{background:#fff;border-radius:20px;padding:40px;box-shadow:0 10px 25px rgba(0,0,0,.1);}
  .btn-rounded{border-radius:30px;padding:.6rem 1.25rem}
  .muted{color:#6c757d}
</style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        <div class="register-card">
          <h2 class="mb-4 fw-bold">ยินดีต้อนรับ</h2>

          <p class="fs-5">
            ผู้ใช้ :
            <?= htmlspecialchars($_SESSION['username']) ?>
            <span class="muted">(<?= $_SESSION['role'] ?>)</span>
          </p>

          <a href="logout.php" class="btn btn-success btn-rounded">ออกจากระบบ</a>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
