<?php
require '../config.php';         // เชื่อมต่อข้อมูลด้วย PDO
require 'auth_admin.php';        // กำหนดสิทธิ์ (Admin Guard)

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'member') !== 'admin') {
  header("Location: login.php"); // หน้า login
  exit;
}

$user_id = $_SESSION['user_id'];

// -----------------------------
// ดึงคำสั่งซื้อทั้งหมด (ใหม่สุดก่อน)
// -----------------------------
$stmt = $conn->query("
  SELECT o.*, u.username
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.user_id
  ORDER BY o.order_date DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------
// ฟังก์ชัน: รายการสินค้าในคำสั่งซื้อ
// -----------------------------
function getOrderItems($conn, $order_id) {
  $stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
  ");
  $stmt->execute([$order_id]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// -----------------------------
// ฟังก์ชัน: ข้อมูลการจัดส่ง
// -----------------------------
function getShippingInfo($conn, $order_id) {
  $stmt = $conn->prepare("SELECT * FROM shipping WHERE order_id = ?");
  $stmt->execute([$order_id]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

// -----------------------------
// Map สถานะ → badge class (โทนเดียวกับ cart)
// -----------------------------
function statusBadgeClass($status) {
  $s = strtolower(trim((string)$status));
  return match ($s) {
    'pending'    => 'text-bg-warning',
    'processing' => 'text-bg-info',
    'paid'       => 'text-bg-primary',
    'shipped'    => 'text-bg-info',
    'completed', 'delivered' => 'text-bg-success',
    'cancelled', 'canceled', 'failed' => 'text-bg-danger',
    default      => 'text-bg-light'
  };
}

// -----------------------------
// อัปเดตสถานะคำสั่งซื้อ / การจัดส่ง
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['update_status'])) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: orders.php"); exit;
  }
  if (isset($_POST['update_shipping'])) {
    $stmt = $conn->prepare("UPDATE shipping SET shipping_status = ? WHERE shipping_id = ?");
    $stmt->execute([$_POST['shipping_status'], $_POST['shipping_id']]);
    header("Location: orders.php"); exit;
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการคำสั่งซื้อ (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
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

    /* ตาราง (desktop) โทนเดียวกับ cart */
    table.order-table{width:100%; border-collapse:collapse}
    table.order-table thead th{
      font-weight:700; color:#334155; border-bottom:1px solid var(--stroke);
      padding:12px 10px; background:#fff8; backdrop-filter:blur(6px);
    }
    table.order-table tbody td{
      padding:14px 10px; border-bottom:1px solid rgba(15,23,42,.06); vertical-align:top;
    }
    .meta{color:var(--muted); font-size:.92rem}
    .badge{font-weight:700; letter-spacing:.2px; text-transform:capitalize;}
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

    /* mobile → การ์ด */
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
    <!-- Header -->
    <div class="card-glass p-3 p-md-4 mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h1 class="title">จัดการคำสั่งซื้อทั้งหมด</h1>
          <p class="sub">ดูรายละเอียดคำสั่งซื้อ อัปเดตสถานะการชำระเงิน/จัดส่ง ในโทนเดียวกับตะกร้าสินค้า</p>
        </div>
        <div class="d-flex gap-2">
          <a href="index.php" class="btn btn-pay"><i class="bi bi-arrow-left"></i> กลับหน้าผู้ดูแล</a>
        </div>
      </div>
    </div>

    <?php if (!$orders): ?>
      <div class="card-glass p-4 text-center">
        <div class="display-6 mb-2">📦</div>
        <h5 class="mb-2">ยังไม่มีคำสั่งซื้อ</h5>
        <p class="text-muted mb-4">เมื่อมีออเดอร์เข้ามา จะแสดงในหน้านี้</p>
        <a href="../products.php" class="btn btn-pay btn-lg"><i class="bi bi-bag"></i> ไปหน้าสินค้า</a>
      </div>
    <?php else: ?>

      <!-- Desktop: ตารางสรุป + ปุ่มแก้ไขในแถว -->
      <div class="card-glass p-2 p-md-3 desktop-only">
        <div class="table-responsive">
          <table class="order-table">
            <thead>
              <tr>
                <th style="width:140px;">รหัสออเดอร์</th>
                <th style="width:180px;">วันที่</th>
                <th style="width:200px;">ผู้สั่งซื้อ</th>
                <th>รายการ & จัดส่ง</th>
                <th style="width:150px;" class="text-end">ยอดรวม</th>
                <th style="width:180px;">สถานะ</th>
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
                    <div class="meta">สถานะชำระ: <?= htmlspecialchars(ucfirst($order['status'])) ?></div>
                  </td>
                  <td class="align-middle">
                    <div><?= htmlspecialchars($order['order_date']) ?></div>
                    <?php if ($shipping): ?>
                      <div class="meta">จัดส่ง: <?= htmlspecialchars(ucfirst($shipping['shipping_status'] ?? $shipping['status'] ?? '')) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="align-middle">
                    <?= htmlspecialchars($order['username'] ?? 'ผู้ใช้ถูกลบ') ?>
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
                  <td class="align-middle">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <span class="badge <?= $badge ?> px-3 py-2"><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
                      <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                        <select name="status" class="form-select form-select-sm" style="max-width:150px">
                          <?php
                            $statuses = ['pending','processing','shipped','completed','cancelled'];
                            foreach ($statuses as $s) {
                              $sel = (($order['status'] ?? '') === $s) ? 'selected' : '';
                              echo "<option value=\"$s\" $sel>$s</option>";
                            }
                          ?>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-outline-primary btn-sm">
                          บันทึก
                        </button>
                      </form>
                    </div>
                    <?php if ($shipping): ?>
                      <div class="mt-2">
                        <form method="post" class="d-flex gap-2">
                          <input type="hidden" name="shipping_id" value="<?= htmlspecialchars($shipping['shipping_id']) ?>">
                          <select name="shipping_status" class="form-select form-select-sm" style="max-width:150px">
                            <?php
                              $s_statuses = ['not_shipped','shipped','delivered'];
                              $curr = $shipping['shipping_status'] ?? ($shipping['status'] ?? '');
                              foreach ($s_statuses as $ss) {
                                $sel = ($curr === $ss) ? 'selected' : '';
                                echo "<option value=\"$ss\" $sel>$ss</option>";
                              }
                            ?>
                          </select>
                          <button type="submit" name="update_shipping" class="btn btn-outline-success btn-sm">
                            อัปเดตจัดส่ง
                          </button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile: การ์ดสรุป (สไตล์เดียวกับ cart) -->
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
                <div class="order-meta"><?= htmlspecialchars($order['order_date']) ?> · <?= htmlspecialchars($order['username'] ?? 'ผู้ใช้ถูกลบ') ?></div>
              </div>
              <span class="badge <?= $badge ?> align-self-start px-3 py-2"><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
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
              <div class="fw-bold">ยอดรวม</div>
              <div class="money fw-bold fs-5"><?= number_format($order['total_amount'], 2) ?> ฿</div>
            </div>

            <!-- ปุ่มแก้ไขอย่างย่อ -->
            <div class="mt-2 d-flex gap-2">
              <form method="post" class="d-flex gap-2 flex-grow-1">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                <select name="status" class="form-select form-select-sm">
                  <?php
                    $statuses = ['pending','processing','shipped','completed','cancelled'];
                    foreach ($statuses as $s) {
                      $sel = (($order['status'] ?? '') === $s) ? 'selected' : '';
                      echo "<option value=\"$s\" $sel>$s</option>";
                    }
                  ?>
                </select>
                <button type="submit" name="update_status" class="btn btn-outline-primary btn-sm">บันทึก</button>
              </form>
              <?php if ($shipping): ?>
                <form method="post" class="d-flex gap-2">
                  <input type="hidden" name="shipping_id" value="<?= htmlspecialchars($shipping['shipping_id']) ?>">
                  <select name="shipping_status" class="form-select form-select-sm">
                    <?php
                      $s_statuses = ['not_shipped','shipped','delivered'];
                      $curr = $shipping['shipping_status'] ?? ($shipping['status'] ?? '');
                      foreach ($s_statuses as $ss) {
                        $sel = ($curr === $ss) ? 'selected' : '';
                        echo "<option value=\"$ss\" $sel>$ss</option>";
                      }
                    ?>
                  </select>
                  <button type="submit" name="update_shipping" class="btn btn-outline-success btn-sm">จัดส่ง</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
