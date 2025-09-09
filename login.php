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
        body {
            background: linear-gradient(120deg, #edf2ffff 0%, #b6ccffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .login-card {
            max-width: 420px;
            margin: auto;
            margin-top: 90px;
            padding: 38px 32px 32px 32px;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0px 12px 32px rgba(30,63,145,0.09);
        }
        .login-card h3 {
            text-align: center;
            margin-bottom: 28px;
            font-weight: 700;
            color: #222e3a;
            letter-spacing: 0.5px;
        }
        .form-label {
            font-weight: 500;
            color: #222e3a;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
            font-size: 1.08em;
        }
        .btn-primary {
            width: 100%;
            border-radius: 12px;
            background: linear-gradient(90deg,#3349a4 60%,#5e7be2 100%);
            border: none;
            font-weight: 600;
            padding: 12px;
            font-size: 1.08em;
            box-shadow: 0 2px 8px #3349a430;
        }
        .btn-primary:hover {
            background: #3349a4;
        }
        .btn-link {
            display: block;
            text-align: center;
            margin-top: 14px;
            color: #3349a4;
            font-weight: 500;
            font-size: 1.04em;
        }
        .btn-link:hover {
            color: #222e3a;
        }
        .alert {
            max-width: 420px;
            margin: 20px auto;
            border-radius: 12px;
            font-size: 1.08em;
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
        <div class="alert alert-success text-center shadow-sm"> ✅ สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center shadow-sm">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="login-card">
        <h3>🔑 เข้าสู่ระบบ</h3>
        <form method="post" class="row g-3">
            <div class="col-12">
                <label for="username_or_email" class="form-label">ชื่อผู้ใช้หรืออีเมล</label>
                <input type="text" name="username_or_email" id="username_or_email" class="form-control" required>
            </div>
            <div class="col-12">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                <a href="register.php" class="btn btn-link">สมัครสมาชิก</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
