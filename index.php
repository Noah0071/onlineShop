<?php
session_start(); // เริ่ม session
require_once 'config.php'; // เชื่อมต่อฐานข้อมูล
$isLoggedIn = isset($_SESSION['user_id']); // ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่

$stmt = $conn->query("SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>หน้าหลัก</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- สไตล์ให้เหมือน register.php: สว่าง-มินิมอล + กลาสโมร์ฟิค + พื้นหลังนามธรรม -->
  <style>
    :root{
      --bg: #f6f7fb;
      --ink: #0f172a;
      --muted: #64748b;
      --card: #ffffffcc;   /* glass */
      --stroke: #e6e8ef;
      --brand: #5b8cff;    /* ฟ้าพาสเทล */
      --brand2:#8b5bff;    /* ม่วงพาสเทล */
      --soft: #ffffff;
    }

    html,body{height:100%}
    body{
      margin:0; background:var(--bg); color:var(--ink);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
    }

    /* พื้นหลังนามธรรม (เหมือน register.php) */
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

    .page{
      min-height:100svh; display:grid; place-items:start center; padding:24px;
    }

    /* การ์ดครอบหน้า (glass) */
    .card-glass{
      width:min(1200px, 96vw);
      background: var(--card);
      backdrop-filter: blur(12px);
      border:1px solid var(--stroke);
      border-radius:24px;
      box-shadow: 0 12px 36px rgba(15,23,42,.12);
      overflow:hidden;
    }
    .head{
      padding:22px 22px 12px;
      border-bottom:1px solid rgba(15,23,42,.06);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      flex-wrap:wrap;
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip: text; background-clip: text; color: transparent;
      font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
    }
    .hello{ color:var(--muted); font-weight:600; }

    .body{ padding:22px; }

    /* ปุ่มให้โทนเดียว */
    .btn-brand{
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      border:none; color:#fff; font-weight:800;
      box-shadow:0 10px 24px rgba(91,140,255,.25);
    }
    .btn-ghost{
      border:1px solid #dfe3f3; background:#fff; font-weight:700; color:#334155;
    }
    .btn-soft{
      border:1px solid #e8eaf3; background:#fff; color:#334155; font-weight:700;
    }

    /* การ์ดสินค้า (โทนสว่าง) */
    .prod{
      background: var(--soft);
      border:1px solid #e8eaf3;
      border-radius:16px;
      box-shadow: 0 10px 24px rgba(15,23,42,.06);
      transition: transform .15s ease, box-shadow .15s ease;
      height:100%;
    }
    .prod:hover{
      transform: translateY(-2px);
      box-shadow: 0 14px 30px rgba(15,23,42,.10);
    }
    .prod .card-body{ padding:16px; }
    .prod .card-title{ font-weight:800; margin-bottom:6px; }
    .prod .card-subtitle{ color:var(--muted); }
    .prod .card-text{ color:#334155; }

    .grid{ row-gap:18px; }
    .product-card { border: 1; background:#fff; }
.product-thumb { height: 180px; object-fit: cover; border-radius:.5rem; }
.product-meta { font-size:.75rem; letter-spacing:.05em; color:#8a8f98; text-transform:uppercase; }
.product-title { font-size:1rem; margin:.25rem 0 .5rem; font-weight:600; color:#222; }
.price { font-weight:700; }
.rating i { color:#ffc107; } /* ดำวสที อง */
.wishlist { color:#b9bfc6; }
.wishlist:hover { color:#ff5b5b; }
.badge-top-left {
position:absolute; top:.5rem; left:.5rem; z-index:2;
border-radius:.375rem;
}
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="page">
    <div class="card-glass">
      <div class="head">
        <h1 class="title">รายการสินค้า</h1>
        <div class="d-flex gap-2 flex-wrap">
          <?php if ($isLoggedIn): ?>
            <span class="hello">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username'] ?? 'ผู้ใช้') ?> (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</span>
            <a href="profile.php" class="btn btn-soft">ข้อมูลส่วนตัว</a>
            <a href="cart.php" class="btn btn-soft">ดูตะกร้าสินค้า</a>
            <a href="logout.php" class="btn btn-ghost">ออกจากระบบ</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-brand">เข้าสู่ระบบ</a>
            <a href="register.php" class="btn btn-ghost">สมัครสมาชิก</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="body">
        <!-- <div class="row grid">
          <?php foreach ($products as $product): ?>
            <div class="col-md-4">
              <div class="card prod h-100">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                  <h6 class="card-subtitle mb-2"><?= htmlspecialchars($product['category_name']) ?></h6>
                  <p class="card-text mb-2"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                  <p class="mb-3"><strong>ราคา:</strong> <?= number_format($product['price'], 2) ?> บาท</p>

                  <div class="d-flex align-items-center justify-content-between">
                    <?php if ($isLoggedIn): ?>
                      <form action="cart.php" method="post" class="m-0">
                        <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-soft btn-sm">เพิ่มในตะกร้า</button>
                      </form>
                    <?php else: ?>
                      <small class="text-muted">เข้าสู่ระบบเพื่อสั่งสินค้า</small>
                    <?php endif; ?>

                    <a href="product_detail.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-brand btn-sm">ดูรายละเอียด</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$products): ?>
            <div class="col-12">
              <div class="alert alert-warning mb-0">ยังไม่มีสินค้าในระบบ</div>
            </div>
          <?php endif; ?>
        </div> -->
      <!-- ===== สว่ นแสดงสนิ คำ้ ===== -->
<div class="row g-4"> <!-- EDIT C -->
<?php foreach ($products as $p): ?>
<!-- TODO==== เตรียมรูป / ตกแต่ง badge / ดำวรีวิว ==== -->
<?php
// เตรียมรูป
$img = !empty($p['image'])
? 'product_images/' . rawurlencode($p['image'])
: 'product_images/no-image.jpg';
// ตกแต่ง badge: NEW ภำยใน 7 วัน / HOT ถ ้ำสต็อกน้อยกว่ำ 5
$isNew = isset($p['created_at']) && (time() - strtotime($p['created_at']) <= 7*24*3600);
$isHot = (int)$p['stock'] > 0 && (int)$p['stock'] < 5;
// ดำวรีวิว (ถ ้ำไม่มีใน DB จะโชว์ 4.5 จ ำลอง; ถ ้ำมี $p['rating'] ให้แทน)
$rating = isset($p['rating']) ? (float)$p['rating'] : 4.5;
$full = floor($rating); // จ ำนวนดำวเต็ม (เต็ม 1 ดวง) , floor ปัดลง
$half = ($rating - $full) >= 0.5 ? 1 : 0; // มีดำวครึ่งดวงหรือไม่
?>
<div class="col-12 col-sm-6 col-lg-3"> <!-- EDIT C -->
<div class="card product-card h-100 position-relative"> <!-- EDIT C -->
<!-- TODO====check $isNew / $isHot ==== -->
<?php if ($isNew): ?>
<span class="badge bg-success badge-top-left">NEW</span>
<?php elseif ($isHot): ?>
<span class="badge bg-danger badge-top-left">HOT</span>
<?php endif; ?>
<!-- TODO====show Product images ==== -->
<a href="product_detail.php?id=<?= (int)$p['product_id'] ?>" class="p-3 d-block">
<img src="<?= htmlspecialchars($img) ?>"
alt="<?= htmlspecialchars($p['product_name']) ?>"
class="img-fluid w-100 product-thumb">
</a>
<div class="px-3 pb-3 d-flex flex-column"> <!-- EDIT C -->
<!-- TODO====div for category, heart ==== -->
<div class="d-flex justify-content-between align-items-center mb-1">
<div class="product-meta">
<?= htmlspecialchars($p['category_name'] ?? 'Category') ?>
</div>
<button class="btn btn-link p-0 wishlist" title="Add to wishlist" type="button">
<i class="bi bi-heart"></i>
</button>
</div>
<!-- TODO====link, div for product name ==== -->
<a class="text-decoration-none" href="product_detail.php?id=<?= (int)$p['product_id'] ?>">
<div class="product-title">
<?= htmlspecialchars($p['product_name']) ?>
</div>
</a>
<!-- TODO====div for rating ==== -->
<!-- ดำวรีวิว -->
<div class="rating mb-2">
<?php for ($i=0; $i<$full; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
<?php if ($half): ?><i class="bi bi-star-half"></i><?php endif; ?>
<?php for ($i=0; $i<5-$full-$half; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
</div>
<!-- TODO====div for price ==== -->
<div class="price mb-3">
<?= number_format((float)$p['price'], 2) ?> บำท
</div>
<!-- TODO====div for button check login ==== -->
<div class="mt-auto d-flex gap-2">
<?php if ($isLoggedIn): ?>
<form action="cart.php" method="post" class="d-inline-flex gap-2">
<input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
<input type="hidden" name="quantity" value="1">
<button type="submit" class="btn btn-sm btn-success">เพิ่มในตะกร ้ำ</button>
</form>
<?php else: ?>
<small class="text-muted">เขำ้สรู่ ะบบเพอื่ สั่งซอื้ </small>
<?php endif; ?>
<a href="product_detail.php?id=<?= (int)$p['product_id'] ?>"
class="btn btn-sm btn-outline-primary ms-auto">ดูรำยละเอียด</a>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
