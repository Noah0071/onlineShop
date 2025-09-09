<?php
session_start();
require_once 'config.php';


$product_id = $_GET['id'];

$stmt = $conn->prepare("SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?");
    $stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!isset($_GET['id'])){
    header('Location: test_index.php');
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียดสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #edf2ffff 0%, #b6ccffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .detail-card {
            max-width: 480px;
            margin: auto;
            margin-top: 70px;
            padding: 35px 30px 30px 30px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.10);
        }
        .detail-card h3 {
            text-align: center;
            margin-bottom: 18px;
            font-weight: 700;
            color: #0a2a66;
        }
        .detail-card h6 {
            text-align: center;
            color: #1e3f91;
            margin-bottom: 18px;
        }
        .detail-card .card-text {
            font-size: 1.08em;
            color: #333;
        }
        .detail-card p {
            margin-bottom: 10px;
        }
        .btn-success {
            width: 100%;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px;
        }
        .btn-secondary {
            border-radius: 10px;
            margin-bottom: 18px;
        }
        .alert {
            border-radius: 10px;
        }
        label {
            font-weight: 500;
            color: #1e3f91;
        }
        input[type="number"] {
            border-radius: 10px;
            padding: 8px;
            width: 100px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <a href="index.php" class="btn btn-secondary">← กลับหน้ารายการสินค้า</a>
    <div class="detail-card">
        <h3><?= htmlspecialchars($product['product_name']) ?></h3>
        <h6>หมวดหมู่: <?= htmlspecialchars($product['category_name']) ?></h6>
        <div class="card-text">
            <p><strong>ราคา:</strong> <?= htmlspecialchars($product['price']) ?> บาท</p>
            <p><strong>คงเหลือ:</strong> <?= htmlspecialchars($product['stock']) ?>  ชิ้น </p>
        </div>
        <?php if ($isLoggedIn): ?>
            <form action="cart.php" method="post" class="mt-3">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <label for="quantity">จำนวน:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required>
                <button type="submit" class="btn btn-success mt-2">เพิ่มในตะกร้า</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info mt-3 text-center">กรุณาเข้าสู่ระบบเพื่อสั่งซื้อสินค้า</div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>