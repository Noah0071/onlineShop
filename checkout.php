<?php
require 'session_timeout.php'; 
require 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$errors = [];

// ดึงรายการสินค้าในตะกร้า
$stmt = $conn->prepare("SELECT cart.cart_id, cart.product_id, cart.quantity, products.product_name, products.price
FROM cart
JOIN products ON cart.product_id = products.product_id
WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้าไม่มีสินค้าในตะกร้า
if (!$items) {
    echo "<h3>ไม่มีสินค้าในตะกร้า</h3>";
    exit;
}

// คำนวณราคารวม
$total = 0;
foreach ($items as $item) {
    $total += $item['quantity'] * $item['price'];
}

// เมื่อผู้ใช้กดยืนยันคำสั่งซื้อ (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');

    // ตรวจสอบการกรอกข้อมูล
    if ($address === '' || $city === '' || $postal_code === '' || $phone === '') {
        $errors[] = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }

    if (empty($errors)) {
        // เริ่ม transaction
        $conn->beginTransaction();
        try {
            // บันทึกข้อมูลคำสั่งซื้อ
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$user_id, $total]);
            $order_id = $conn->lastInsertId();

            // บันทึกรายการสินค้าใน order_items
            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            // บันทึกข้อมูลการจัดส่ง
            $stmt = $conn->prepare("INSERT INTO shipping (order_id, address, city, postal_code, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $address, $city, $postal_code, $phone]);

            // ล้างตะกร้าสินค้า
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // ยืนยันการบันทึก
            $conn->commit();
            header("Location: orders.php?success=1");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
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


  <style>
    /* Extra polish for checkout page */
    .checkout-hero{display:flex;align-items:center;gap:.75rem;font-weight:800;letter-spacing:.2px;}
    .checkout-hero .chip{font-size:.8rem;padding:.25rem .6rem;border-radius:999px;border:1px dashed rgba(0,0,0,.08);
      background: rgba(255,255,255,.6);backdrop-filter: blur(8px);}
    .glass-card{background: var(--card, #ffffffcc);backdrop-filter: blur(12px);border: 1px solid var(--stroke, #e5e7eb);
      border-radius: 20px;box-shadow: 0 14px 40px rgba(2,6,23,.08);overflow:hidden;}
    .form-floating>.form-control{border-radius:12px;}
    .summary{position:sticky; top:1rem;}
    .summary .line{display:flex;justify-content:space-between;align-items:center;margin:.25rem 0;}
    .summary .line .label{color:var(--muted, #64748b);}
  </style>

</head>
<body>
  <div class="bg-abstract"></div>
  <main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="checkout-hero">
        <i class="bi bi-credit-card-2-front"></i>
        <span>ยืนยันการสั่งซื้อ</span>
        <span class="chip">Checkout</span>
      </div>
      <a href="cart.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับตะกร้า</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="glass-card p-3">
            <div class="fw-semibold mb-2"><i class="bi bi-geo-alt"></i> ที่อยู่จัดส่ง</div>
            <div class="row g-3">
              <div class="col-12">
                <div class="form-floating">
                  <input type="text" class="form-control" id="address" name="address" placeholder="ที่อยู่"
                         value="<?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" >
                  <label for="address">ที่อยู่</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input type="text" class="form-control" id="city" name="city" placeholder="จังหวัด"
                         value="<?= htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" >
                  <label for="city">จังหวัด</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating">
                  <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="รหัสไปรษณีย์"
                         value="<?= htmlspecialchars($_POST['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" >
                  <label for="postal_code">รหัสไปรษณีย์</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating">
                  <input type="text" class="form-control" id="phone" name="phone" placeholder="เบอร์โทรศัพท์"
                         value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" >
                  <label for="phone">เบอร์โทรศัพท์</label>
                </div>
              </div>
            </div>
          </div>

          <div class="glass-card mt-3">
            <div class="p-3 pb-0 d-flex align-items-center justify-content-between">
              <div class="fw-semibold"><i class="bi bi-list-ul"></i> รายการสินค้าในตะกร้า</div>
              <small class="text-muted"><?= count($items) ?> รายการ</small>
            </div>
            <ul class="list-group list-group-flush">
              <?php foreach ($items as $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?>
                  </div>
                  <div><?= number_format($item['price'] * $item['quantity'], 2) ?> บาท</div>
                </li>
              <?php endforeach; ?>
              <li class="list-group-item text-end"><strong>รวมทั้งหมด: <?= number_format($total, 2) ?> บาท</strong></li>
            </ul>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="glass-card p-3 summary">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold"><i class="bi bi-receipt-cutoff"></i> สรุปคำสั่งซื้อ</div>
              <span class="badge text-bg-light">รวมสุทธิ</span>
            </div>
            <div class="line"><span class="label">ยอดชำระ</span><span class="fw-bold"><?= number_format($total, 2) ?> บาท</span></div>
            <div class="small text-muted">* ยังไม่รวมค่าจัดส่ง (ถ้ามี)</div>
            <hr class="my-3"/>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-pay btn-lg"><i class="bi bi-check2-circle"></i> ยืนยันการสั่งซื้อ</button>
              <a href="cart.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับตะกร้า</a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
