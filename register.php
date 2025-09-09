<?php
require_once 'config.php'; 
$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $fname     = trim($_POST['fname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    // ตรวจอินพุตพื้นฐาน
    if ($username === '' || $fname === '' || $email === '' || $password === '' || $cpassword === '') {
        $error[] = "กรุณากรอกให้ครบทุกช่อง";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error[] = "กรุณากรอกอีเมลให้ถูกต้อง";
    } elseif ($password !== $cpassword) {
        $error[] = "รหัสผ่านไม่ตรงกัน";
    } elseif (strlen($password) < 8) {
        $error[] = "รหัสผ่านควรมีอย่างน้อย 8 ตัวอักษร";
    }

    if (empty($error)) {
        try {
            
            $sql  = "SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error[] = "ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql  = "INSERT INTO users (username, full_name, email, password, role) 
                        VALUES (?, ?, ?, ?, 'admin')";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $fname, $email, $hashedPassword]);

                header("Location: login.php?register=success");
                exit;
            }
        } catch (PDOException $e) {
            // ข้อความนี้ควร log จริงๆ แล้วแสดงข้อความกลางๆ ให้ผู้ใช้
            $error[] = "ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง";
            // error_log($e->getMessage());
        }
    }
}
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
        <h2 class="text-center mb-4">สมัครสมาชิก</h2>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
            <?php foreach ($error as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" action="">
        <div class="row g-3">
            <div class="col-md-6">
            <label for="username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้"
                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
            <label for="fname" class="form-label">ชื่อ - นามสกุล</label>
            <input type="text" class="form-control" id="fname" name="fname" placeholder="ชื่อ - นามสกุล"
                value="<?= htmlspecialchars($_POST['fname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
            <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
            </div>
            <div class="col-md-6">
            <label for="cpassword" class="form-label">ยืนยันรหัสผ่าน</label>
            <input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="ยืนยันรหัสผ่าน" required>
            </div>
        </div>
        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-lg">สมัครสมาชิก</button>
            <a href="login.php" class="btn btn-outline-secondary">กลับไปหน้าล็อกอิน</a>
        </div>
        </form>

    </div>
    </div>
</div>
</div>
</body>
</html>
