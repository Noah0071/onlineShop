<?php
session_start(); // เริ่ม session
require_once '../config.php'; // เชื่อมต่อฐานข้อมูล
require_once 'auth_admin.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body{background: linear-gradient(120deg, #edf2ffff 0%, #b6ccffff 100%);font-family:'Sarabun',sans-serif;}
    .register-card{background:#fff;border-radius:20px;padding:40px;box-shadow:0 10px 25px rgba(0,0,0,.1);}
    .form-label{font-weight:bold;}
    .btn-primary{border-radius:30px;}
</style>
</head>

<body>
<div class="container my-5">
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="register-card">
<h2>ระบบผู้ดูแลระบบ</h2>
<p class="mb-4">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?></p>
<div class="row">
<div class="col-md-4 mb-3">
<a href="products.php" class="btn btn-primary w-100">จัดการสินค้า</a>
</div>
<div class="col-md-4 mb-3">
<a href="orders.php" class="btn btn-success w-100">จัดการคำสั่งซื้อ </a>
</div>
<div class="col-md-4 mb-3">
<a href="users.php" class="btn btn-warning w-100">จัดการสมาชิก</a>
</div>
<div class="col-md-4 mb-3">
<a href="categories.php" class="btn btn-dark w-100">จัดการหมวดหมู่</a>
</div>
</div>
<a href="../logout.php" class="btn btn-secondary mt-3">ออกจากระบบ</a>
    </div>
  </div>
</div>
</div>
</body>
</html>