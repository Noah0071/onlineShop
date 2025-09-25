<?php
session_start();
require 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['user_id'])) { // session ของ user_id
  header("Location: login.php"); // หน้า login
  exit;
}

// เก็บ user_id
$user_id = $_SESSION['user_id']; // ตัวแปรเก็บ user_id

// -----------------------------
// ดึงคำสั่งซื้อของผู้ใช้
// -----------------------------
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------
// ฟังก์ชันดึงรายการสินค้าในคำสั่งซื้อ
// -----------------------------
function getOrderItems($conn, $order_id) {
  $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.product_name
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.product_id
                         WHERE oi.order_id = ?");
  $stmt->execute([$order_id]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// -----------------------------
// ฟังก์ชันดึงข้อมูลจัดส่ง
// -----------------------------
function getShippingInfo($conn, $order_id) {
  $stmt = $conn->prepare("SELECT * FROM shipping WHERE order_id = ?"); // shipping table
  $stmt->execute([$order_id]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ประวัติการสั่งซื้อ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
  <h2>ประวัติการสั่งซื้อ</h2>
  <a href="index.php" class="btn btn-secondary mb-3">← กลับหน้าหลัก</a>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">ทำรายการสั่งซื้อเรียบร้อยแล้ว</div>
  <?php endif; ?>

  <?php if (count($orders) === 0): ?>
    <div class="alert alert-warning">คุณยังไม่เคยสั่งซื้อสินค้า</div>
  <?php else: ?>
    <?php foreach ($orders as $order): ?>
      <div class="card mb-4">
        <div class="card-header bg-light">
          <strong>รหัสคำสั่งซื้อ:</strong> #<?= htmlspecialchars($order['order_id']) ?> |
          <strong>วันที่:</strong> <?= htmlspecialchars($order['order_date']) ?> |
          <strong>สถานะ:</strong> <?= htmlspecialchars(ucfirst($order['status'])) ?>
        </div>
        <div class="card-body">
          <ul class="list-group mb-3">
            <?php foreach (getOrderItems($conn, $order['order_id']) as $item): ?>
              <li class="list-group-item">
                <?= htmlspecialchars($item['product_name']) ?>
                × <?= (int)$item['quantity'] ?>
                = <?= number_format($item['quantity'] * $item['price'], 2) ?> บาท
              </li>
            <?php endforeach; ?>
          </ul>

          <p><strong>รวมทั้งสิ้น:</strong> <?= number_format($order['total_amount'], 2) ?> บาท</p>

          <?php $shipping = getShippingInfo($conn, $order['order_id']); ?>
          <?php if ($shipping): ?>
            <p><strong>ที่อยู่จัดส่ง:</strong>
              <?= htmlspecialchars($shipping['address']) ?>,
              <?= htmlspecialchars($shipping['city']) ?>
              <?= htmlspecialchars($shipping['postal_code']) ?>
            </p>
            <p><strong>สถานะการจัดส่ง:</strong> <?= htmlspecialchars(ucfirst($shipping['shipping_status'])) ?></p>
            <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($shipping['phone']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
