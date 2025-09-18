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

if (!isset($_GET['id'])) {
    header('Location: test_index.php');
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']);

//เตรียมรูป
$img = !empty($product['image'])
? 'product_images/' . rawurlencode($product['image'])
: 'product_images/no-image.jpg';

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>รายละเอียดสินค้า</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- โทนสว่าง-มินิมอล + Glass + พื้นหลังนามธรรม (ให้เหมือน register.php) -->
  <style>
    :root{
      --bg: #f6f7fb;
      --ink: #0f172a;
      --muted: #64748b;
      --card: #ffffffcc;   /* glass */
      --stroke: #e6e8ef;
      --brand: #5b8cff;    /* ฟ้าพาสเทล */
      --brand2:#8b5bff;    /* ม่วงพาสเทล */
    }

    html,body{height:100%}
    body{
      margin:0; min-height:100vh; overflow-x:hidden;
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
      color: var(--ink); background: var(--bg); padding:24px 12px;
    }

    /* abstract background */
    .bg-abstract{
      position:fixed; inset:0; z-index:-1; pointer-events:none;
      background:
        radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
        radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
        radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);
    }
    .bg-abstract::after{
      content:""; position:absolute; inset:0;
      background: conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
      filter: blur(28px); opacity:.6;
    }

    /* ปุ่มกลับ */
    .btn-back{
      border:1px solid #dfe3f3; background:#fff; color:#334155; font-weight:700;
      border-radius:12px; padding:8px 14px; box-shadow:0 8px 20px rgba(15,23,42,.08);
      transition:.15s ease;
    }
    .btn-back:hover{ transform:translateY(-1px); }

    /* การ์ดรายละเอียดแบบ glass */
    .detail-card{
      width:100%; max-width:640px; margin:70px auto 32px; padding:28px 24px;
      background: var(--card);
      backdrop-filter: blur(12px);
      border:1px solid var(--stroke);
      border-radius:24px;
      box-shadow:0 12px 36px rgba(15,23,42,.12);
    }
    .detail-card h3{
      text-align:center; margin:0 0 6px; font-weight:900; letter-spacing:.2px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
    }
    .detail-card h6{
      text-align:center; color:var(--muted); margin:0 0 16px; font-weight:700;
    }
    .detail-card .card-text{ color:#334155; }
    .detail-card p{ margin-bottom:10px; }

    label{ font-weight:700; color:#1f2937; margin-right:8px; }

    input[type="number"]{
      background:#fff; color:#0f172a; border:1px solid #dfe3f3; border-radius:12px;
      padding:10px 12px; width:120px; outline:none;
    }
    input[type="number"]::placeholder{ color:#9aa3b8; }
    input[type="number"]:focus{
      border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18);
    }

    .btn-buy{
      width:100%; border:none; color:#fff; font-weight:800; border-radius:12px; padding:12px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      box-shadow:0 12px 24px rgba(91,140,255,.25);
      transition:.15s ease;
    }
    .btn-buy:hover{ filter:brightness(1.05); transform:translateY(-1px); }

    .alert{
      border-radius:14px; border:1px solid #cfefff;
      background:#f3fbff; color:#0b4a6f;
    }

    @media (max-width:576px){
      .detail-card{ margin-top:48px; padding:22px 18px; }
      input[type="number"]{ width:100%; margin-top:8px; }
    }
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <a href="index.php" class="btn-back">← กลับหน้ารายการสินค้า</a>

  <div class="detail-card">

  <img src="<?= $img ?>">

    <h3><?= htmlspecialchars($product['product_name']) ?></h3>
    <h6>หมวดหมู่: <?= htmlspecialchars($product['category_name']) ?></h6>

    <div class="card-text">
      <p><strong>ราคา:</strong> <?= htmlspecialchars($product['price']) ?> บาท</p>
      <p><strong>คงเหลือ:</strong> <?= htmlspecialchars($product['stock']) ?> ชิ้น</p>
      <?php if (!empty($product['description'])): ?>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      <?php endif; ?>
    </div>

    <?php if ($isLoggedIn): ?>
      <form action="cart.php" method="post" class="mt-3">
        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
        <label for="quantity">จำนวน:</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required>
        <button type="submit" class="btn-buy mt-2">เพิ่มในตะกร้า</button>
      </form>
    <?php else: ?>
      <div class="alert mt-3 text-center">กรุณาเข้าสู่ระบบเพื่อสั่งซื้อสินค้า</div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
