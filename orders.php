<?php
require 'session_timeout.php'; 
require 'config.php';

// Guard
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php"); exit;
}
$user_id = $_SESSION['user_id'];

// orders ของผู้ใช้ (ใหม่สุดก่อน)
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รายการสินค้าในออเดอร์
function getOrderItems($conn, $order_id) {
  $st = $conn->prepare("SELECT oi.quantity, oi.price, p.product_name
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.product_id
                        WHERE oi.order_id = ?");
  $st->execute([$order_id]);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ข้อมูลจัดส่ง
function getShippingInfo($conn, $order_id) {
  $st = $conn->prepare("SELECT * FROM shipping WHERE order_id = ?");
  $st->execute([$order_id]);
  return $st->fetch(PDO::FETCH_ASSOC);
}

// map สถานะ → badge
function statusBadgeClass($status) {
  $s = strtolower(trim((string)$status));
  return match ($s) {
    'pending'   => 'text-bg-warning',
    'paid'      => 'text-bg-primary',
    'processing'=> 'text-bg-info',
    'shipped'   => 'text-bg-info',
    'delivered' => 'text-bg-success',
    'cancelled','canceled','failed' => 'text-bg-danger',
    default     => 'text-bg-light'
  };
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ประวัติการสั่งซื้อ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root{
      --bg:#f6f7fb; --ink:#0f172a; --muted:#64748b;
      --card:#ffffffcc; --stroke:#e6e8ef;
      --brand:#5b8cff; --brand2:#8b5bff;
    }
    html,body{height:100%}
    body{
      color:var(--ink); background:var(--bg);
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }
    .bg-abstract{
      position:fixed; inset:-20vmax; z-index:-1; filter:blur(80px) saturate(1.1);
      background:
        radial-gradient(35vmax 35vmax at 10% 15%, rgba(91,140,255,.25), transparent 60%),
        radial-gradient(35vmax 35vmax at 90% 10%, rgba(139,91,255,.25), transparent 60%),
        radial-gradient(40vmax 40vmax at 10% 90%, rgba(91,140,255,.15), transparent 60%),
        radial-gradient(40vmax 40vmax at 90% 85%, rgba(139,91,255,.15), transparent 60%);
      pointer-events:none;
    }
    .wrap{max-width:min(1100px,96vw); margin:40px auto; padding:0 8px}
    .card-glass{
      background:var(--card);
      border:1px solid var(--stroke);
      backdrop-filter:saturate(110%) blur(10px);
      box-shadow:0 10px 30px rgba(15,23,42,.06);
      border-radius:20px;
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size:clamp(1.2rem,.9rem + 1.2vw,1.8rem);
    }
    .sub{margin:6px 0 0; color:var(--muted); font-size:.95rem}

    /* ตารางออเดอร์ ให้ฟีลเดียวกับ cart */
    table.order-table{width:100%; border-collapse:collapse}
    table.order-table thead th{
      font-weight:700; color:#334155; border-bottom:1px solid var(--stroke);
      padding:12px 10px; background:#fff8; backdrop-filter:blur(6px);
    }
    table.order-table tbody td{
      padding:14px 10px; border-bottom:1px solid rgba(15,23,42,.06); vertical-align:top;
    }
    .meta{color:var(--muted); font-size:.92rem}
    .badge{font-weight:700; letter-spacing:.2px}
    .money{font-variant-numeric:tabular-nums}
    .btn-pay{
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      border:none; color:#fff;
      box-shadow:0 8px 18px rgba(91,140,255,.25);
    }
    .btn-pay:hover{opacity:.95}
    details summary{cursor:pointer; list-style:none}
    details summary::-webkit-details-marker{display:none}
    .list-group-item{border-color:rgba(15,23,42,.06)}

    /* mobile: แสดงเป็นการ์ดเหมือน cart */
    @media (max-width: 768px){
      .desktop-only{display:none}
      .order-card{display:block; border:1px solid var(--stroke)}
      .order-head{display:flex; gap:10px; justify-content:space-between; align-items:center}
      .order-meta{color:var(--muted); font-size:.95rem}
    }
    @media (min-width: 769px){ .mobile-only{display:none} }
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <main class="wrap">
    <div class="card-glass p-3 p-md-4 mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h1 class="title">ประวัติการสั่งซื้อ</h1>
          <p class="sub">รวมคำสั่งซื้อทั้งหมดของคุณ พร้อมสถานะการชำระเงินและจัดส่ง</p>
        </div>
        <div class="d-flex gap-2">
          <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับหน้าหลัก</a>
          <a href="index.php" class="btn btn-pay"><i class="bi bi-plus-circle"></i> เลือกสินค้าเพิ่ม</a>
        </div>
      </div>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success mt-3 mb-0"><i class="bi bi-check-circle"></i> ทำรายการสั่งซื้อเรียบร้อยแล้ว</div>
      <?php endif; ?>
    </div>

    <?php if (!$orders): ?>
      <div class="card-glass p-4 text-center">
        <div class="display-6 mb-2">🛒</div>
        <h5 class="mb-2">ยังไม่มีประวัติการสั่งซื้อ</h5>
        <p class="text-muted mb-4">เริ่มช้อปสินค้าที่คุณสนใจได้เลย</p>
        <a href="products.php" class="btn btn-pay btn-lg"><i class="bi bi-bag-plus"></i> ไปเลือกสินค้า</a>
      </div>
    <?php else: ?>

      <!-- Desktop table -->
      <div class="card-glass p-2 p-md-3 desktop-only">
        <div class="table-responsive">
          <table class="order-table">
            <thead>
              <tr>
                <th style="width:140px;">รหัสออเดอร์</th>
                <th style="width:180px;">วันที่</th>
                <th>รายการ</th>
                <th style="width:150px;" class="text-end">ยอดรวม</th>
                <th style="width:140px;">สถานะ</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <?php
                  $items = getOrderItems($conn, $order['order_id']);
                  $shipping = getShippingInfo($conn, $order['order_id']);
                  $badge = statusBadgeClass($order['status'] ?? '');
                ?>
                <tr>
                  <td class="align-middle">
                    <div class="fw-bold">#<?= htmlspecialchars($order['order_id']) ?></div>
                    <div class="meta">ชำระ: <?= htmlspecialchars(ucfirst($order['status'])) ?></div>
                  </td>
                  <td class="align-middle">
                    <div><?= htmlspecialchars($order['order_date']) ?></div>
                    <?php if ($shipping): ?>
                      <div class="meta">ส่ง: <?= htmlspecialchars(ucfirst($shipping['shipping_status'] ?? $shipping['status'] ?? '')) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <details>
                      <summary class="text-primary">ดูรายการสินค้า (<?= count($items) ?>)</summary>
                      <ul class="list-group mt-2">
                        <?php foreach ($items as $it): ?>
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= htmlspecialchars($it['product_name']) ?> × <?= (int)$it['quantity'] ?></span>
                            <span class="money"><?= number_format($it['quantity'] * $it['price'], 2) ?> ฿</span>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </details>
                    <?php if ($shipping): ?>
                      <div class="mt-2 meta">
                        <i class="bi bi-geo-alt"></i>
                        <?= htmlspecialchars($shipping['address']) ?>,
                        <?= htmlspecialchars($shipping['city']) ?>
                        <?= htmlspecialchars($shipping['postal_code']) ?>
                        <?php if (!empty($shipping['phone'])): ?>
                          · <i class="bi bi-telephone"></i> <?= htmlspecialchars($shipping['phone']) ?>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="text-end align-middle money fw-bold"><?= number_format($order['total_amount'], 2) ?> ฿</td>
                  <td class="align-middle"><span class="badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile cards -->
      <div class="mobile-only">
        <?php foreach ($orders as $order): ?>
          <?php
            $items = getOrderItems($conn, $order['order_id']);
            $shipping = getShippingInfo($conn, $order['order_id']);
            $badge = statusBadgeClass($order['status'] ?? '');
          ?>
          <div class="card-glass p-3 mb-3 order-card">
            <div class="order-head">
              <div>
                <div class="fw-bold">#<?= htmlspecialchars($order['order_id']) ?></div>
                <div class="order-meta"><?= htmlspecialchars($order['order_date']) ?></div>
              </div>
              <span class="badge <?= $badge ?> align-self-start"><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
            </div>

            <details class="mt-2">
              <summary class="text-primary">ดูรายการสินค้า (<?= count($items) ?>)</summary>
              <ul class="list-group mt-2">
                <?php foreach ($items as $it): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($it['product_name']) ?> × <?= (int)$it['quantity'] ?></span>
                    <span class="money"><?= number_format($it['quantity'] * $it['price'], 2) ?> ฿</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </details>

            <?php if ($shipping): ?>
              <div class="mt-2 order-meta">
                <i class="bi bi-truck"></i>
                <?= htmlspecialchars(ucfirst($shipping['shipping_status'] ?? $shipping['status'] ?? '')) ?>
                · <i class="bi bi-geo-alt"></i>
                <?= htmlspecialchars($shipping['address']) ?>,
                <?= htmlspecialchars($shipping['city']) ?>
                <?= htmlspecialchars($shipping['postal_code']) ?>
                <?php if (!empty($shipping['phone'])): ?>
                  · <i class="bi bi-telephone"></i> <?= htmlspecialchars($shipping['phone']) ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center">
              <div class="fw-bold">รวมทั้งสิ้น</div>
              <div class="money fw-bold fs-5"><?= number_format($order['total_amount'], 2) ?> ฿</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
