<?php
require 'session_timeout.php'; 
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

// เตรียมรูป
$img = !empty($product['image'])
  ? 'product_images/' . rawurlencode($product['image'])
  : 'product_images/no-image.jpg';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>รายละเอียดสินค้า</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- โทนสว่าง-มินิมอล + กลาสโมร์ฟิก + พื้นหลังนามธรรม -->
  <style>
    :root{
      --bg:#f6f7fb;
      --ink:#0f172a;
      --muted:#64748b;
      --card:#ffffffcc;      /* glass */
      --stroke:#e6e8ef;
      --brand:#5b8cff;
      --brand2:#8b5bff;
      --success:#16a34a;
      --chip:#eef2ff;
    }

    html,body{height:100%}
    body{
      margin:0; min-height:100vh; overflow-x:hidden;
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
      color:var(--ink); background:var(--bg); padding:24px 12px;
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

    /* การ์ด glass */
    .detail-card{
      width:min(980px,96vw);
      margin:28px auto;
      padding:0;
      background:var(--card);
      backdrop-filter:blur(12px);
      border:1px solid var(--stroke);
      border-radius:24px;
      box-shadow:0 12px 36px rgba(15,23,42,.12);
      overflow:hidden;
    }

    .card-head{
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
      padding:18px 20px;
      border-bottom:1px solid rgba(15,23,42,.06);
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size: clamp(1.1rem,.8rem + 1.2vw,1.6rem);
    }
    .btn-back{
      border:1px solid #dfe3f3; background:#fff; color:#334155; font-weight:700;
      border-radius:12px; padding:8px 14px; box-shadow:0 8px 20px rgba(15,23,42,.08);
      transition:.15s ease;
    }
    .btn-back:hover{ transform:translateY(-1px); }

    .card-body-wrap{ padding:18px 18px 22px; }

    /* เลย์เอาต์รายละเอียด */
    .detail-grid{
      display:grid; gap:18px;
      grid-template-columns: 1fr 1.1fr;
    }
    @media (max-width: 768px){
      .detail-grid{ grid-template-columns: 1fr; }
    }

    /* รูปสินค้า */
    .media-wrap{
      background:#fff; border:1px solid #e8eaf3;
      border-radius:18px; padding:12px;
      box-shadow:0 10px 24px rgba(15,23,42,.06);
    }
    .product-media{
      width:100%; aspect-ratio: 1 / 1; object-fit:cover;
      border-radius:14px;
      box-shadow:0 6px 18px rgba(15,23,42,.10);
    }

    /* กล่องข้อมูล */
    .info{
      background:#fff; border:1px solid #e8eaf3;
      border-radius:18px; padding:16px 18px;
      box-shadow:0 10px 24px rgba(15,23,42,.06);
    }
    .chip{
      display:inline-block; padding:6px 10px; border-radius:999px;
      background:var(--chip); color:#3730a3; font-weight:700; font-size:.85rem;
      border:1px solid #e1e6ff;
    }
    .price{
      font-weight:900; margin:10px 0 6px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size: clamp(1.4rem,1rem + 1.6vw,2rem);
    }
    .stock{
      font-weight:700; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0;
      display:inline-block; padding:6px 10px; border-radius:10px; font-size:.9rem;
    }
    .desc{
      color:#334155; background:#f8fafc; border:1px solid #e2e8f0;
      border-radius:12px; padding:12px; margin-top:10px;
      max-height:180px; overflow:auto; white-space:pre-wrap;
    }

    /* ฟอร์มสั่งซื้อ */
    label{ font-weight:700; color:#1f2937; margin-right:8px; }
    input[type="number"]{
      background:#fff; color:#0f172a; border:1px solid #dfe3f3; border-radius:12px;
      padding:10px 12px; width:140px; outline:none;
    }
    input[type="number"]:focus{ border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18); }

    .btn-buy{
      width:100%; border:none; color:#fff; font-weight:800; border-radius:12px; padding:12px;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      box-shadow:0 12px 24px rgba(91,140,255,.25);
      transition:.15s ease; 
    }
    .btn-buy:hover{ filter:brightness(1.05); transform:translateY(-1px); }

    .alert{
      border-radius:14px; border:1px solid #cfefff;
      background:#f3fbff; color:#0b4a6f;
    }
    /* ทำให้กล่องข้อมูลเป็นคอลัมน์ และให้ฟอร์มกินพื้นที่ที่เหลือ */
.info{
  display: flex;
  flex-direction: column;
}

/* ฟอร์มสั่งซื้อเป็นคอลัมน์และขยายกินพื้นที่ใน .info */
.form-buy{
  display: flex;
  flex-direction: column;
  flex: 1;
}

/* ดันปุ่มลงล่างสุดของฟอร์ม */
.form-buy .btn-buy{
  margin-top: auto;
}

/* แถวจำนวนให้จัดวางสวย ๆ */
.qty-row{
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="detail-card">
    <div class="card-head">
      <h1 class="title">รายละเอียดสินค้า</h1>
      <a href="index.php" class="btn-back">← กลับหน้ารายการสินค้า</a>
    </div>

    <div class="card-body-wrap">
      <div class="detail-grid">
        <!-- ซ้าย: รูปสินค้า -->
        <div class="media-wrap">
          <img src="<?= $img ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="product-media">
        </div>

        <!-- ขวา: ข้อมูลสินค้า -->
        <div class="info">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="chip"><?= htmlspecialchars($product['category_name']) ?></span>
            <span class="stock">คงเหลือ <?= htmlspecialchars($product['stock']) ?> ชิ้น</span>
          </div>

          <h3 class="mt-2 mb-1"><?= htmlspecialchars($product['product_name']) ?></h3>
          <div class="price"><?= number_format((float)$product['price'], 2) ?> บาท</div>

          <?php if (!empty($product['description'])): ?>
            <div class="desc"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
          <?php endif; ?>

          <?php if ($isLoggedIn): ?>
            <form action="cart.php" method="post" class="mt-3 form-buy">
              <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

              <div class="qty-row">
                <label for="quantity" class="mb-0">จำนวน:</label>
                <input
                  type="number"
                  name="quantity"
                  id="quantity"
                  value="1"
                  min="1"
                  max="<?= $product['stock'] ?>"
                  required
                >
              </div>

              <button type="submit" class="btn-buy">เพิ่มในตะกร้า</button>
            </form>
          <?php else: ?>
            <div class="alert mt-3 text-center">กรุณาเข้าสู่ระบบเพื่อสั่งซื้อสินค้า</div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
