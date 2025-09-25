<?php
session_start();
require 'config.php';
// ตรวจสอบกำรล็อกอิน
if (!isset($_SESSION['user_id'])) { // TODO: ใส่ session ของ user
    header("Location: login.php"); // TODO: หน้ำ login
    exit;
}
$user_id = $_SESSION['user_id']; // TODO: กำหนด user_id

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) { // TODO: product_id
    $product_id = intval($_POST['product_id']); // TODO: product_id
    $quantity = max(1, intval($_POST['quantity'] ?? 1)); // ตรวจสอบจำนวนอย่างน้อย 1

    // ตรวจสอบว่าสินค้าอยู่ในตะกร้าแล้วหรือยัง
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?"); // TODO: ใส่ชื่อตารางตะกร้า
    $stmt->execute([$user_id, $product_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        // ถ้ามีแล้ว ให้เพิ่มจำนวน
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE cart_id = ?");
        // TODO: ชื่อตาราง, primary key ของตะกร้า
        $stmt->execute([$quantity, $item['cart_id']]);
    } else {
        // ถ้ายังไม่มี ให้เพิ่มใหม่
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
    header("Location: cart.php"); // TODO: กลับมาที่ cart
    exit;
}

// -----------------------------
// ลบสินค้าออกจากตะกร้า
// -----------------------------
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    // TODO: ชื่อตารางตะกร้า, primary key
    $stmt->execute([$cart_id, $user_id]);
    header("Location: cart.php"); // TODO: กลับมาที่ cart
    exit;
}

// -----------------------------
// ดึงรายการสินค้าในตะกร้า
// -----------------------------
$stmt = $conn->prepare(
    "SELECT cart.cart_id, cart.quantity, products.product_name, products.price
     FROM cart
     JOIN products ON cart.product_id = products.product_id
     WHERE cart.user_id = ?"
);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------
// คำนวณราคารวม
// -----------------------------
$total = 0;
foreach ($items as $item) {
    $total += $item['quantity'] * $item['price']; // TODO: quantity * price
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ตะกร้าสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-4">
    <h2>ตะกร้าสินค้า</h2>
    <a href="index.php" class="btn btn-secondary mb-3">← กลับไปเลือกสินค้า</a> <!-- TODO: หน้า index -->
    <?php if (count($items) === 0): ?>
        <div class="alert alert-warning">ตะกร้าของคุณยังว่างอยู่</div> <!-- TODO: ข้อความกรณีตะกร้าว่าง -->
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>จำนวน</th>
                    <th>ราคาต่อหน่วย</th>
                    <th>ราคารวม</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td> <!-- TODO: product_name -->
                        <td><?= $item['quantity'] ?></td> <!-- TODO: quantity -->
                        <td><?= number_format($item['price'], 2) ?></td> <!-- TODO: price -->
                        <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td> <!-- TODO: price * quantity -->
                        <td>
                            <a href="cart.php?remove=<?= $item['cart_id'] ?>" class="btn btn-sm btn-danger"
                                onclick="return confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')">ลบ</a>
                            <!-- TODO: cart_id -->
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>รวมทั้งหมด:</strong></td>
                    <td colspan="2"><strong><?= number_format($total, 2) ?> บาท</strong></td>
                </tr>
            </tbody>
        </table>
        <a href="checkout.php" class="btn btn-success">สั่งซื้อสินค้า</a> <!-- TODO: checkout -->
    <?php endif; ?>
</body>

</html>