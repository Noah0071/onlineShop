<?php
// admin/products.php  — จัดการสินค้า (เพิ่ม/ลบ/รายการ)
require '../config.php';
require 'auth_admin.php'; // ต้องเป็นแอดมินเท่านั้น

$errors = [];

// ---------- เพิ่มสินค้าใหม่ ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name        = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $stock       = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    // validate เบื้องต้น
    if ($name === '')            $errors[] = 'กรุณากรอกชื่อสินค้า';
    if ($price <= 0)             $errors[] = 'ราคาต้องมากกว่า 0';
    if ($stock < 0)              $errors[] = 'จำนวนคงเหลือต้องไม่ติดลบ';
    if ($category_id <= 0)       $errors[] = 'กรุณาเลือกหมวดหมู่สินค้า';

    // อัปโหลดรูป (ถ้ามี)
    $imageName = null;
    if (!empty($_FILES['product_image']['name'])) {
        $file = $_FILES['product_image'];
        if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
            // ตรวจ mime ให้ชัดเจน
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!isset($allowed[$mime])) {
                $errors[] = 'ไฟล์รูปต้องเป็น JPG หรือ PNG เท่านั้น';
            } else {
                $ext = $allowed[$mime];
                $imageName = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $path = __DIR__ . '/../product_images/' . $imageName;
                if (!move_uploaded_file($file['tmp_name'], $path)) {
                    $errors[] = 'อัปโหลดรูปไม่สำเร็จ';
                }
            }
        } else {
            $errors[] = 'อัปโหลดรูปไม่สำเร็จ';
        }
    }

    if (!$errors) {
        $stmt = $conn->prepare("
          INSERT INTO products (product_name, description, price, stock, category_id, image)
          VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $imageName]);
        header("Location: products.php");
        exit;
    }
}

// ---------- ลบสินค้า (ลบรูปในดิสก์ด้วย) ----------
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];

    // 1) ขอชื่อไฟล์รูปก่อน
    $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $imageName = $stmt->fetchColumn(); // อาจเป็น null

    // 2) ลบใน DB ด้วย Transaction
    try {
        $conn->beginTransaction();
        $del = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $del->execute([$product_id]);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: products.php");
        exit;
    }

    // 3) ลบไฟล์รูปหลัง DB ลบสำเร็จ
    if ($imageName) {
        $baseDir = realpath(__DIR__ . '/../product_images');
        $filePath = realpath($baseDir . '/' . $imageName);
        if ($filePath && strpos($filePath, $baseDir) === 0 && is_file($filePath)) {
            @unlink($filePath);
        }
    }

    header("Location: products.php");
    exit;
}

// ---------- ดึงรายการสินค้า (รวมคอลัมน์ image) ----------
$stmt = $conn->query("
  SELECT p.*, c.category_name
  FROM products p
  LEFT JOIN categories c ON p.category_id = c.category_id
  ORDER BY p.created_at DESC, p.product_id DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- ดึงหมวดหมู่ ----------
$categories = $conn->query("
  SELECT category_id, category_name
  FROM categories
  ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการสินค้า</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --bg: #f6f7fb;
      --ink: #0f172a;
      --muted: #64748b;
      --card: #ffffffcc;        /* glass */
      --stroke: #e6e8ef;
      --brand: #5b8cff;         /* ฟ้าพาสเทล */
      --brand2:#8b5bff;         /* ม่วงพาสเทล */
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

    .page{ min-height:100svh; display:grid; place-items:start center; padding:24px; }

    /* การ์ดครอบหน้าแบบ Glassmorphism */
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
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
    }
    .body{ padding:22px; }

    /* ปุ่มโทนเดียวกับชุด register */
    .btn-brand{
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      border:none; color:#fff; font-weight:800;
      box-shadow:0 10px 24px rgba(91,140,255,.25);
    }
    .btn-ghost{
      border:1px solid #dfe3f3; background: linear-gradient(135deg, var(--brand), var(--brand2)); font-weight:700; color:black;
    }

    /* การ์ดฟอร์ม + ตาราง */
    .block{
      background:#fff;
      border:1px solid #e8eaf3;
      border-radius:16px;
      box-shadow:0 10px 24px rgba(15,23,42,.06);
      padding:18px;
    }

    .form-label{ font-weight:700; color:#1f2937; }
    .form-control, .form-select{
      background:#fff; color:#0f172a; border:1px solid #dfe3f3;
    }
    .form-control::placeholder{ color:#9aa3b8; }
    .form-control:focus, .form-select:focus{
      border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18);
    }

    table.table{
      background:#fff; border-color:#eef1f7;
    }
    thead.table-light th{
      background:#f3f6ff; border-bottom:1px solid #e5e9f5;
      color:#334155; font-weight:800;
    }
    tbody td{ vertical-align: middle; }

    .mb-18{ margin-bottom:18px; }
    .thumb{ width:60px; height:60px; object-fit:cover; background:#f1f5f9; }
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="page">
    <div class="card-glass">
      <div class="head">
        <h1 class="title">จัดการสินค้า</h1>
        <div class="d-flex gap-2">
          <a href="index.php" class="btn btn-ghost">← กลับหน้าผู้ดูแล</a>
        </div>
      </div>

      <div class="body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger mb-18">
            <div><strong>ไม่สามารถบันทึกได้</strong></div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- ฟอร์มเพิ่มสินค้า -->
        <div class="block mb-18">
          <h5 class="mb-3">เพิ่มสินค้าใหม่</h5>
          <form method="post" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="add_product" value="1">

            <div class="col-md-4">
              <label class="form-label" for="pname">ชื่อสินค้า</label>
              <input id="pname" type="text" name="product_name" class="form-control" placeholder="ชื่อสินค้า" required>
            </div>

            <div class="col-md-2">
              <label class="form-label" for="price">ราคา (บาท)</label>
              <input id="price" type="number" step="0.01" min="0" name="price" class="form-control" placeholder="0.00" required>
            </div>

            <div class="col-md-2">
              <label class="form-label" for="stock">จำนวนคงเหลือ</label>
              <input id="stock" type="number" min="0" name="stock" class="form-control" placeholder="0" required>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="cat">หมวดหมู่</label>
              <select id="cat" name="category_id" class="form-select" required>
                <option value="">เลือกหมวดหมู่</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label" for="desc">รายละเอียด (ไม่บังคับ)</label>
              <textarea id="desc" name="description" class="form-control" rows="2" placeholder="รายละเอียดสินค้า"></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">รูปสินค้า (JPG, PNG)</label>
              <input type="file" name="product_image" class="form-control" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-brand">เพิ่มสินค้า</button>
            </div>
          </form>
        </div>

        <!-- ตารางรายการสินค้า -->
        <div class="block">
          <h5 class="mb-3">รายการสินค้า</h5>
          <?php if (!$products): ?>
            <div class="alert alert-warning mb-0">ยังไม่มีสินค้าในระบบ</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:80px;">รูป</th> <!-- คอลัมน์รูป -->
                    <th style="min-width:220px;">ชื่อสินค้า</th>
                    <th>หมวดหมู่</th>
                    <th class="text-end">ราคา (บาท)</th>
                    <th class="text-end">คงเหลือ</th>
                    <th style="min-width:160px;">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $p): ?>
                    <tr>
                      <td class="align-middle">
                        <?php
                          $baseDir = realpath(__DIR__ . '/../product_images');
                          $imgSrc = '';

                          // ใช้รูปจาก DB ถ้ามีและไฟล์อยู่จริง
                          if (!empty($p['image'])) {
                            $diskPath = realpath($baseDir . '/' . $p['image']);
                            if ($diskPath && strpos($diskPath, $baseDir) === 0 && is_file($diskPath)) {
                              $imgSrc = '../product_images/' . rawurlencode($p['image']);
                            }
                          }

                          // ถ้าไม่พบรูป -> ใช้ no-image.png ถ้ามี, ไม่งั้นค่อยเป็น SVG
                          if ($imgSrc === '') {
                            $ph = realpath($baseDir . '../product_images/no-image.png');
                            if ($ph && strpos($ph, $baseDir) === 0 && is_file($ph)) {
                              $imgSrc = '../product_images/no-image.png';
                            } else {
                              $svg = '
                                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
                                  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#e5e7eb"/><stop offset="100%" stop-color="#f1f5f9"/>
                                  </linearGradient></defs>
                                  <rect width="60" height="60" rx="8" fill="url(#g)"/>
                                  <g fill="none" stroke="#9ca3af" stroke-width="2">
                                    <rect x="12" y="16" width="36" height="28" rx="6"/>
                                    <circle cx="24" cy="28" r="6"/>
                                    <path d="M18 42l10-10 8 8 6-6 8 8"/>
                                  </g>
                                </svg>';
                              $imgSrc = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
                            }
                          }
                        ?>
                        <img src="<?= $imgSrc ?>" alt="รูปสินค้า" class="rounded thumb"
                            onerror="this.onerror=null;this.src='../product_images/no-image.png';">
                      </td>
                      <td>
                        <div class="fw-semibold"><?= htmlspecialchars($p['product_name']) ?></div>
                        <?php if (!empty($p['description'])): ?>
                          <div class="text-muted small"><?= nl2br(htmlspecialchars($p['description'])) ?></div>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                      <td class="text-end"><?= number_format((float)$p['price'], 2) ?></td>
                      <td class="text-end"><?= (int)$p['stock'] ?></td>
                      <td>
                        <a href="edit_products.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                        <a href="products.php?delete=<?= (int)$p['product_id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('ยืนยันการลบสินค้านี้หรือไม่?')">ลบ</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
