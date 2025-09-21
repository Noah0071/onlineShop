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
      margin:0; background:var(--bg); color:var(--ink);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
    }

    /* พื้นหลังนามธรรม */
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

    .page{ min-height:100svh; display:grid; place-items:start center; padding:24px; }

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
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip: text; background-clip: text; color: transparent;
      font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
    }
    .hello{ color:var(--muted); font-weight:600; }
    .body{ padding:22px; }

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

    /* ===== Product Card – Clean Minimal ===== */
    .product-list{ row-gap:18px; }

    .product-card{
      background:#fff;
      border:1px solid #ebeff5;
      border-radius:16px;
      box-shadow:0 6px 16px rgba(15,23,42,.06);
      transition:transform .12s ease, box-shadow .12s ease;
      display:flex; flex-direction:column; overflow:hidden;
    }
    .product-card:hover{
      transform:translateY(-2px);
      box-shadow:0 10px 24px rgba(15,23,42,.10);
    }

    .thumb-wrap{ position:relative; }
    .product-thumb{
      width:100%; aspect-ratio: 4 / 3;
      object-fit:cover; display:block;
      background:#f4f6fa;
    }
    .wishlist{
      position:absolute; right:10px; top:10px; z-index:2;
      width:34px; height:34px; display:grid; place-items:center;
      background:#ffffffd9; border:1px solid #e9eef8; border-radius:10px; color:#94a3b8;
    }
    .wishlist:hover{ color:#e11d48; }

    .card-chip{
      position:absolute; top:10px; left:10px;
      background:#eef2ff; color:#3f51b5; border:1px solid #e0e7ff;
      font-size:.72rem; padding:.2rem .5rem; border-radius:999px;
      box-shadow:0 2px 6px rgba(15,23,42,.08);
    }
    .card-chip.danger{ background:#fff1f2; color:#e11d48; border-color:#ffe4e6; }

    .card-body-slim{ padding:14px 14px 12px; display:flex; flex-direction:column; }
    .product-meta{
      font-size:.72rem; letter-spacing:.06em; text-transform:uppercase;
      color:#8a94a6; margin-bottom:6px;
    }
    .product-title{
      font-size:1rem; font-weight:700; color:#0f172a;
      margin:0 0 6px; line-height:1.25;
    }
    .rating i{ color:#ffc107; font-size:.9rem; }

    .price{ font-weight:800; color:#0f172a; }
    .stock-badge{
      background:#f6f7fb; border:1px solid #ebeff5; color:#64748b;
      border-radius:999px; padding:.15rem .5rem; font-size:.75rem;
    }

    .card-actions{ margin-top:10px; display:flex; gap:.5rem; align-items:center; }
    .card-actions .btn{ white-space:nowrap; }
    .btn-detail{
      background:#fff; border:1px solid #dfe3f3; color:#334155; font-weight:700;
      border-radius:10px; padding:.42rem .8rem;
    }
    .btn-detail:hover{ background:#f8fafc; }
    .btn-cart{
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      border:none; color:#fff; font-weight:800; border-radius:10px;
      padding:.42rem .8rem; box-shadow:0 8px 18px rgba(91,140,255,.18);
    }
    .btn-cart:disabled{ opacity:.6; box-shadow:none; }
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
        <div class="row product-list">
          <?php foreach ($products as $p): ?>
            <?php
              $img = !empty($p['image'])
                ? 'product_images/' . rawurlencode($p['image'])
                : 'product_images/no-image.jpg';

              $isNew = isset($p['created_at']) && (time() - strtotime($p['created_at']) <= 7*24*3600);
              $isHot = (int)$p['stock'] > 0 && (int)$p['stock'] < 5;

              $rating = isset($p['rating']) ? (float)$p['rating'] : 4.5;
              $full = floor($rating);
              $half = ($rating - $full) >= 0.5 ? 1 : 0;
            ?>
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="product-card h-100">
                <div class="thumb-wrap">
                  <?php if ($isNew): ?>
                    <span class="card-chip">NEW</span>
                  <?php elseif ($isHot): ?>
                    <span class="card-chip danger">HOT</span>
                  <?php endif; ?>

                  <a href="product_detail.php?id=<?= (int)$p['product_id'] ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" class="product-thumb">
                  </a>

                  <button class="wishlist" type="button" title="เพิ่มรายการที่อยากได้">
                    <i class="bi bi-heart"></i>
                  </button>
                </div>

                <div class="card-body-slim">
                  <div class="product-meta"><?= htmlspecialchars($p['category_name'] ?? 'หมวดหมู่') ?></div>
                  <a class="text-decoration-none" href="product_detail.php?id=<?= (int)$p['product_id'] ?>">
                    <h6 class="product-title"><?= htmlspecialchars($p['product_name']) ?></h6>
                  </a>

                  <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rating">
                      <?php for ($i=0; $i<$full; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                      <?php if ($half): ?><i class="bi bi-star-half"></i><?php endif; ?>
                      <?php for ($i=0; $i<5-$full-$half; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between">
                    <div class="price"><?= number_format((float)$p['price'], 2) ?> บาท</div>
                    <span class="stock-badge">คงเหลือ <?= (int)$p['stock'] ?></span>
                  </div>

                  <div class="card-actions">
                    <?php if ($isLoggedIn): ?>
                      <form action="cart.php" method="post" class="d-inline-flex">
                        <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-sm btn-cart">
                          <i class="bi bi-cart-plus"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                    <a href="product_detail.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm btn-detail ms-auto">
                      <i class="bi bi-eye"></i> ดูรายละเอียด
                    </a>
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
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
