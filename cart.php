<?php
require 'session_timeout.php'; 
require 'config.php';
// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
  $product_id = intval($_POST['product_id']);
  $quantity = max(1, intval($_POST['quantity'] ?? 1)); // อย่างน้อย 1 ชิ้น

  // ตรวจสอบว่าสินค้าอยู่ในตะกร้าแล้วหรือยัง
  $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
  $stmt->execute([$user_id, $product_id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($item) {
    // มีแล้ว เพิ่มจำนวน
    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE cart_id = ?");
    $stmt->execute([$quantity, $item['cart_id']]);
  } else {
    // ยังไม่มี เพิ่มใหม่
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $quantity]);
  }
  header("Location: cart.php");
  exit;
}

// ลบสินค้าออกจากตะกร้า
if (isset($_GET['remove'])) {
  $cart_id = intval($_GET['remove']);
  $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
  $stmt->execute([$cart_id, $user_id]);
  header("Location: cart.php");
  exit;
}

// ดึงรายการสินค้าในตะกร้า
$stmt = $conn->prepare(
  "SELECT cart.cart_id, cart.quantity, products.product_name, products.price
     FROM cart
     JOIN products ON cart.product_id = products.product_id
     WHERE cart.user_id = ?"
);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณราคารวม
$total = 0;
foreach ($items as $item) {
  $total += $item['quantity'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <title>สมัครสมาชิก</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    :root {
      --bg: #f6f7fb;
      --ink: #0f172a;
      --muted: #64748b;
      --card: #ffffffcc;
      /* glass */
      --stroke: #e6e8ef;
      --brand: #5b8cff;
      /* ฟ้าพาสเทล */
      --brand2: #8b5bff;
      /* ม่วงพาสเทล */
    }

    html,
    body {
      height: 100%
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
    }

    /* พื้นหลังนามธรรม (abstract background) โทนสว่าง */
    .bg-abstract {
      position: fixed;
      inset: 0;
      z-index: -1;
      pointer-events: none;
      background:
        radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
        radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
        radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);
    }

    .bg-abstract::after {
      content: "";
      position: absolute;
      inset: 0;
      background: conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
      filter: blur(28px);
      opacity: .6;
    }

    .page {
      min-height: 100svh;
      display: grid;
      place-items: center;
      padding: 24px;
    }

    /* การ์ดแบบกลาสโมร์ฟิค */
    .card-glass {
      width: min(920px, 96vw);
      background: var(--card);
      backdrop-filter: blur(12px);
      border: 1px solid var(--stroke);
      border-radius: 24px;
      box-shadow: 0 12px 36px rgba(15, 23, 42, .12);
      overflow: hidden;
    }

    .head {
      padding: 22px 22px 12px;
      border-bottom: 1px solid rgba(15, 23, 42, .06);
    }

    .title {
      margin: 0;
      font-weight: 900;
      letter-spacing: .2px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
    }

    .sub {
      margin: 6px 0 0;
      color: var(--muted);
    }

    .body {
      padding: 22px;
    }

    .grid {
      display: grid;
      gap: 14px;
      grid-template-columns: repeat(2, 1fr);
    }

    @media (max-width: 720px) {
      .grid {
        grid-template-columns: 1fr;
      }
    }

    .form-label {
      font-weight: 700;
      color: #1f2937;
    }

    .form-control {
      background: #fff;
      border: 1px solid #dfe3f3;
      color: #0f172a;
    }

    .form-control::placeholder {
      color: #9aa3b8;
    }

    .form-control:focus {
      border-color: #b9c7ff;
      box-shadow: 0 0 0 .25rem rgba(91, 140, 255, .18);
    }

    .btn-brand {
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      border: none;
      color: #fff;
      font-weight: 800;
      box-shadow: 0 10px 24px rgba(91, 140, 255, .25);
    }

    .btn-brand:hover {
      filter: brightness(1.05);
    }

    .alert {
      border-radius: 14px;
      border: 1px solid #ffe1e1;
      background: #fff5f5;
      color: #b42318;
    }
  </style>

  <style>
    /* ===== Cart overrides on top of register page theme ===== */
    .cart-hero {
      display: flex;
      align-items: center;
      gap: .75rem;
      font-weight: 800;
      letter-spacing: .2px;
    }

    .cart-hero .chip {
      font-size: .8rem;
      padding: .25rem .6rem;
      border-radius: 999px;
      border: 1px dashed rgba(0, 0, 0, .08);
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      backdrop-filter: blur(8px);
      color: #fff;
    }

    .glass-card {
      background: var(--card, #ffffffcc);
      backdrop-filter: blur(12px);
      border: 1px solid var(--stroke, #e5e7eb);
      border-radius: 20px;
      box-shadow: 0 14px 40px rgba(2, 6, 23, .08);
      overflow: hidden;
    }

    .cart-table th {
      text-transform: uppercase;
      font-size: .76rem;
      letter-spacing: .05em;
      color: var(--muted, #64748b);
      border-bottom: 1px solid var(--stroke, #e5e7eb) !important;
    }

    .cart-table td {
      border-bottom: 1px dashed var(--stroke, #e5e7eb) !important;
    }

    .summary {
      position: sticky;
      top: 1rem;
    }

    .empty-state {
      text-align: center;
      padding: 48px 20px;
    }

    .empty-state .emoji {
      font-size: 54px;
    }

    .btn-pay {
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      color: #fff;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      table.cart-table thead {
        display: none;
      }

      table.cart-table tbody tr {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: .25rem .75rem;
        padding: .75rem 1rem;
      }

      table.cart-table tbody td {
        border: 0 !important;
        padding: .35rem .25rem !important;
      }

      table.cart-table tbody td:nth-child(1) {
        grid-column: 1 / -1;
        font-weight: 700;
      }

      table.cart-table tbody td:nth-child(2) {
        justify-self: center;
      }

      table.cart-table tbody td:nth-child(3),
      table.cart-table tbody td:nth-child(4) {
        justify-self: end;
      }

      table.cart-table tbody td:nth-child(5) {
        grid-column: 1 / -1;
        justify-self: end;
      }
    }
  </style>

</head>

<body>

  <div class="bg-abstract"></div>
  <main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="cart-hero">
        <i class="bi bi-bag-check-fill"></i>
        <span>ตะกร้าสินค้า</span>
        <span class="chip">Cart</span>
      </div>
      <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับไปเลือกสินค้า</a>
    </div>

    <?php if (count($items) === 0): ?>
      <div class="glass-card empty-state">
        <div class="emoji">🛒</div>
        <h5 class="mb-1">ตะกร้าของคุณยังว่างอยู่</h5>
        <p class="text-muted mb-3">เริ่มช้อปสินค้าได้เลย เลือกสินค้าที่ต้องการแล้วกด “เพิ่มลงตะกร้า”</p>
        <a href="index.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> ไปหน้าเลือกสินค้า</a>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="glass-card">
            <div class="p-3 pb-0 d-flex align-items-center justify-content-between">
              <div class="fw-semibold"><i class="bi bi-list-ul"></i> รายการในตะกร้า</div>
              <small class="text-muted"><?= count($items) ?> รายการ</small>
            </div>
            <div class="table-responsive">
              <table class="table cart-table align-middle m-0">
                <thead>
                  <tr>
                    <th>ชื่อสินค้า</th>
                    <th class="text-center" style="width:120px;">จำนวน</th>
                    <th class="text-end" style="width:160px;">ราคาต่อหน่วย</th>
                    <th class="text-end" style="width:160px;">ราคารวม</th>
                    <th class="text-center" style="width:120px;">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <tr>
                      <td class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></td>
                      <td class="text-center"><?= $item['quantity'] ?></td>
                      <td class="text-end"><?= number_format($item['price'], 2) ?></td>
                      <td class="text-end"><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                      <td class="text-center">
                        <a href="cart.php?remove=<?= $item['cart_id'] ?>" class="btn btn-sm btn-outline-danger"
                          onclick="return confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')">
                          <i class="bi bi-trash3"></i> ลบ
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" class="text-end"><strong>รวมทั้งหมด:</strong></td>
                    <td class="text-end"><strong><?= number_format($total, 2) ?> บาท</strong></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="glass-card p-3 summary">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold"><i class="bi bi-receipt-cutoff"></i> สรุปคำสั่งซื้อ</div>
              <span class="badge text-bg-light">รวมสุทธิ</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">ยอดชำระ</span>
              <span class="fw-bold"><?= number_format($total, 2) ?> บาท</span>
            </div>
            <div class="small text-muted mt-1">* ยังไม่รวมค่าจัดส่ง (ถ้ามี)</div>
            <hr class="my-3" />
            <div class="d-grid gap-2">
              <a href="checkout.php" class="btn btn-pay btn-lg"><i class="bi bi-credit-card-2-front"> </i> ดำเนินการชำระเงิน</a>
              <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-plus-circle"></i> เลือกสินค้าเพิ่ม</a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>